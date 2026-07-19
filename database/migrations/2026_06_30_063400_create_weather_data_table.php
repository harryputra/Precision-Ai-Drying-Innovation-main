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
        Schema::create('weather_data', function (Blueprint $table) {
            $table->id();

            // Relasi device (opsional — data bisa dari API eksternal)
            $table->foreignId('device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();

            // Sumber data
            $table->enum('source', ['sensor', 'api', 'manual'])->default('api');
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Data Cuaca
            $table->decimal('temperature', 5, 2)->nullable();         // °C
            $table->decimal('humidity', 5, 2)->nullable();            // % RH
            $table->decimal('solar_irradiance', 7, 2)->nullable();   // W/m²
            $table->decimal('wind_speed', 5, 2)->nullable();         // m/s
            $table->smallInteger('wind_direction')->nullable();       // derajat
            $table->decimal('rainfall', 6, 2)->nullable();           // mm
            $table->decimal('cloud_cover', 5, 2)->nullable();        // %
            $table->decimal('uv_index', 4, 2)->nullable();

            // Prakiraan vs aktual
            $table->boolean('is_forecast')->default(false);
            $table->timestamp('forecast_for')->nullable();

            $table->string('weather_condition')->nullable();          // e.g. "sunny", "cloudy"
            $table->string('weather_icon')->nullable();

            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index('device_id');
            $table->index('recorded_at');
            $table->index('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_data');
    }
};
