<?php

namespace App;

use App\FusionKit\Adapter;
use App\FusionKit\LinkError;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Fusion Kit: Moduli visto da Fusion ID e Fusion Control (clienti = organizzazioni/tenant). */
class FusionKitAdapter implements Adapter
{
    public function linkUser(array $claims): Authenticatable
    {
        $user = User::where('fusion_id_sub', $claims['sub'])->first();
        if (! $user) {
            if (! $claims['email_verified']) {
                throw new LinkError('Conferma prima la tua email (trovi il link nella posta), poi riprova.');
            }
            $user = User::where('email', $claims['email'])->first();
            if (! $user) {
                throw new LinkError("Non c'è un account Fusion Moduli per {$claims['email']}. Registrati prima da moduli.fusionsoft.it.");
            }
            if ($user->fusion_id_sub && $user->fusion_id_sub !== $claims['sub']) {
                throw new LinkError('Questo account è già collegato a un altro accesso: entra con quello o scrivi all\'assistenza.');
            }
            $user->forceFill(['fusion_id_sub' => $claims['sub']])->save();
        }
        if ($reason = $user->accessDeniedReason()) {
            throw new LinkError($reason);
        }
        if ($user->isSuperAdmin() && ! in_array('mfa', $claims['amr'], true)) {
            throw new LinkError('Per entrare come superadmin serve la verifica in due passaggi: attivala nella pagina di accesso.');
        }
        // Chi entra con Fusion ID ha l'email già verificata lì.
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return $user;
    }

    public function homeFor(Authenticatable $user): string
    {
        return $user->isSuperAdmin() ? route('superadmin.dashboard', absolute: false) : route('dashboard', absolute: false);
    }

    public function widgetUser(Authenticatable $user): ?array
    {
        return [
            'id' => (string) $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'tenantId' => $user->tenant_id ? (string) $user->tenant_id : null,
            'tenantName' => $user->tenant?->name,
        ];
    }

    public function checks(): array
    {
        return [
            'database' => fn () => DB::select('select 1 as ok')[0]->ok == 1,
        ];
    }

    public function tenants(): array
    {
        return Tenant::query()->withCount('users')->orderBy('id')->get()->map(fn (Tenant $t) => [
            'id' => (string) $t->id,
            'name' => $t->name,
            'status' => $t->is_active ? 'active' : 'suspended',
            'plan' => $t->plan,
            'users' => (int) $t->users_count,
            'createdAt' => optional($t->created_at)->toIso8601String() ?? now()->toIso8601String(),
            'lastActivityAt' => optional($t->updated_at)->toIso8601String(),
            'contactEmail' => User::where('tenant_id', $t->id)->orderByRaw("role = 'admin' desc")->orderBy('id')->value('email'),
        ])->all();
    }

    public function users(): array
    {
        return User::query()->with('tenant:id,name')->orderBy('id')->get()->map(fn (User $u) => [
            'id' => (string) $u->id,
            'email' => $u->email,
            'name' => $u->name,
            'role' => $u->is_active ? $u->role : 'disattivato',
            'tenantId' => $u->tenant_id ? (string) $u->tenant_id : null,
            'tenantName' => $u->tenant?->name,
            'fusionIdSub' => $u->fusion_id_sub,
            'createdAt' => optional($u->created_at)->toIso8601String() ?? now()->toIso8601String(),
        ])->all();
    }

    public function setTenantSuspended(string $tenantId, bool $suspended): void
    {
        $tenant = Tenant::findOrFail($tenantId);
        $tenant->forceFill(['is_active' => ! $suspended])->save();
        if ($suspended) {
            DB::table('sessions')->whereIn('user_id', User::where('tenant_id', $tenant->id)->pluck('id'))->delete();
        }
    }

    public function impersonationTarget(string $tenantId, ?string $userId): ?string
    {
        $query = User::where('tenant_id', $tenantId)->where('is_active', true);
        $user = $userId ? $query->whereKey($userId)->first() : (clone $query)->where('role', 'admin')->orderBy('id')->first() ?? $query->orderBy('id')->first();

        return $user ? (string) $user->id : null;
    }

    public function revokeSessions(string $email): int
    {
        $user = User::where('email', $email)->first();
        if (! $user) {
            return 0;
        }
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
