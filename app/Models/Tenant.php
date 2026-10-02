<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    protected $fillable = ['name', 'plan', 'is_active', 'settings'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings'  => 'array',
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

    public function planLabel(): string
    {
        return match ($this->plan) {
            'trial'      => 'Trial',
            'enterprise' => 'Enterprise',
            default      => 'Pro',
        };
    }
}
