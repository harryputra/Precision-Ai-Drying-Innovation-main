<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE knowledge_bases MODIFY COLUMN category ENUM(
                'drying_rules',
                'rice_varieties',
                'weather_patterns',
                'equipment_specs',
                'troubleshooting',
                'best_practices',
                'video_tutorial',
                'other'
            ) NOT NULL DEFAULT 'drying_rules'");
        } elseif ($driver === 'pgsql') {
            // PostgreSQL: drop constraint lama, buat yang baru dengan nilai tambahan
            DB::statement("ALTER TABLE knowledge_bases DROP CONSTRAINT IF EXISTS knowledge_bases_category_check");
            DB::statement("ALTER TABLE knowledge_bases ADD CONSTRAINT knowledge_bases_category_check
                CHECK (category IN (
                    'drying_rules',
                    'rice_varieties',
                    'weather_patterns',
                    'equipment_specs',
                    'troubleshooting',
                    'best_practices',
                    'video_tutorial',
                    'other'
                ))");
        }
        // SQLite: tidak ada constraint enum, tidak perlu alter
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::table('knowledge_bases')
                ->where('category', 'video_tutorial')
                ->update(['category' => 'other']);

            DB::statement("ALTER TABLE knowledge_bases MODIFY COLUMN category ENUM(
                'drying_rules',
                'rice_varieties',
                'weather_patterns',
                'equipment_specs',
                'troubleshooting',
                'best_practices',
                'other'
            ) NOT NULL DEFAULT 'drying_rules'");
        } elseif ($driver === 'pgsql') {
            DB::table('knowledge_bases')
                ->where('category', 'video_tutorial')
                ->update(['category' => 'other']);

            DB::statement("ALTER TABLE knowledge_bases DROP CONSTRAINT IF EXISTS knowledge_bases_category_check");
            DB::statement("ALTER TABLE knowledge_bases ADD CONSTRAINT knowledge_bases_category_check
                CHECK (category IN (
                    'drying_rules',
                    'rice_varieties',
                    'weather_patterns',
                    'equipment_specs',
                    'troubleshooting',
                    'best_practices',
                    'other'
                ))");
        }
    }
};
