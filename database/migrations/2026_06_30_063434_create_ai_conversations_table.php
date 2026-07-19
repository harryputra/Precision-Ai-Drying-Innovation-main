<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('drying_batches')
                ->nullOnDelete();

            // Sesi percakapan
            $table->string('session_id');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('message');
            $table->json('context_data')->nullable();

            // Model AI
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->nullable();

            // Feedback
            $table->boolean('is_helpful')->nullable();
            $table->text('feedback_note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
