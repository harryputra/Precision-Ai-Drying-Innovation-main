<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { background: #1d4ed8; color: #fff; padding: 16px 20px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
    .header p { font-size: 10px; opacity: 0.85; }
    .meta { display: flex; gap: 20px; margin-bottom: 12px; padding: 0 4px; font-size: 10px; color: #64748b; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    thead tr { background: #1d4ed8; color: #fff; }
    thead th { padding: 7px 8px; text-align: left; font-weight: bold; }
    tbody tr:nth-child(even) { background: #f1f5f9; }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-green  { background: #dcfce7; color: #166534; }
    .badge-blue   { background: #dbeafe; color: #1e40af; }
    .badge-yellow { background: #fef9c3; color: #854d0e; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-gray   { background: #f1f5f9; color: #475569; }
    .footer { margin-top: 16px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>Laporan Drying Batches</h1>
    <p>SolarDryer AI — Dicetak: {{ now()->format('d M Y H:i') }}</p>
</div>

<div class="meta">
    <span>Total: <strong>{{ $batches->count() }}</strong> batch</span>
    @if(request('status') && request('status') !== 'all')
    <span>Filter status: <strong>{{ ucfirst(request('status')) }}</strong></span>
    @endif
    @if(request('date_from'))
    <span>Dari: <strong>{{ request('date_from') }}</strong></span>
    @endif
    @if(request('date_to'))
    <span>Sampai: <strong>{{ request('date_to') }}</strong></span>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Kode Batch</th>
            <th>Device</th>
            <th>Jenis Padi</th>
            <th>Kadar Air</th>
            <th>Metode</th>
            <th>Operator</th>
            <th>Status</th>
            <th>Mulai</th>
            <th>Durasi</th>
        </tr>
    </thead>
    <tbody>
        @foreach($batches as $i => $batch)
        @php
            $badgeMap = [
                'waiting'   => 'badge-gray',
                'drying'    => 'badge-blue',
                'paused'    => 'badge-yellow',
                'completed' => 'badge-green',
                'failed'    => 'badge-red',
            ];
            $badge = $badgeMap[$batch->status] ?? 'badge-gray';
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td><strong>{{ $batch->batch_code }}</strong><br><span style="color:#94a3b8;">{{ $batch->created_at->format('d M Y') }}</span></td>
            <td>{{ $batch->device?->device_name ?? '-' }}</td>
            <td>{{ $batch->rice_type }}<br>{{ $batch->rice_variety ?? '' }}</td>
            <td>{{ number_format($batch->initial_moisture, 1) }}% → {{ number_format($batch->target_moisture, 1) }}%<br>Saat ini: {{ number_format($batch->current_moisture ?? $batch->initial_moisture, 1) }}%</td>
            <td>{{ $batch->drying_method ?? '-' }}</td>
            <td>{{ $batch->operator_name ?? '-' }}</td>
            <td><span class="badge {{ $badge }}">{{ ucfirst($batch->status) }}</span></td>
            <td>{{ $batch->start_time?->format('d M H:i') ?? '-' }}</td>
            <td>{{ $batch->durationMinutes() ? $batch->durationMinutes().' mnt' : '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    SolarDryer AI &copy; {{ now()->year }} — Laporan digenerate otomatis
</div>

</body>
</html>
