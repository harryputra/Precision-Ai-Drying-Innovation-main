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
        Schema::create('ai_decisions', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('drying_batches')
                ->nullOnDelete();

            // Tipe keputusan AI
            $table->enum('decision_type', [
                'open_roof',
                'close_roof',
                'start_fan',
                'stop_fan',
                'start_heater',
                'stop_heater',
                'pause_drying',
                'resume_drying',
                'alert_operator',
                'adjust_temperature',
                'adjust_airflow',
                'other'
            ]);

            // Alasan & konteks
            $table->text('reasoning');                        // penjelasan keputusan AI
            $table->json('input_data')->nullable();           // snapshot sensor saat keputusan
            $table->json('output_action')->nullable();        // aksi yang diperintahkan
            $table->decimal('confidence_score', 4, 3)->nullable(); // 0.000 - 1.000

            // Model AI yang dipakai
            $table->string('ai_model')->nullable();           // e.g. "fuzzy-logic-v1", "ml-model-v2"

            // Status eksekusi
            $table->enum('execution_status', [
                'pending',
                'executed',
                'failed',
                'skipped',
                'overridden'
            ])->default('pending');

            $table->string('override_reason')->nullable();    // jika operator override
            $table->foreignId('overridden_by')->nullable()   // user yg override
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('decided_at');
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index('device_id');
            $table->index('batch_id');
            $table->index('decision_type');
            $table->index('execution_status');
            $table->index('decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_decisions');
    }
};
