<?php

use App\FusionKit\KitToken;
use App\FusionKit\LinkError;
use App\FusionKitAdapter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['fusionkit.kit_secret' => 'segreto-di-prova', 'fusionkit.product' => 'moduli', 'fusionkit.app_url' => 'http://localhost']);
    $this->tenant = Tenant::create(['name' => 'Studio Kit', 'is_active' => true]);
    $this->user = User::factory()->create(['email' => 'mario@example.com', 'tenant_id' => $this->tenant->id, 'role' => 'admin', 'is_active' => true]);
});

it('protegge le API di Fusion Control con il segreto del Kit', function () {
    $this->getJson('/api/fusion/users')->assertStatus(401);
    $this->getJson('/api/fusion/tenants', ['Authorization' => 'Bearer segreto-di-prova'])
        ->assertOk()
        ->assertJsonPath('tenants.0.name', 'Studio Kit')
        ->assertJsonPath('tenants.0.contactEmail', 'mario@example.com');
});

it('collega Fusion ID per email verificata e rifiuta gli sconosciuti', function () {
    $adapter = new FusionKitAdapter();
    expect($adapter->linkUser(['sub' => 'z1', 'email' => 'mario@example.com', 'email_verified' => true, 'amr' => []])->id)->toBe($this->user->id);
    expect(fn () => $adapter->linkUser(['sub' => 'z2', 'email' => 'nessuno@example.com', 'email_verified' => true, 'amr' => []]))->toThrow(LinkError::class);
});

it('non fa entrare chi è in un\'organizzazione disattivata', function () {
    $this->tenant->update(['is_active' => false]);
    expect(fn () => (new FusionKitAdapter())->linkUser(['sub' => 'z1', 'email' => 'mario@example.com', 'email_verified' => true, 'amr' => []]))->toThrow(LinkError::class);
});

it('chiede la 2FA al superadmin', function () {
    User::factory()->superadmin()->create(['email' => 'boss@fusionsoft.it']);
    $adapter = new FusionKitAdapter();
    expect(fn () => $adapter->linkUser(['sub' => 'z9', 'email' => 'boss@fusionsoft.it', 'email_verified' => true, 'amr' => ['pwd']]))->toThrow(LinkError::class);
});

it('sospende e riattiva un cliente chiudendo le sue sessioni', function () {
    DB::table('sessions')->insert(['id' => 's1', 'user_id' => $this->user->id, 'payload' => '', 'last_activity' => time()]);
    $this->postJson("/api/fusion/tenants/{$this->tenant->id}/suspend", [], ['Authorization' => 'Bearer segreto-di-prova'])->assertOk();
    expect($this->tenant->fresh()->is_active)->toBeFalse();
    expect(DB::table('sessions')->count())->toBe(0);
    $this->postJson("/api/fusion/tenants/{$this->tenant->id}/resume", [], ['Authorization' => 'Bearer segreto-di-prova'])->assertOk();
    expect($this->tenant->fresh()->is_active)->toBeTrue();
});

it('entra come il cliente solo con un link valido', function () {
    $falso = KitToken::sign(['purpose' => 'impersonate', 'userId' => (string) $this->user->id], 60, 'altro-segreto');
    $this->get('/auth/fusion/impersonate?token='.urlencode($falso))->assertRedirect('/login');
    $this->assertGuest();
    $res = $this->postJson('/api/fusion/impersonate', ['tenantId' => (string) $this->tenant->id], ['Authorization' => 'Bearer segreto-di-prova'])->assertOk();
    parse_str(parse_url($res->json('url'), PHP_URL_QUERY), $q);
    $this->get('/auth/fusion/impersonate?token='.urlencode($q['token']))->assertRedirect();
    $this->assertAuthenticatedAs($this->user);
});
