<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleTemplate extends Model
{
    protected $fillable = ['name', 'pdf_template_s3_key', 'fields_schema', 'font_size'];

    protected $casts = ['fields_schema' => 'array'];

    public function compiledModules(): HasMany
    {
        return $this->hasMany(CompiledModule::class);
    }
}
