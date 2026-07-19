<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { background: #7c3aed; color: #fff; padding: 16px 20px; margin-bottom: 16px; }
    .header h1 { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
    .header p { font-size: 10px; opacity: 0.85; }
    .meta { margin-bottom: 12px; padding: 0 4px; font-size: 10px; color: #64748b; }
    table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
    thead tr { background: #7c3aed; color: #fff; }
    thead th { padding: 7px 8px; text-align: left; font-weight: bold; }
    tbody tr:nth-child(even) { background: #f5f3ff; }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 9px; font-weight: bold; }
    .badge-green  { background: #dcfce7; color: #166534; }
    .badge-yellow { background: #fef9c3; color: #854d0e; }
    .badge-red    { background: #fee2e2; color: #991b1b; }
    .badge-gray   { background: #f1f5f9; color: #475569; }
    .badge-purple { background: #ede9fe; color: #6d28d9; }
    .reasoning { font-size: 9px; color: #475569; max-width: 200px; }
    .footer { margin-top: 16px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px; }
</style>
</head>
<body>

<div class="header">
    <h1>Laporan Keputusan AI</h1>
    <p>SolarDryer AI — Dicetak: {{ now()->format('d M Y H:i') }}</p>
</div>

<div class="meta">
    Total: <strong>{{ $decisions->count() }}</strong> keputusan
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Waktu</th>
            <th>Device / Batch</th>
            <th>Tipe Keputusan</th>
            <th>Alasan</th>
            <th>Conf.</th>
            <th>Risk</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($decisions as $i => $dec)
        @php
            $statusBadge = [
                'pending'    => 'badge-yellow',
                'executed'   => 'badge-green',
                'failed'     => 'badge-red',
                'skipped'    => 'badge-gray',
                'overridden' => 'badge-purple',
            ][$dec->execution_status] ?? 'badge-gray';
            $riskBadge = [
                'low'      => 'badge-green',
                'medium'   => 'badge-yellow',
                'high'     => 'badge-red',
                'critical' => 'badge-red',
            ][$dec->output_action['risk_level'] ?? 'low'] ?? 'badge-gray';
        @endphp
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $dec->decided_at?->format('d M Y') }}<br><span style="color:#94a3b8;">{{ $dec->decided_at?->format('H:i') }}</span></td>
            <td>{{ $dec->device?->device_name ?? '-' }}<br><span style="color:#94a3b8;">{{ $dec->batch?->batch_code ?? '-' }}</span></td>
            <td><strong>{{ ucwords(str_replace('_', ' ', $dec->decision_type)) }}</strong></td>
            <td class="reasoning">{{ Str::limit($dec->reasoning, 80) }}</td>
            <td>{{ $dec->confidence_score ? number_format($dec->confidence_score * 100, 0).'%' : '-' }}</td>
            <td><span class="badge {{ $riskBadge }}">{{ ucfirst($dec->output_action['risk_level'] ?? '-') }}</span></td>
            <td><span class="badge {{ $statusBadge }}">{{ ucfirst($dec->execution_status) }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    SolarDryer AI &copy; {{ now()->year }} — Laporan digenerate otomatis
</div>

</body>
</html>
