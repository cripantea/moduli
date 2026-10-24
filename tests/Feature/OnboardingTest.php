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
