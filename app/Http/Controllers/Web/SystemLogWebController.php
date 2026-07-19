<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = SystemLog::with(['user', 'device'])->latest();

        if ($request->filled('level'))   $query->where('level', $request->level);
        if ($request->filled('channel')) $query->where('channel', $request->channel);

        $logs = $query->paginate(50)->withQueryString();

        $levels   = ['debug','info','notice','warning','error','critical','alert','emergency'];
        $channels = SystemLog::select('channel')->distinct()->pluck('channel');

        return view('logs.index', compact('logs', 'levels', 'channels'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = SystemLog::with(['user', 'device'])->latest();

        if ($request->filled('level'))   $query->where('level', $request->level);
        if ($request->filled('channel')) $query->where('channel', $request->channel);

        $logs = $query->limit(5000)->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="system-logs-'.now()->format('Y-m-d-His').'.csv"',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Time','Level','Channel','Event','Message','User','Device','IP Address','URL','Method']);
            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    strtoupper($log->level),
                    $log->channel,
                    $log->event,
                    $log->message,
                    $log->user?->name,
                    $log->device?->device_name,
                    $log->ip_address,
                    $log->url,
                    $log->method,
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

}
