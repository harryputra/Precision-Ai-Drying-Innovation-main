<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * user_id harus nullable — n8n/AI simpan balasan tanpa user context,
     * dan AiConversationController::store() bisa dipanggil tanpa auth.
     */
    public function up(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable(false)
                ->change();
        });
    }
};
