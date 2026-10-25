<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contatori anonimi del percorso landing → registrazione → pagamento, uno per giorno, passo e fonte
 * (meta = arrivato da una pubblicità Meta). Nessun dato personale. La fonte si ricorda anche sull'azienda,
 * così il pagamento che arriva dal webhook di Stripe si attribuisce alla fonte giusta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funnel_daily', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('step', 30);
            $table->string('source', 20);
            $table->unsignedInteger('total')->default(0);
            $table->unique(['day', 'step', 'source']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('source', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_daily');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
