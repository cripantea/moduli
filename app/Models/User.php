<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['superadmin', 'admin']);
    }

    /**
     * Motivo per cui l'utente non può usare l'app, oppure null se può.
     * Il superadmin non ha tenant; tutti gli altri devono averne uno attivo.
     */
    public function accessDeniedReason(): ?string
    {
        if (! $this->is_active) {
            return 'Il tuo account è stato disattivato.';
        }

        if ($this->isSuperAdmin()) {
            return null;
        }

        if (! $this->tenant_id || ! $this->tenant) {
            return 'Il tuo account non è associato ad alcuna organizzazione.';
        }

        if (! $this->tenant->is_active) {
            return 'La tua organizzazione è stata disattivata.';
        }

        return null;
    }
}
