<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'pdf_template_s3_key',
        'fields_schema',
        'font_size',
        'text_baseline_shift',
    ];

    protected $casts = ['fields_schema' => 'array'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function compiledModules(): HasMany
    {
        return $this->hasMany(CompiledModule::class);
    }
}
