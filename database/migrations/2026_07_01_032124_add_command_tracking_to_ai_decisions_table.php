<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_decisions', function (Blueprint $table) {
            // Kapan perintah dikirim ke ESP32
            $table->timestamp('command_sent_at')->nullable()->after('executed_at');
            // Kapan ESP32 konfirmasi sudah eksekusi
            $table->timestamp('acknowledged_at')->nullable()->after('command_sent_at');
            // Perintah aktuator spesifik untuk ESP32
            $table->json('esp32_command')->nullable()->after('acknowledged_at');
            // Status ACK dari ESP32
            $table->enum('ack_status', ['waiting', 'acked', 'timeout', 'failed'])
                ->nullable()
                ->after('esp32_command');
        });
    }

    public function down(): void
    {
        Schema::table('ai_decisions', function (Blueprint $table) {
            $table->dropColumn(['command_sent_at', 'acknowledged_at', 'esp32_command', 'ack_status']);
        });
    }
};
