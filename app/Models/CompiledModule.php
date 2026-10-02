<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompiledModule extends Model
{
    protected $fillable = [
        'tenant_id',
        'module_template_id',
        'template_name',
        'values',
        's3_key',
        'original_filename',
    ];

    protected $casts = ['values' => 'array'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ModuleTemplate::class, 'module_template_id');
    }

    public function searchableText(): string
    {
        if (empty($this->values)) {
            return '';
        }
        return implode(' ', array_map('strval', array_values($this->values)));
    }
}
