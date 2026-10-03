<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CompiledModule;
use App\Models\ModuleTemplate;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = Tenant::withCount(['users', 'moduleTemplates', 'compiledModules'])
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('name', 'like', '%' . $request->search . '%')
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Superadmin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255', 'unique:tenants,name'],
            'plan'           => ['required', Rule::in(['trial', 'pro', 'enterprise'])],
            // Optional first admin user
            'admin_name'     => ['nullable', 'string', 'max:255'],
            'admin_email'    => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['nullable', 'string', 'min:8'],
        ]);

        $tenant = Tenant::create([
            'name'      => $data['name'],
            'plan'      => $data['plan'],
            'is_active' => true,
        ]);

        if (! empty($data['admin_email'])) {
            User::create([
                'name'              => $data['admin_name'] ?? $data['admin_email'],
                'email'             => $data['admin_email'],
                'email_verified_at' => now(),
                'password'          => Hash::make($data['admin_password'] ?? str()->random(16)),
                'role'              => 'admin',
                'tenant_id'         => $tenant->id,
                'is_active'         => true,
            ]);
        }

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" creato.");
    }

    public function edit(Tenant $tenant): Response
    {
        $tenant->load([
            'users' => fn ($q) => $q->orderBy('name'),
        ]);

        $stats = [
            'templates' => ModuleTemplate::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)->count(),
            'compiled'  => CompiledModule::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $tenant->id)->count(),
        ];

        return Inertia::render('Superadmin/Tenants/Edit', [
            'tenant' => $tenant,
            'stats'  => $stats,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255', Rule::unique('tenants')->ignore($tenant->id)],
            'plan'      => ['required', Rule::in(['trial', 'pro', 'enterprise'])],
            'is_active' => ['boolean'],
        ]);

        $tenant->update($data);

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant \"{$tenant->name}\" aggiornato.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $name = $tenant->name;

        $templates = ModuleTemplate::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id);
        $compiled  = CompiledModule::withoutGlobalScope(TenantScope::class)->where('tenant_id', $tenant->id);

        $keys = $templates->clone()->whereNotNull('pdf_template_s3_key')->pluck('pdf_template_s3_key')
            ->merge($compiled->clone()->whereNotNull('s3_key')->pluck('s3_key'));

        // Le FK sono nullOnDelete: senza pulizia esplicita resterebbero dati orfani senza tenant.
        DB::transaction(function () use ($tenant, $templates, $compiled) {
            $compiled->delete();
            $templates->delete();
            $tenant->users()->delete();
            $tenant->delete();
        });

        // I file si eliminano dopo il commit, così un errore DB non lascia record senza file.
        foreach ($keys->unique()->chunk(500) as $chunk) {
            Storage::disk('s3')->delete($chunk->all());
        }

        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Tenant \"{$name}\" eliminato.");
    }
}
