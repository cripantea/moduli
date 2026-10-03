<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Prepara gli account esistenti al TenantScope fail-closed e alla verifica email obbligatoria.
 *
 * - L'account amministratore (ADMIN_EMAIL), creato dal vecchio AdminUserSeeder senza ruolo,
 *   diventa superadmin: continua a vedere i template e i moduli storici senza tenant.
 * - Gli account già esistenti vengono marcati come verificati, per non bloccarli.
 *
 * Template e moduli compilati non vengono toccati.
 */
return new class extends Migration
{
    public function up(): void
    {
        $adminEmail = config('app.admin_email');

        if ($adminEmail) {
            DB::table('users')
                ->where('email', $adminEmail)
                ->update(['role' => 'superadmin', 'tenant_id' => null]);
        }

        // Senza ADMIN_EMAIL: se non esiste alcun superadmin, promuove il primo account senza tenant.
        if (! DB::table('users')->where('role', 'superadmin')->exists()) {
            $firstOrphan = DB::table('users')->whereNull('tenant_id')->orderBy('id')->value('id');

            if ($firstOrphan) {
                DB::table('users')->where('id', $firstOrphan)->update(['role' => 'superadmin']);
            }
        }

        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Migrazione di soli dati: nessun ripristino automatico.
    }
};
