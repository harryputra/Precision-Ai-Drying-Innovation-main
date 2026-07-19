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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // Penerima
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            // Relasi konteks
            $table->foreignId('device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('drying_batches')
                ->nullOnDelete();

            // Konten
            $table->enum('type', [
                'info',
                'warning',
                'alert',
                'success',
                'error'
            ])->default('info');

            $table->enum('category', [
                'moisture_alert',
                'temperature_alert',
                'weather_alert',
                'device_offline',
                'batch_complete',
                'batch_failed',
                'ai_decision',
                'system',
                'other'
            ])->default('system');

            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();                 // payload tambahan

            // Channel pengiriman
            $table->boolean('via_app')->default(true);
            $table->boolean('via_email')->default(false);
            $table->boolean('via_sms')->default(false);
            $table->boolean('via_whatsapp')->default(false);

            // Status
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('device_id');
            $table->index('type');
            $table->index('read_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
