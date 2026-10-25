<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function funnelTotal(string $step, string $source): int
{
    return (int) DB::table('funnel_daily')->where('step', $step)->where('source', $source)->sum('total');
}

test('the funnel counts each step once per visit and remembers the Meta source', function () {
    $browser = ['User-Agent' => 'Mozilla/5.0 (iPhone)'];

    $this->withHeaders($browser)->get('/prova?fbclid=abc123')->assertOk();
    $this->withHeaders($browser)->get('/prova')->assertOk();
    $this->withHeaders($browser)->postJson('/funnel', ['step' => 'clic'])->assertNoContent();
    $this->withHeaders($browser)->postJson('/funnel', ['step' => 'clic'])->assertNoContent();
    $this->withHeaders($browser)->get('/register')->assertOk();
    $this->withHeaders($browser)->post('/register', [
        'name' => 'Mario Rossi', 'email' => 'mario@example.com', 'password' => 'password', 'phone' => '333 000 0000',
    ]);

    expect(funnelTotal('visita', 'meta'))->toBe(1)
        ->and(funnelTotal('clic', 'meta'))->toBe(1)
        ->and(funnelTotal('registrazione', 'meta'))->toBe(1)
        ->and(funnelTotal('registrato', 'meta'))->toBe(1)
        ->and(User::where('email', 'mario@example.com')->first()->tenant->source)->toBe('meta');
});

test('bots and unknown steps are not counted', function () {
    $this->withHeaders(['User-Agent' => 'facebookexternalhit/1.1'])->get('/')->assertOk();
    $this->postJson('/funnel', ['step' => 'pagato'])->assertUnprocessable();

    expect(DB::table('funnel_daily')->count())->toBe(0);
});

test('the superadmin sees the funnel, customers do not', function () {
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get('/');

    $customer = User::factory()->create();
    $this->actingAs($customer)->get('/superadmin/funnel')->assertForbidden();

    $super = User::factory()->create(['role' => 'superadmin', 'tenant_id' => null, 'email_verified_at' => now()]);
    $this->actingAs($super)->get('/superadmin/funnel')
        ->assertInertia(fn ($page) => $page->component('Superadmin/Funnel')->where('steps.0.total', 1));
});

test('registration goes to the Conversions API only with cookie consent', function () {
    config(['services.meta.pixel_id' => '123', 'services.meta.capi_token' => 'token']);
    Http::fake();

    $this->withUnencryptedCookie('cookie-consenso', 'si')->post('/register', [
        'name' => 'Anna Bianchi', 'email' => 'anna@example.com', 'password' => 'password', 'phone' => '333 111 2222',
    ]);
    $this->app->terminate();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/123/events')
        && $request['data'][0]['event_name'] === 'CompleteRegistration'
        && $request['data'][0]['user_data']['em'][0] === hash('sha256', 'anna@example.com')
        && $request['data'][0]['user_data']['ph'][0] === hash('sha256', '393331112222'));
});

test('without consent nothing is sent to Meta from the server', function () {
    config(['services.meta.pixel_id' => '123', 'services.meta.capi_token' => 'token']);
    Http::fake();

    $this->post('/register', [
        'name' => 'Luigi Verdi', 'email' => 'luigi@example.com', 'password' => 'password', 'phone' => '333 111 3333',
    ]);
    $this->app->terminate();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
});

test('the qualifying answers are saved on the company and the payment page offers the free days', function () {
    $this->post('/register', [
        'settore' => 'Agenzia immobiliare', 'volume' => 'Da 10 a 50',
        'name' => 'Sara Neri', 'email' => 'sara@example.com', 'password' => 'password', 'phone' => '333 222 1111',
    ]);

    $user = User::where('email', 'sara@example.com')->first();
    expect($user->tenant->settings)->toBe(['settore' => 'Agenzia immobiliare', 'volume' => 'Da 10 a 50']);

    $this->get('/abbonamento?benvenuto=1')
        ->assertInertia(fn ($page) => $page->component('Offerta')->where('volume', 'Da 10 a 50')->where('giorniGratis', 7));
});
