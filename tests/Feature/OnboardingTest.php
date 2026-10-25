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
        'password' => 'password', 'password_confirmation' => 'password', 'phone' => '333 000 0000',
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

test('a trial company cannot save more templates than the trial allows', function () {
    $user = trialUser();
    foreach (range(1, config('moduli.limits.trial.templates')) as $i) {
        ModuleTemplate::withoutGlobalScopes()->create([
            'tenant_id' => $user->tenant_id, 'name' => "T{$i}",
            'pdf_template_s3_key' => "tenants/{$user->tenant_id}/templates/t{$i}.pdf", 'fields_schema' => [],
        ]);
    }

    $this->actingAs($user)->from('/benvenuto')->post('/templates', ['name' => 'Altro', 'fields_schema' => []])
        ->assertRedirect('/benvenuto')
        ->assertSessionHas('error');

    expect(ModuleTemplate::withoutGlobalScopes()->where('tenant_id', $user->tenant_id)->count())
        ->toBe(config('moduli.limits.trial.templates'));
});

test('registrations from the same address are throttled', function () {
    foreach (range(1, config('moduli.limits.registrations_per_ip_per_hour')) as $i) {
        $this->post('/register', [
            'company' => "Azienda {$i}", 'name' => 'Bot', 'email' => "bot{$i}@example.com",
            'password' => 'password', 'password_confirmation' => 'password', 'phone' => '333 000 0000',
        ]);
        auth()->logout();
    }

    $this->post('/register', [
        'company' => 'Azienda extra', 'name' => 'Bot', 'email' => 'extra@example.com',
        'password' => 'password', 'password_confirmation' => 'password', 'phone' => '333 000 0000',
    ])->assertSessionHasErrors('email');

    expect(\App\Models\User::where('email', 'extra@example.com')->exists())->toBeFalse();
});

test('a customer can open a support ticket that only the superadmin sees', function () {
    $user = trialUser();

    $this->actingAs($user)->from('/compiled')->post('/assistenza', [
        'subject' => 'Campo spostato', 'message' => 'Il nome finisce sopra la riga', 'page' => '/compiled',
    ])->assertRedirect('/compiled')->assertSessionHas('success');

    expect(\App\Models\SupportTicket::where('tenant_id', $user->tenant_id)->where('status', 'aperto')->count())->toBe(1);

    $this->actingAs($user)->get('/superadmin/ticket')->assertForbidden();

    $super = User::factory()->create(['role' => 'superadmin', 'tenant_id' => null, 'email_verified_at' => now()]);
    $this->actingAs($super)->get('/superadmin/ticket')
        ->assertInertia(fn ($page) => $page->component('Superadmin/Tickets')->where('aperti', 1));
});

test('the AI assistant answers and the superadmin can read the conversation', function () {
    config(['services.deepseek.key' => 'test-key']);
    \Illuminate\Support\Facades\Http::fake([
        'api.deepseek.com/*' => \Illuminate\Support\Facades\Http::response(['content' => [['type' => 'text', 'text' => 'Vai in **Template** e trascina la linea.']]]),
    ]);
    $user = trialUser();

    $first = $this->actingAs($user)->postJson('/assistente', ['message' => 'Il nome è fuori posto', 'page' => '/compiled'])
        ->assertOk()->assertJsonPath('reply', 'Vai in **Template** e trascina la linea.');

    $this->actingAs($user)->postJson('/assistente', ['chat_id' => $first->json('chat_id'), 'message' => 'Grazie'])->assertOk();

    $chat = \App\Models\AssistantChat::firstOrFail();
    expect($chat->messages()->count())->toBe(4);

    // Un altro cliente non può continuare la chat di qualcun altro.
    $this->actingAs(trialUser())->postJson('/assistente', ['chat_id' => $chat->id, 'message' => 'ciao'])->assertNotFound();

    $super = User::factory()->create(['role' => 'superadmin', 'tenant_id' => null, 'email_verified_at' => now()]);
    $this->actingAs($super)->get("/superadmin/chat/{$chat->id}")
        ->assertInertia(fn ($page) => $page->component('Superadmin/Chats')->has('chat.messages', 4));
});

test('without DeepSeek the assistant points to human support', function () {
    config(['services.deepseek.key' => null]);

    $this->actingAs(trialUser())->postJson('/assistente', ['message' => 'aiuto'])
        ->assertOk()->assertJsonPath('reply', fn ($r) => str_contains($r, 'Assistenza'));
});

test('the launch price ends at the deadline and checkout switches to the standard price', function () {
    config(['moduli.launch.ends_at' => '2026-10-29 23:59:59', 'services.stripe.price' => 'price_29', 'services.stripe.price_standard' => 'price_49']);

    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-29 23:00:00', 'Europe/Rome'));
    expect(\App\Services\LaunchOffer::active())->toBeTrue()
        ->and(\App\Services\LaunchOffer::price())->toBe(29)
        ->and(\App\Services\LaunchOffer::stripePrice())->toBe('price_29');

    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-30 00:00:01', 'Europe/Rome'));
    expect(\App\Services\LaunchOffer::active())->toBeFalse()
        ->and(\App\Services\LaunchOffer::price())->toBe(49)
        ->and(\App\Services\LaunchOffer::stripePrice())->toBe('price_49');

    $this->actingAs(trialUser())->get('/abbonamento')
        ->assertInertia(fn ($page) => $page->where('prezzo', 49)->where('offerta.attiva', false));
});

test('registrations and payments are sent to Fusion CRM for the follow-up emails', function () {
    config(['services.fusion_crm.webhook_url' => 'https://crm.test/api/hooks/x', 'services.fusion_crm.webhook_token' => 'tok']);
    \Illuminate\Support\Facades\Http::fake(['crm.test/*' => \Illuminate\Support\Facades\Http::response(['contactId' => 'c1'])]);

    $this->post('/register', [
        'company' => 'Studio Bianchi', 'name' => 'Giulia Bianchi', 'email' => 'giulia@example.com', 'phone' => '333 123 4567',
        'password' => 'password', 'password_confirmation' => 'password',
    ]);

    \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r->hasHeader('X-Fusion-Token', 'tok')
        && $r['email'] === 'giulia@example.com' && $r['firstName'] === 'Giulia' && $r['phone'] === '333 123 4567'
        && in_array('moduli-prova', $r['tags']) && $r['customFields']['moduli_azienda'] === 'Studio Bianchi');

    $tenant = \App\Models\User::where('email', 'giulia@example.com')->first()->tenant;
    stripeWebhook([
        'id' => 'evt_9', 'object' => 'event', 'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'object' => 'checkout.session', 'mode' => 'subscription', 'client_reference_id' => (string) $tenant->id,
            'metadata' => ['app' => 'moduli', 'tenant_id' => (string) $tenant->id], 'customer' => 'cus_g', 'subscription' => 'sub_g',
        ]],
    ])->assertOk();

    \Illuminate\Support\Facades\Http::assertSent(fn ($r) => $r['email'] === 'giulia@example.com' && in_array('moduli-cliente', $r['tags']));
});

test('the mobile number is required to register', function () {
    $this->post('/register', [
        'company' => 'Senza Tel Srl', 'name' => 'Anna', 'email' => 'anna@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('phone');
});
