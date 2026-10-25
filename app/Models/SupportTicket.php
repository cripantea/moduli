<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Richiesta di assistenza di un cliente. Non usa TenantScope: la legge solo il superadmin.
 */
class SupportTicket extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'subject', 'message', 'page', 'status'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
