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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();

            // Konteks
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('device_id')
                ->nullable()
                ->constrained('devices')
                ->nullOnDelete();

            // Level log
            $table->enum('level', [
                'debug',
                'info',
                'notice',
                'warning',
                'error',
                'critical',
                'alert',
                'emergency'
            ])->default('info');

            $table->string('channel')->default('app');       // e.g. "app", "device", "ai", "auth"
            $table->string('event');                          // nama event singkat
            $table->text('message');
            $table->json('context')->nullable();              // data tambahan

            // HTTP / request info
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();

            // Loggable polymorphic (opsional)
            $table->nullableMorphs('loggable');

            $table->timestamps();

            $table->index('level');
            $table->index('channel');
            $table->index('user_id');
            $table->index('device_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
