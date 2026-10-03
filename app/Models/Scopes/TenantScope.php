<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // CLI e job non hanno un utente: devono filtrare esplicitamente per tenant.
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return;
        }

        // Fail-closed: un utente senza tenant non vede nulla.
        if (! $user->tenant_id) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
    }
}
