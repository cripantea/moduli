<?php

use App\Models\CompiledModule;
use App\Models\ModuleTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('s3');
});

function templateFor(?Tenant $tenant, string $key): ModuleTemplate
{
    return ModuleTemplate::withoutGlobalScopes()->create([
        'tenant_id'           => $tenant?->id,
        'name'                => 'Template ' . ($tenant?->name ?? 'storico'),
        'pdf_template_s3_key' => $key,
        'fields_schema'       => [],
    ]);
}

// ── Isolamento tenant ────────────────────────────────────────────

test('a tenant user does not see other tenants data', function () {
    $user  = User::factory()->create();
    $other = Tenant::create(['name' => 'Altro']);
    $foreign = templateFor($other, 'tenants/' . $other->id . '/templates/x.pdf');

    $this->actingAs($user)->get("/templates/{$foreign->id}/edit")->assertNotFound();
});

test('a user without tenant sees nothing and is logged out', function () {
    $user = User::factory()->create(['tenant_id' => null]);
    templateFor(null, 'templates/legacy.pdf');

    $this->actingAs($user)->get('/templates')->assertRedirect(route('login', absolute: false));
    $this->assertGuest();

    $this->actingAs($user);
    expect(ModuleTemplate::count())->toBe(0);
});

test('superadmin still sees legacy templates without tenant', function () {
    $admin = User::factory()->superadmin()->create();
    $legacy = templateFor(null, 'templates/legacy.pdf');

    $this->actingAs($admin)->get("/templates/{$legacy->id}/edit")->assertOk();
});

// ── Dati sensibili nel frontend ──────────────────────────────────

test('password hash and remember token never reach the frontend', function () {
    $user = User::factory()->create();

    expect($user->toArray())->not->toHaveKeys(['password', 'remember_token']);

    $html = $this->actingAs($user)->get('/profile')->getContent();
    expect($html)->not->toContain($user->password)
        ->and($html)->not->toContain($user->remember_token);
});

test('superadmin user list does not expose password hashes', function () {
    $admin = User::factory()->superadmin()->create();
    $victim = User::factory()->create();

    $html = $this->actingAs($admin)->get('/superadmin/users')->getContent();
    expect($html)->not->toContain($victim->password);
});

// ── Chiavi S3 ────────────────────────────────────────────────────

test('uploads are stored under the tenant prefix', function () {
    $user = User::factory()->create();

    $key = $this->actingAs($user)
        ->post('/templates/upload-pdf', ['pdf' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
        ->json('s3_key');

    expect($key)->toStartWith("tenants/{$user->tenant_id}/templates/");
});

test('preview and AI extraction reject keys of other tenants', function () {
    Http::fake();
    $user  = User::factory()->create();
    $other = Tenant::create(['name' => 'Altro']);
    $foreignKey = 'tenants/' . $other->id . '/templates/11111111-1111-1111-1111-111111111111.pdf';
    templateFor($other, $foreignKey);

    $this->actingAs($user)
        ->getJson('/templates/preview?s3_key=' . urlencode($foreignKey))
        ->assertUnprocessable();
    $this->actingAs($user)
        ->postJson('/templates/extract-fields', ['s3_key' => 'moduli/1/whatever.pdf'])
        ->assertUnprocessable();

    Http::assertNothingSent();
});

test('a template cannot point to another tenant pdf', function () {
    $user  = User::factory()->create();
    $other = Tenant::create(['name' => 'Altro']);

    $this->actingAs($user)
        ->post('/templates', ['name' => 'Furto', 'pdf_template_s3_key' => 'templates/legacy.pdf'])
        ->assertSessionHasErrors('pdf_template_s3_key');

    $this->actingAs($user)
        ->post('/templates', [
            'name' => 'Ok',
            'pdf_template_s3_key' => "tenants/{$user->tenant_id}/templates/22222222-2222-2222-2222-222222222222.pdf",
        ])
        ->assertSessionHasNoErrors();
});

test('existing template keys remain usable by whoever can see the template', function () {
    $admin = User::factory()->superadmin()->create();
    $legacy = templateFor(null, 'templates/legacy.pdf');

    $this->actingAs($admin)
        ->put("/templates/{$legacy->id}", ['name' => $legacy->name, 'pdf_template_s3_key' => 'templates/legacy.pdf'])
        ->assertSessionHasNoErrors();
});

// ── Stato attivo ─────────────────────────────────────────────────

test('disabled users cannot log in', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('users of a disabled tenant are logged out', function () {
    $user = User::factory()->create();
    $user->tenant->update(['is_active' => false]);

    $this->actingAs($user)->get('/compiled')->assertRedirect(route('login', absolute: false));
    $this->assertGuest();
});

// ── Superadmin ───────────────────────────────────────────────────

test('deleting a tenant removes its users, data and files', function () {
    $admin  = User::factory()->superadmin()->create();
    $member = User::factory()->create();
    $tenant = $member->tenant;
    $template = templateFor($tenant, "tenants/{$tenant->id}/templates/t.pdf");
    CompiledModule::withoutGlobalScopes()->create([
        'tenant_id' => $tenant->id, 'module_template_id' => $template->id,
        'template_name' => 'x', 'values' => [], 'original_filename' => 'c.pdf', 's3_key' => "tenants/{$tenant->id}/moduli/1/c.pdf",
    ]);
    Storage::disk('s3')->put("tenants/{$tenant->id}/templates/t.pdf", 'pdf');
    Storage::disk('s3')->put("tenants/{$tenant->id}/moduli/1/c.pdf", 'pdf');

    $this->actingAs($admin)->delete("/superadmin/tenants/{$tenant->id}")->assertRedirect();

    expect(User::find($member->id))->toBeNull()
        ->and(ModuleTemplate::withoutGlobalScopes()->count())->toBe(0)
        ->and(CompiledModule::withoutGlobalScopes()->count())->toBe(0);
    Storage::disk('s3')->assertMissing("tenants/{$tenant->id}/templates/t.pdf");
    Storage::disk('s3')->assertMissing("tenants/{$tenant->id}/moduli/1/c.pdf");
});

test('the last superadmin cannot be demoted', function () {
    $admin = User::factory()->superadmin()->create();
    $other = User::factory()->superadmin()->create();
    $tenant = Tenant::create(['name' => 'T']);

    // Self-demotion is refused.
    $this->actingAs($admin)->put("/superadmin/users/{$admin->id}", [
        'name' => 'A', 'email' => $admin->email, 'role' => 'user', 'tenant_id' => $tenant->id,
    ]);
    expect($admin->fresh()->role)->toBe('superadmin');

    // Demoting another superadmin works while one remains.
    $this->actingAs($admin)->put("/superadmin/users/{$other->id}", [
        'name' => 'B', 'email' => $other->email, 'role' => 'user', 'tenant_id' => $tenant->id,
    ]);
    expect($other->fresh()->role)->toBe('user');
});

test('the AI extraction endpoint is rate limited', function () {
    Http::fake(['*' => Http::response(['content' => [['type' => 'text', 'text' => '[]']]])]);
    config(['services.anthropic.key' => 'test']);
    $user = User::factory()->create();
    $key  = "tenants/{$user->tenant_id}/templates/33333333-3333-3333-3333-333333333333.pdf";
    Storage::disk('s3')->put($key, '%PDF-1.4');

    foreach (range(1, 5) as $i) {
        $this->actingAs($user)->postJson('/templates/extract-fields', ['s3_key' => $key]);
    }

    $this->actingAs($user)->postJson('/templates/extract-fields', ['s3_key' => $key])->assertStatus(429);
});
