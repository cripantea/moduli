<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            $table->float('text_baseline_shift')->default(0)->after('font_size');
        });
    }

    public function down(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            $table->dropColumn('text_baseline_shift');
        });
    }
};
