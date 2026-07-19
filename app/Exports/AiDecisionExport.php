<?php

namespace App\Exports;

use App\Models\AiDecision;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AiDecisionExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private Request $request) {}

    public function query()
    {
        $query = AiDecision::with(['device', 'batch'])->latest('decided_at');

        if ($this->request->filled('device_id'))        $query->where('device_id', $this->request->device_id);
        if ($this->request->filled('decision_type'))    $query->where('decision_type', $this->request->decision_type);
        if ($this->request->filled('execution_status')) $query->where('execution_status', $this->request->execution_status);

        return $query;
    }

    public function title(): string
    {
        return 'AI Decisions';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Device',
            'Kode Batch',
            'Tipe Keputusan',
            'Alasan',
            'Confidence (%)',
            'Risk Level',
            'Status Eksekusi',
            'Model AI',
            'Waktu Keputusan',
        ];
    }

    public function map($decision): array
    {
        return [
            $decision->id,
            $decision->device?->device_name ?? '-',
            $decision->batch?->batch_code ?? '-',
            ucwords(str_replace('_', ' ', $decision->decision_type)),
            $decision->reasoning,
            $decision->confidence_score ? number_format($decision->confidence_score * 100, 1) : '-',
            ucfirst($decision->output_action['risk_level'] ?? '-'),
            ucfirst($decision->execution_status),
            $decision->ai_model ?? '-',
            $decision->decided_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF7C3AED']],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
