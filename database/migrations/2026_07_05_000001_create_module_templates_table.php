<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('pdf_template_s3_key')->nullable();
            $table->json('fields_schema')->nullable();
            $table->unsignedTinyInteger('font_size')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_templates');
    }
};
