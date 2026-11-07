<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fusion ID: login unico dei software Fusionsoft.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identità in Fusion ID (claim sub), collegata al primo accesso con Fusion ID.
            $table->string('fusion_id_sub')->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('fusion_id_sub'));
    }
};
