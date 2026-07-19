<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            // PID setpoint aktif saat sensor dikirim — dikirim dari ESP32
            $table->decimal('pid_setpoint', 5, 2)->nullable()->after('grain_weight')
                ->comment('Setpoint PID heater aktif saat sensor dikirim (°C)');

            // Output PID saat sensor dikirim — untuk monitoring dan tuning
            $table->decimal('pid_output', 6, 2)->nullable()->after('pid_setpoint')
                ->comment('Output PID controller saat sensor dikirim');

            // Flag apakah AI sedang aktif mengontrol setpoint
            $table->boolean('ai_active')->default(false)->after('pid_output')
                ->comment('true = setpoint dari AI, false = setpoint default ESP32');
        });
    }

    public function down(): void
    {
        Schema::table('sensor_readings', function (Blueprint $table) {
            $table->dropColumn(['pid_setpoint', 'pid_output', 'ai_active']);
        });
    }
};
