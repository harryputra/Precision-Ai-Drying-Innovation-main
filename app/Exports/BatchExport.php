<?php

namespace App\Exports;

use App\Models\DryingBatch;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BatchExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private Request $request) {}

    public function query()
    {
        $query = DryingBatch::with('device')->latest();

        if ($this->request->filled('status') && $this->request->status !== 'all') {
            $query->where('status', $this->request->status);
        }
        if ($this->request->filled('search')) {
            $query->where('batch_code', 'like', '%'.$this->request->search.'%');
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->date_to);
        }

        return $query;
    }

    public function title(): string
    {
        return 'Drying Batches';
    }

    public function headings(): array
    {
        return [
            'Kode Batch',
            'Device',
            'Jenis Padi',
            'Varietas',
            'Berat Awal (kg)',
            'Berat Akhir (kg)',
            'Kadar Air Awal (%)',
            'Kadar Air Saat Ini (%)',
            'Kadar Air Target (%)',
            'Metode Pengeringan',
            'Operator',
            'Status',
            'Waktu Mulai',
            'Waktu Selesai',
            'Durasi (menit)',
            'Dibuat',
        ];
    }

    public function map($batch): array
    {
        return [
            $batch->batch_code,
            $batch->device?->device_name ?? '-',
            $batch->rice_type,
            $batch->rice_variety ?? '-',
            $batch->initial_weight,
            $batch->current_weight ?? '-',
            $batch->initial_moisture,
            $batch->current_moisture ?? '-',
            $batch->target_moisture,
            $batch->drying_method ?? '-',
            $batch->operator_name ?? '-',
            ucfirst($batch->status),
            $batch->start_time?->format('d/m/Y H:i') ?? '-',
            $batch->end_time?->format('d/m/Y H:i') ?? '-',
            $batch->durationMinutes() ?? '-',
            $batch->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1D4ED8']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
