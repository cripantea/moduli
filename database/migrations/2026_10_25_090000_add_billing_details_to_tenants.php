<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dati di fatturazione raccolti al pagamento (ragione sociale, P.IVA, sede, SDI/PEC).
 * Colonna nuova e vuota: le aziende esistenti restano come sono.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('billing_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('billing_details');
        });
    }
};
