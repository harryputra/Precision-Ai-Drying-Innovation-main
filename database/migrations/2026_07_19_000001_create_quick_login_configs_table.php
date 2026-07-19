<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi Quick-Login (singleton, id=1).
 * Saat enabled=false semua endpoint quick-login balas 404 (tak terlihat).
 * Token acak 128-bit menjadi bagian URL /q/{token}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quick_login_configs', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('token', 64)->nullable();
            $table->boolean('show_button_on_login')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_login_configs');
    }
};
