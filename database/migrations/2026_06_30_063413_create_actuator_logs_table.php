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
        Schema::create('actuator_logs', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('drying_batches')
                ->nullOnDelete();
            $table->foreignId('ai_decision_id')
                ->nullable()
                ->constrained('ai_decisions')
                ->nullOnDelete();

            // Aktuator
            $table->enum('actuator_type', [
                'roof',
                'fan',
                'heater',
                'ventilation',
                'pump',
                'conveyor',
                'other'
            ]);
            $table->string('actuator_name')->nullable();      // e.g. "Fan Utara", "Atap Kiri"

            // Perintah & status
            $table->enum('command', ['on', 'off', 'open', 'close', 'adjust']);
            $table->decimal('set_value', 7, 2)->nullable();   // nilai target (misal: kecepatan fan %)
            $table->decimal('actual_value', 7, 2)->nullable();// nilai aktual terbaca
            $table->string('unit')->nullable();               // e.g. "%", "rpm", "°C"

            // Sumber perintah
            $table->enum('triggered_by', ['ai', 'manual', 'schedule', 'safety'])->default('ai');
            $table->foreignId('triggered_by_user')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Status eksekusi
            $table->enum('status', ['success', 'failed', 'timeout'])->default('success');
            $table->string('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();  // latensi eksekusi

            $table->timestamp('executed_at');
            $table->timestamps();

            $table->index('device_id');
            $table->index('batch_id');
            $table->index('actuator_type');
            $table->index('executed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actuator_logs');
    }
};
