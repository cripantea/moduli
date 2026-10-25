<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conversazioni con l'assistente AI dentro l'app; il superadmin le rilegge per capire dubbi e problemi dei clienti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistant_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('page', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistant_chat_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // user | assistant
            $table->text('content');
            $table->string('model', 60)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistant_messages');
        Schema::dropIfExists('assistant_chats');
    }
};
