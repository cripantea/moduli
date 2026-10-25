<?php

namespace App\Http\Controllers;

use App\Models\CompiledModule;
use App\Models\Tenant;
use App\Services\LaunchOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abbonamento Moduli a 29 €/mese con Stripe Checkout.
 *
 * Un'azienda in prova (plan = trial) diventa "pro" quando Stripe conferma il pagamento,
 * e torna in prova solo se l'abbonamento Stripe collegato viene chiuso. Le aziende senza
 * stripe_subscription_id (es. quelle create dal superadmin) non vengono mai toccate.
 * L'account Stripe è condiviso con altri prodotti Fusion: si considerano solo i checkout
 * con metadata app = moduli e gli abbonamenti già collegati a un'azienda.
 */
class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->user()->tenant?->isTrial()) {
            return redirect()->route('compiled.index');
        }

        return Inertia::render('Abbonamento', [
            'pronto' => $this->readyModule($request),
            'prezzo' => LaunchOffer::price(),
        ]);
    }

    public function checkout(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant?->isTrial()) {
            return redirect()->route('compiled.index');
        }

        if (! config('services.stripe.secret') || ! LaunchOffer::stripePrice()) {
            return back()->with('error', 'Pagamento non ancora disponibile: riprova tra poco.');
        }

        $back = array_filter(['pronto' => $request->integer('pronto') ?: null]);
        $successUrl = route('abbonamento.attivo', $back);
        $successUrl .= (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

        $session = $this->stripe()->checkout->sessions->create([
            'mode'                => 'subscription',
            'line_items'          => [['price' => LaunchOffer::stripePrice(), 'quantity' => 1]],
            'client_reference_id' => (string) $tenant->id,
            'metadata'            => ['app' => 'moduli', 'tenant_id' => $tenant->id],
            'subscription_data'   => ['metadata' => ['app' => 'moduli', 'tenant_id' => $tenant->id]],
            ...($tenant->stripe_customer_id
                ? ['customer' => $tenant->stripe_customer_id, 'customer_update' => ['name' => 'auto', 'address' => 'auto']]
                : ['customer_email' => $request->user()->email]),
            // Dati per la fattura elettronica: Stripe chiede indirizzo e, per le aziende, ragione sociale e P.IVA.
            'billing_address_collection' => 'required',
            'tax_id_collection'          => ['enabled' => true],
            'custom_fields'              => [
                [
                    'key'      => 'sdi',
                    'label'    => ['type' => 'custom', 'custom' => 'Codice destinatario o PEC'],
                    'type'     => 'text',
                    'optional' => true,
                    'text'     => ['maximum_length' => 100],
                ],
                [
                    'key'      => 'codice_fiscale',
                    'label'    => ['type' => 'custom', 'custom' => 'Codice fiscale (se diverso dalla P.IVA)'],
                    'type'     => 'text',
                    'optional' => true,
                    'text'     => ['minimum_length' => 11, 'maximum_length' => 16],
                ],
            ],
            'locale'                => 'it',
            'allow_promotion_codes' => true,
            'success_url'           => $successUrl,
            'cancel_url'            => route('abbonamento', $back),
        ]);

        return Inertia::location($session->url);
    }

    /**
     * Ritorno da Stripe dopo il pagamento: attiva subito (senza aspettare il webhook)
     * e propone la prenotazione della call di onboarding.
     */
    public function activated(Request $request)
    {
        $tenant = $request->user()->tenant;
        $track = [];

        if ($tenant && $request->filled('session_id') && config('services.stripe.secret')) {
            try {
                $session = $this->stripe()->checkout->sessions->retrieve($request->string('session_id'));

                if (($session->metadata->app ?? null) === 'moduli'
                    && $session->client_reference_id === (string) $tenant->id
                    && $session->status === 'complete') {
                    if ($tenant->isTrial()) {
                        $this->activate($tenant, $session);
                    }

                    // Il browser non lo ripete per la stessa sessione e Meta deduplica sull'eventID.
                    $track[] = [
                        'event'   => 'Purchase',
                        'params'  => ['value' => ($session->amount_total ?? 2900) / 100, 'currency' => 'EUR'],
                        'eventID' => $session->id,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe: verifica sessione al ritorno fallita', ['error' => $e->getMessage()]);
            }
        }

        return Inertia::render('AbbonamentoAttivo', [
            'attivo'     => ! $tenant?->fresh()?->isTrial(),
            'pronto'     => $this->readyModule($request),
            'bookingUrl' => config('services.onboarding.booking_url'),
            'track'      => $track,
        ]);
    }

    /**
     * Portale clienti Stripe: disdetta (a fine periodo), carta e fatture, senza dover scrivere a noi.
     * Usa una configurazione dedicata a Moduli, così non tocca quella degli altri prodotti Fusion.
     */
    public function portal(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant?->stripe_customer_id || ! config('services.stripe.secret')) {
            return back()->with('error', 'Nessun abbonamento da gestire.');
        }

        try {
            $session = $this->stripe()->billingPortal->sessions->create([
                'customer'      => $tenant->stripe_customer_id,
                'configuration' => $this->portalConfiguration(),
                'locale'        => 'it',
                'return_url'    => route('compiled.index'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe: portale clienti non disponibile', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Al momento non riusciamo ad aprire la gestione abbonamento. Scrivici e lo sistemiamo subito.');
        }

        return Inertia::location($session->url);
    }

    private function portalConfiguration(): string
    {
        return Cache::rememberForever('stripe_portal_configuration', function () {
            foreach ($this->stripe()->billingPortal->configurations->all(['active' => true, 'limit' => 100])->autoPagingIterator() as $configuration) {
                if (($configuration->metadata->app ?? null) === 'moduli') {
                    return $configuration->id;
                }
            }

            return $this->stripe()->billingPortal->configurations->create([
                'metadata'         => ['app' => 'moduli'],
                'business_profile' => [
                    'headline'           => 'Fusion Moduli: gestisci il tuo abbonamento',
                    'privacy_policy_url' => 'https://fusionsoft.it/privacy',
                ],
                'features' => [
                    'invoice_history'       => ['enabled' => true],
                    'payment_method_update' => ['enabled' => true],
                    'customer_update'       => ['enabled' => true, 'allowed_updates' => ['email', 'address', 'tax_id']],
                    'subscription_cancel'   => [
                        'enabled'             => true,
                        'mode'                => 'at_period_end',
                        'cancellation_reason' => [
                            'enabled' => true,
                            'options' => ['too_expensive', 'missing_features', 'unused', 'switched_service', 'other'],
                        ],
                    ],
                ],
            ])->id;
        });
    }

    public function webhook(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Firma non valida', 400);
        }

        $object = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
                // Sullo stesso account Stripe passano anche CRM e FusionWA: solo i checkout di Moduli.
                if (($object->metadata->app ?? null) !== 'moduli') {
                    break;
                }

                $tenant = Tenant::find((int) ($object->metadata->tenant_id ?? 0));
                if ($tenant && $object->mode === 'subscription') {
                    $this->activate($tenant, $object);
                }
                break;

            case 'customer.subscription.updated':
            case 'customer.subscription.deleted':
                $tenant = Tenant::where('stripe_subscription_id', $object->id)->first();
                $ended = $event->type === 'customer.subscription.deleted'
                    || in_array($object->status, ['canceled', 'unpaid', 'incomplete_expired'], true);

                if ($tenant && $ended) {
                    $tenant->update(['plan' => 'trial']);
                    Log::info('Stripe: abbonamento chiuso, azienda tornata in prova', ['tenant' => $tenant->id]);
                } elseif ($tenant && $object->status === 'active' && $tenant->isTrial()) {
                    $tenant->update(['plan' => 'pro']);
                }
                break;
        }

        return response('ok');
    }

    private function activate(Tenant $tenant, object $session): void
    {
        $tenant->update([
            'plan'                   => 'pro',
            'stripe_customer_id'     => $session->customer ?? $tenant->stripe_customer_id,
            'stripe_subscription_id' => $session->subscription ?? $tenant->stripe_subscription_id,
            'billing_details'        => $this->billingDetails($session) ?? $tenant->billing_details,
        ]);

        Log::info('Stripe: abbonamento attivato', ['tenant' => $tenant->id]);
    }

    /**
     * Dati per la fattura dal checkout: ragione sociale (o nome), P.IVA, sede, SDI/PEC, codice fiscale.
     */
    private function billingDetails(object $session): ?array
    {
        $customer = $session->customer_details ?? null;
        if (! $customer) {
            return null;
        }

        $fields = [];
        foreach ($session->custom_fields ?? [] as $field) {
            $fields[$field->key] = trim((string) ($field->text->value ?? '')) ?: null;
        }

        $address = $customer->address ?? null;

        return [
            'ragione_sociale' => $customer->name ?? null,
            'email'           => $customer->email ?? null,
            'partita_iva'     => ($customer->tax_ids ?? [])[0]->value ?? null,
            'codice_fiscale'  => $fields['codice_fiscale'] ?? null,
            'sdi_o_pec'       => $fields['sdi'] ?? null,
            'indirizzo'       => $address ? trim(($address->line1 ?? '') . ' ' . ($address->line2 ?? '')) : null,
            'cap'             => $address->postal_code ?? null,
            'citta'           => $address->city ?? null,
            'provincia'       => $address->state ?? null,
            'paese'           => $address->country ?? null,
        ];
    }

    private function readyModule(Request $request): ?CompiledModule
    {
        return $request->integer('pronto')
            ? CompiledModule::find($request->integer('pronto'), ['id', 'template_name', 'created_at'])
            : null;
    }

    private function stripe(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}
