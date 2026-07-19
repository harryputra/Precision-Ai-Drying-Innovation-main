<?php

namespace App\Console\Commands;

use App\Models\ActuatorLog;
use App\Models\AiDecision;
use App\Models\SensorReading;
use App\Models\SystemLog;
use Illuminate\Console\Command;

class CleanOldData extends Command
{
    protected $signature = 'app:clean-old-data
                            {--dry-run : Tampilkan jumlah yang akan dihapus tanpa menghapus}';

    protected $description = 'Hapus data lama sesuai retention policy untuk menjaga performa DB';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 DRY RUN — tidak ada data yang dihapus' : '🗑️  Menjalankan cleanup data lama...');
        $this->newLine();

        $rules = [
            [
                'label'  => 'Sensor Readings  (> 3 bulan)',
                'query'  => SensorReading::where('recorded_at', '<', now()->subMonths(3)),
            ],
            [
                'label'  => 'System Logs      (> 1 bulan)',
                'query'  => SystemLog::where('created_at', '<', now()->subMonths(1)),
            ],
            [
                'label'  => 'AI Decisions     (> 6 bulan, sudah executed)',
                'query'  => AiDecision::where('decided_at', '<', now()->subMonths(6))
                                      ->where('execution_status', 'executed'),
            ],
            [
                'label'  => 'Actuator Logs    (> 3 bulan)',
                'query'  => ActuatorLog::where('executed_at', '<', now()->subMonths(3)),
            ],
        ];

        $total = 0;

        foreach ($rules as $rule) {
            $count = $rule['query']->count();
            $total += $count;

            if ($dryRun) {
                $this->line("  {$rule['label']}: <comment>{$count} row</comment>");
            } else {
                $rule['query']->delete();
                $this->line("  {$rule['label']}: <info>dihapus {$count} row</info>");
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->warn("Total yang akan dihapus: {$total} row");
            $this->line('Jalankan tanpa --dry-run untuk menghapus.');
        } else {
            $this->info("✅ Cleanup selesai. Total dihapus: {$total} row.");

            // Catat ke system_logs
            \App\Models\SystemLog::create([
                'level'   => 'info',
                'message' => "Data cleanup selesai: {$total} row dihapus.",
                'context' => ['total_deleted' => $total, 'run_at' => now()->toIso8601String()],
            ]);
        }

        return self::SUCCESS;
    }
}
