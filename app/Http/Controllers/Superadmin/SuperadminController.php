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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SuperadminController extends Controller
{
    public function dashboard(): Response
    {
        $recentTenants = Tenant::withCount(['users', 'moduleTemplates', 'compiledModules'])
            ->latest()
            ->take(8)
            ->get();

        return Inertia::render('Superadmin/Dashboard', [
            'stats' => [
                'tenants'   => Tenant::count(),
                'users'     => User::where('role', '!=', 'superadmin')->count(),
                'templates' => ModuleTemplate::withoutGlobalScope(TenantScope::class)->count(),
                'compiled'  => CompiledModule::withoutGlobalScope(TenantScope::class)->count(),
            ],
            'recentTenants' => $recentTenants,
        ]);
    }

    public function users(Request $request): Response
    {
        $users = User::with('tenant:id,name')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(
                    fn ($q) => $q
                        ->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('email', 'like', '%' . $request->search . '%')
                )
            )
            ->when($request->filled('role'),      fn ($q) => $q->where('role', $request->role))
            ->when($request->filled('tenant_id'), fn ($q) => $q->where('tenant_id', $request->integer('tenant_id')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Superadmin/Users', [
            'users'   => $users,
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'role', 'tenant_id']),
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
            'role'      => ['required', Rule::in(['superadmin', 'admin', 'user'])],
            'tenant_id' => ['required_unless:role,superadmin', 'nullable', 'exists:tenants,id'],
        ]);

        User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'email_verified_at' => now(),
            'password'          => Hash::make($data['password']),
            'role'              => $data['role'],
            'tenant_id'         => $data['role'] === 'superadmin' ? null : $data['tenant_id'],
            'is_active'         => true,
        ]);

        return redirect()->route('superadmin.users')
            ->with('success', "Utente {$data['name']} creato.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'  => ['nullable', 'string', 'min:8'],
            'role'      => ['required', Rule::in(['superadmin', 'admin', 'user'])],
            'tenant_id' => ['required_unless:role,superadmin', 'nullable', 'exists:tenants,id'],
        ]);

        if ($user->isSuperAdmin() && $data['role'] !== 'superadmin') {
            if ($user->is($request->user())) {
                return back()->with('error', 'Non puoi rimuovere il ruolo Superadmin a te stesso.');
            }
            if (User::where('role', 'superadmin')->count() <= 1) {
                return back()->with('error', "Deve restare almeno un Superadmin.");
            }
        }

        $update = [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'role'      => $data['role'],
            'tenant_id' => $data['role'] === 'superadmin' ? null : ($data['tenant_id'] ?? null),
        ];

        if (! empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $user->update($update);

        return redirect()->route('superadmin.users')
            ->with('success', "Utente {$user->name} aggiornato.");
    }

    public function toggleUser(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin()) {
            abort(403, 'Non è possibile disabilitare un Superadmin.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $stato = $user->is_active ? 'attivato' : 'disabilitato';

        return back()->with('success', "Utente {$user->name} {$stato}.");
    }
}
