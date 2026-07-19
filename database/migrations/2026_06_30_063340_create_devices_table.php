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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->string('device_name');
            $table->string('serial_number')->unique();

            $table->string('firmware_version')->nullable();
            $table->string('ip_address')->nullable();

            $table->string('location')->nullable();

            $table->enum('status', [
                'online',
                'offline',
                'maintenance'
            ])->default('offline');

            $table->timestamp('last_seen')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};