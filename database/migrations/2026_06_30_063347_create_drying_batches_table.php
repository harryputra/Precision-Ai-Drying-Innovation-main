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
        Schema::create('drying_batches', function (Blueprint $table) {

            $table->id();

            // Relasi ke perangkat
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();

            // Informasi Batch
            $table->string('batch_code')->unique();

            $table->string('rice_type');
            $table->string('rice_variety')->nullable();

            // Berat Gabah
            $table->decimal('initial_weight',8,2);
            $table->decimal('current_weight',8,2)->nullable();

            // Kadar Air
            $table->decimal('initial_moisture',5,2);
            $table->decimal('current_moisture',5,2)->nullable();
            $table->decimal('target_moisture',5,2);

            // Metode Pengeringan
            $table->string('drying_method')->default('Hybrid');

            // Operator
            $table->string('operator_name')->nullable();

            // Waktu
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();

            // Status Batch
            $table->enum('status',[
                'waiting',
                'drying',
                'paused',
                'completed',
                'failed'
            ])->default('waiting');

            $table->timestamps();

            // Index
            $table->index('device_id');
            $table->index('batch_code');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drying_batches');
    }
};