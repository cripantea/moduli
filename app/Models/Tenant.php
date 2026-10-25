<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'plan', 'is_active', 'settings', 'stripe_customer_id', 'stripe_subscription_id', 'billing_details'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings'  => 'array',
            'billing_details' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function moduleTemplates(): HasMany
    {
        return $this->hasMany(ModuleTemplate::class);
    }

    public function compiledModules(): HasMany
    {
        return $this->hasMany(CompiledModule::class);
    }

    /**
     * In prova: può caricare e compilare, ma per scaricare i PDF deve attivare l'abbonamento.
     */
    public function isTrial(): bool
    {
        return $this->plan === 'trial';
    }

    public function planLabel(): string
    {
        return match ($this->plan) {
            'trial'      => 'Trial',
            'enterprise' => 'Enterprise',
            default      => 'Pro',
        };
    }
}
