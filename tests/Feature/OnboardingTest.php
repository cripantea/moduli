<?php

use App\Models\CompiledModule;
use App\Models\ModuleTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

function trialUser(): User
{
    return User::factory()->create([
        'tenant_id' => Tenant::create(['name' => 'Prova Srl', 'plan' => 'trial'])->id,
    ]);
}

function compiledFor(User $user): CompiledModule
{
    $template = ModuleTemplate::withoutGlobalScopes()->create([
        'tenant_id'           => $user->tenant_id,
        'name'                => 'Delega',
        'pdf_template_s3_key' => 'tenants/' . $user->tenant_id . '/templates/delega.pdf',
        'fields_schema'       => [],
    ]);

    return CompiledModule::withoutGlobalScopes()->create([
        'tenant_id'          => $user->tenant_id,
        'module_template_id' => $template->id,
        'template_name'      => 'Delega',
        'values'             => [],
        's3_key'             => 'tenants/' . $user->tenant_id . '/compiled/delega.pdf',
        'original_filename'  => 'delega.pdf',
    ]);
}

test('a tenant with templates goes straight to the archive', function () {
    $user = User::factory()->create();
    compiledFor($user);

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('compiled.index', absolute: false));
});

test('saving the first template from onboarding opens the compile page on it', function () {
    $user = trialUser();
    $key = 'tenants/' . $user->tenant_id . '/templates/22222222-2222-2222-2222-222222222222.pdf';
    Storage::disk('s3')->put($key, '%PDF');

    $response = $this->actingAs($user)->post('/templates', [
        'name' => 'Primo modulo',
        'pdf_template_s3_key' => $key,
        'fields_schema' => [],
        'onboarding' => true,
    ]);

    $template = ModuleTemplate::first();
    $response->assertRedirect(route('compiled.create', ['template' => $template->id], absolute: false));
});

test('a trial tenant cannot download and is sent to the subscription page', function () {
    $user = trialUser();
    $compiled = compiledFor($user);

    $this->actingAs($user)->get("/compiled/{$compiled->id}/download")
        ->assertRedirect(route('abbonamento', ['pronto' => $compiled->id], absolute: false));

    $this->actingAs($user)->get('/abbonamento?pronto=' . $compiled->id)->assertOk();
});

test('a paying tenant does not see the subscription page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/abbonamento')->assertRedirect(route('compiled.index', absolute: false));
});

// ── Pagamento Stripe ─────────────────────────────────────────────

function stripeWebhook(array $event): \Illuminate\Testing\TestResponse
{
    config(['services.stripe.webhook_secret' => 'whsec_test']);
    $payload = json_encode($event);
    $ts = time();
    $sig = hash_hmac('sha256', "{$ts}.{$payload}", 'whsec_test');

    return test()->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => "t={$ts},v1={$sig}",
        'CONTENT_TYPE'          => 'application/json',
    ], $payload);
}

test('checkout is not offered while Stripe is not configured', function () {
    config(['services.stripe.secret' => null, 'services.stripe.price' => null]);
    $user = trialUser();

    $this->actingAs($user)->from('/abbonamento')->post('/abbonamento/checkout')
        ->assertRedirect('/abbonamento')
        ->assertSessionHas('error');
});

test('the webhook rejects calls without a valid Stripe signature', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $this->postJson('/stripe/webhook', ['type' => 'checkout.session.completed'])->assertStatus(400);
});

test('a completed checkout activates the company', function () {
    $user = trialUser();

    stripeWebhook([
        'id' => 'evt_1', 'object' => 'event', 'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'object' => 'checkout.session', 'mode' => 'subscription',
            'client_reference_id' => (string) $user->tenant_id,
            'metadata' => ['app' => 'moduli', 'tenant_id' => (string) $user->tenant_id],
            'customer' => 'cus_123', 'subscription' => 'sub_123',
        ]],
    ])->assertOk();

    $tenant = $user->tenant->fresh();
    expect($tenant->plan)->toBe('pro')
        ->and($tenant->stripe_subscription_id)->toBe('sub_123');
});

test('a cancelled subscription puts back in trial only the company it belongs to', function () {
    $paying = Tenant::create(['name' => 'Cliente Stripe', 'plan' => 'pro', 'stripe_subscription_id' => 'sub_999']);
    $manual = Tenant::create(['name' => 'Cliente storico', 'plan' => 'pro']);

    stripeWebhook([
        'id' => 'evt_2', 'object' => 'event', 'type' => 'customer.subscription.deleted',
        'data' => ['object' => ['object' => 'subscription', 'id' => 'sub_999', 'status' => 'canceled']],
    ])->assertOk();

    expect($paying->fresh()->plan)->toBe('trial')
        ->and($manual->fresh()->plan)->toBe('pro');
});

test('checkouts of other Fusion products on the same Stripe account are ignored', function () {
    $user = trialUser();

    stripeWebhook([
        'id' => 'evt_3', 'object' => 'event', 'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'object' => 'checkout.session', 'mode' => 'subscription',
            'client_reference_id' => (string) $user->tenant_id,
            'metadata' => ['tenant_id' => (string) $user->tenant_id],
            'customer' => 'cus_crm', 'subscription' => 'sub_crm',
        ]],
    ])->assertOk();

    expect($user->tenant->fresh()->plan)->toBe('trial');
});

test('a paying customer sees the subscription button, a trial company does not', function () {
    $paying = User::factory()->create([
        'tenant_id' => Tenant::create(['name' => 'Cliente', 'plan' => 'pro', 'stripe_customer_id' => 'cus_1'])->id,
    ]);

    $this->actingAs($paying)->get('/compiled')
        ->assertInertia(fn ($page) => $page->where('auth.billing', true));

    $this->actingAs(trialUser())->get('/abbonamento')
        ->assertInertia(fn ($page) => $page->where('auth.billing', false));
});

test('the subscription portal is not opened for companies without a Stripe customer', function () {
    config(['services.stripe.secret' => 'sk_test_x']);
    $manual = User::factory()->create([
        'tenant_id' => Tenant::create(['name' => 'Cliente storico', 'plan' => 'pro'])->id,
    ]);

    $this->actingAs($manual)->from('/compiled')->post('/abbonamento/gestisci')
        ->assertRedirect('/compiled')
        ->assertSessionHas('error');
});

test('registration is reported once to the Meta pixel', function () {
    $this->post('/register', [
        'company' => 'Nuova Srl', 'name' => 'Mario', 'email' => 'mario@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    $this->get('/benvenuto')
        ->assertInertia(fn ($page) => $page->where('track.0.event', 'CompleteRegistration'));

    $this->get('/benvenuto')
        ->assertInertia(fn ($page) => $page->where('track', []));
});

test('a completed checkout keeps the invoicing details', function () {
    $user = trialUser();

    stripeWebhook([
        'id' => 'evt_4', 'object' => 'event', 'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'object' => 'checkout.session', 'mode' => 'subscription',
            'client_reference_id' => (string) $user->tenant_id,
            'metadata' => ['app' => 'moduli', 'tenant_id' => (string) $user->tenant_id],
            'customer' => 'cus_9', 'subscription' => 'sub_9',
            'customer_details' => [
                'name' => 'Prova Srl', 'email' => 'amministrazione@prova.it',
                'address' => ['line1' => 'Via Roma 1', 'postal_code' => '20100', 'city' => 'Milano', 'state' => 'MI', 'country' => 'IT'],
                'tax_ids' => [['type' => 'eu_vat', 'value' => 'IT12345678901']],
            ],
            'custom_fields' => [
                ['key' => 'sdi', 'type' => 'text', 'text' => ['value' => 'M5UXCR1']],
                ['key' => 'codice_fiscale', 'type' => 'text', 'text' => ['value' => null]],
            ],
        ]],
    ])->assertOk();

    expect($user->tenant->fresh()->billing_details)
        ->toMatchArray(['ragione_sociale' => 'Prova Srl', 'partita_iva' => 'IT12345678901', 'sdi_o_pec' => 'M5UXCR1', 'citta' => 'Milano', 'codice_fiscale' => null]);
});
