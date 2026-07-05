<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            $table->unsignedTinyInteger('font_size')->default(10)->after('fields_schema');
        });
    }

    public function down(): void
    {
        Schema::table('module_templates', function (Blueprint $table) {
            $table->dropColumn('font_size');
        });
    }
};
