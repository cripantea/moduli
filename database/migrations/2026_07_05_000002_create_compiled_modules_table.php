<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compiled_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('template_name');
            $table->json('values')->nullable();
            $table->string('s3_key')->nullable();
            $table->string('original_filename');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compiled_modules');
    }
};
