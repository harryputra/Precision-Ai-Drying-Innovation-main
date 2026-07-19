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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();

            // Kategorisasi
            $table->enum('category', [
                'drying_rules',
                'rice_varieties',
                'weather_patterns',
                'equipment_specs',
                'troubleshooting',
                'best_practices',
                'other'
            ])->default('drying_rules');

            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');                          // konten artikel/aturan
            $table->json('tags')->nullable();                 // tag pencarian
            $table->json('metadata')->nullable();             // data tambahan terstruktur

            // Relevansi AI
            $table->boolean('is_active')->default(true);
            $table->boolean('use_for_ai')->default(true);    // dipakai sebagai konteks AI
            $table->decimal('priority_weight', 4, 2)->default(1.00); // bobot relevansi

            // Versi & audit
            $table->integer('version')->default(1);
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
            $table->index('use_for_ai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
