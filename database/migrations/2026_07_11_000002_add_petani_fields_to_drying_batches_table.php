<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drying_batches', function (Blueprint $table) {
            // Mapping petani ke batch — supaya viewer lihat gabah miliknya saja
            // dan notifikasi dikirim tepat sasaran
            $table->string('petani_name')->nullable()->after('operator_name')
                ->comment('Nama petani pemilik gabah di batch ini');
            $table->string('petani_phone')->nullable()->after('petani_name')
                ->comment('No. HP petani — untuk notifikasi WhatsApp/SMS');
        });
    }

    public function down(): void
    {
        Schema::table('drying_batches', function (Blueprint $table) {
            $table->dropColumn(['petani_name', 'petani_phone']);
        });
    }
};
