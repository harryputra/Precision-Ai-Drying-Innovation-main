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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();

            // Relasi
            $table->foreignId('device_id')
                ->constrained('devices')
                ->cascadeOnDelete();
            $table->foreignId('batch_id')
                ->nullable()
                ->constrained('drying_batches')
                ->nullOnDelete();

            // Suhu & Kelembaban
            $table->decimal('temperature_inside', 5, 2)->nullable();  // °C dalam ruang pengering
            $table->decimal('temperature_outside', 5, 2)->nullable(); // °C luar
            $table->decimal('humidity_inside', 5, 2)->nullable();     // % RH dalam
            $table->decimal('humidity_outside', 5, 2)->nullable();    // % RH luar

            // Intensitas Cahaya & Radiasi
            $table->decimal('solar_irradiance', 7, 2)->nullable();    // W/m²
            $table->decimal('lux', 10, 2)->nullable();                 // lux

            // Kadar Air Gabah
            $table->decimal('grain_moisture', 5, 2)->nullable();      // %

            // Berat
            $table->decimal('grain_weight', 8, 2)->nullable();        // kg

            // Kecepatan & Arah Angin
            $table->decimal('wind_speed', 5, 2)->nullable();          // m/s
            $table->smallInteger('wind_direction')->nullable();        // derajat 0-359

            // Status pembacaan
            $table->boolean('is_valid')->default(true);
            $table->string('error_message')->nullable();

            $table->timestamp('recorded_at');
            $table->timestamps();

            // Index
            $table->index('device_id');
            $table->index('batch_id');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
