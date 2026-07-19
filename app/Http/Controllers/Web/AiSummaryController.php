<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AiDecision;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiSummaryController extends Controller
{
    public function index(): View
    {
        $total     = AiDecision::count();
        $executed  = AiDecision::where('execution_status', 'executed')->count();
        $pending   = AiDecision::where('execution_status', 'pending')->count();
        $failed    = AiDecision::where('execution_status', 'failed')->count();
        $overridden = AiDecision::whereNotNull('override_reason')->count();

        $avgConfidence = AiDecision::whereNotNull('confidence_score')
            ->avg('confidence_score');

        $highConfidence = AiDecision::where('confidence_score', '>=', 0.8)->count();
        $lowConfidence  = AiDecision::where('confidence_score', '<', 0.5)->count();

        // Distribution by decision_type
        $byType = AiDecision::select('decision_type', DB::raw('count(*) as total'))
            ->groupBy('decision_type')
            ->orderByDesc('total')
            ->get();

        // Distribution by execution_status
        $byStatus = AiDecision::select('execution_status', DB::raw('count(*) as total'))
            ->groupBy('execution_status')
            ->orderByDesc('total')
            ->get();

        // Daily trend: last 14 days
        $dailyTrend = AiDecision::select(
                DB::raw("date(decided_at) as date"),
                DB::raw('count(*) as total'),
                DB::raw('avg(confidence_score) as avg_conf')
            )
            ->where('decided_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top 5 ai models
        $byModel = AiDecision::select('ai_model', DB::raw('count(*) as total'))
            ->whereNotNull('ai_model')
            ->groupBy('ai_model')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Confidence distribution buckets
        $confBuckets = [
            '0-20%'   => AiDecision::whereNotNull('confidence_score')->where('confidence_score', '<', 0.2)->count(),
            '20-40%'  => AiDecision::whereBetween('confidence_score', [0.2, 0.4])->count(),
            '40-60%'  => AiDecision::whereBetween('confidence_score', [0.4, 0.6])->count(),
            '60-80%'  => AiDecision::whereBetween('confidence_score', [0.6, 0.8])->count(),
            '80-100%' => AiDecision::where('confidence_score', '>=', 0.8)->count(),
        ];

        // Recent overrides
        $recentOverrides = AiDecision::with(['device', 'batch', 'overriddenBy'])
            ->whereNotNull('override_reason')
            ->latest('decided_at')
            ->limit(5)
            ->get();

        return view('ai.summary', compact(
            'total', 'executed', 'pending', 'failed', 'overridden',
            'avgConfidence', 'highConfidence', 'lowConfidence',
            'byType', 'byStatus', 'dailyTrend', 'byModel', 'confBuckets',
            'recentOverrides'
        ));
    }
}
