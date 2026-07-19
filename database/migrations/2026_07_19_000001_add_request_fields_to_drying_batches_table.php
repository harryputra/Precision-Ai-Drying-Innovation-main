<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drying_batches', function (Blueprint $table) {
            // ID user petani yang mengajukan request
            $table->foreignId('requested_by')
                ->nullable()
                ->after('petani_phone')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User ID petani yang mengajukan request pengeringan');

            // Status request: pending = menunggu approve, approved = disetujui, rejected = ditolak
            $table->enum('request_status', ['pending', 'approved', 'rejected'])
                ->nullable()
                ->after('requested_by')
                ->comment('null = dibuat langsung operator, pending/approved/rejected = via request petani');

            // Catatan tambahan dari petani saat request
            $table->text('request_notes')->nullable()->after('request_status');

            // Catatan operator saat approve/reject
            $table->text('operator_notes')->nullable()->after('request_notes');

            // Waktu request diajukan
            $table->timestamp('requested_at')->nullable()->after('operator_notes');

            $table->index('request_status');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('drying_batches', function (Blueprint $table) {
            $table->dropForeign(['requested_by']);
            $table->dropColumn([
                'requested_by', 'request_status', 'request_notes',
                'operator_notes', 'requested_at',
            ]);
        });
    }
};
