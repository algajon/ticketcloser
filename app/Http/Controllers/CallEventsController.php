<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\CallEvent;
use App\Models\SupportCase;
use App\Models\Workspace;
use App\Services\Vapi\VapiCallSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CallEventsController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        app(VapiCallSyncService::class)->hydrateMissingWorkspaceCalls($workspace, 12);

        $events = CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->latest()
            ->paginate(25, [
                'id',
                'workspace_id',
                'queue_id',
                'from_number',
                'to_number',
                'duration_seconds',
                'transcript',
                'recording_url',
                'created_at',
            ])
            ->withQueryString();

        return view('calls.index', compact('workspace', 'events'));
    }

    public function show(Request $request, Workspace $workspace, CallEvent $call)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($call->workspace_id !== $workspace->id, 404);

        if ($this->callNeedsHydration($call)) {
            $hydrated = app(VapiCallSyncService::class)->hydrateCallEvent($workspace, $call);
            if ($hydrated) {
                $call = $hydrated;
            }
        }

        $relatedCases = SupportCase::where('workspace_id', $workspace->id)
            ->where(function ($query) use ($call) {
                if (filled($call->vapi_call_id)) {
                    $query->orWhere('external_call_id', $call->vapi_call_id);
                }

                if (filled($call->from_number)) {
                    $query->orWhere('requester_phone', $call->from_number);
                }
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('calls.show', compact('workspace', 'call', 'relatedCases'));
    }

    public function analytics(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        app(VapiCallSyncService::class)->hydrateMissingWorkspaceCalls($workspace, 12);

        $rangeStart = now()->subDays(30);
        $calls = CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $rangeStart);

        $callStats = (clone $calls)
            ->selectRaw(
                "COUNT(*) as total_calls,
                 COALESCE(SUM(duration_seconds), 0) as total_seconds,
                 COALESCE(AVG(duration_seconds), 0) as avg_duration,
                 COALESCE(SUM(cost), 0) as total_cost,
                 COALESCE(AVG(CASE WHEN cost IS NOT NULL THEN cost END), 0) as avg_cost,
                 SUM(CASE WHEN cost IS NOT NULL THEN 1 ELSE 0 END) as cost_count,
                 SUM(CASE WHEN recording_url IS NOT NULL AND recording_url <> '' THEN 1 ELSE 0 END) as recording_count,
                 SUM(CASE WHEN transcript IS NOT NULL AND transcript <> '' THEN 1 ELSE 0 END) as transcript_count"
            )
            ->first();

        $voiceCases = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->where('source', SupportCase::SOURCE_VOICE)
            ->where('created_at', '>=', $rangeStart);

        $voiceCasesCount = (clone $voiceCases)->count();

        $casesFromTrackedCalls = SupportCase::query()
            ->join('call_events', function ($join) {
                $join->on('support_cases.external_call_id', '=', 'call_events.vapi_call_id')
                    ->on('support_cases.workspace_id', '=', 'call_events.workspace_id');
            })
            ->where('support_cases.workspace_id', $workspace->id)
            ->where('support_cases.source', SupportCase::SOURCE_VOICE)
            ->where('support_cases.created_at', '>=', $rangeStart)
            ->where('call_events.created_at', '>=', $rangeStart)
            ->distinct()
            ->count('support_cases.external_call_id');

        $bookedMeetings = CalendarEvent::where('workspace_id', $workspace->id)
            ->where('created_at', '>=', $rangeStart)
            ->count();

        $callTrend = CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $caseTrend = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->where('source', SupportCase::SOURCE_VOICE)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->pluck('aggregate', 'day');

        $dailyTrend = collect(range(0, 6))
            ->map(function (int $offset) use ($callTrend, $caseTrend) {
                $day = Carbon::today()->subDays(6 - $offset);
                $key = $day->toDateString();

                return [
                    'label' => $day->format('M j'),
                    'calls' => (int) ($callTrend[$key] ?? 0),
                    'cases' => (int) ($caseTrend[$key] ?? 0),
                ];
            });

        $recentCalls = (clone $calls)
            ->latest()
            ->limit(5)
            ->get([
                'id',
                'from_number',
                'cost',
                'duration_seconds',
                'created_at',
            ]);

        $analytics = [
            'total_calls' => (int) ($callStats->total_calls ?? 0),
            'total_minutes' => round(((int) ($callStats->total_seconds ?? 0)) / 60, 1),
            'avg_duration' => (int) round($callStats->avg_duration ?? 0),
            'total_cost' => $callStats->cost_count > 0 ? (float) ($callStats->total_cost ?? 0) : null,
            'avg_cost' => $callStats->cost_count > 0 ? (float) ($callStats->avg_cost ?? 0) : null,
            'cost_count' => (int) ($callStats->cost_count ?? 0),
            'recording_rate' => (int) ($callStats->total_calls ?? 0) > 0 ? round((((int) ($callStats->recording_count ?? 0)) / (int) $callStats->total_calls) * 100, 1) : 0,
            'transcript_rate' => (int) ($callStats->total_calls ?? 0) > 0 ? round((((int) ($callStats->transcript_count ?? 0)) / (int) $callStats->total_calls) * 100, 1) : 0,
            'call_to_case_rate' => (int) ($callStats->total_calls ?? 0) > 0 ? round(($casesFromTrackedCalls / (int) $callStats->total_calls) * 100, 1) : 0,
            'voice_cases' => $voiceCasesCount,
            'booked_meetings' => $bookedMeetings,
            'daily_trend' => $dailyTrend,
            'recent_calls' => $recentCalls,
        ];

        return view('calls.analytics', compact('workspace', 'analytics'));
    }

    private function callNeedsHydration(CallEvent $call): bool
    {
        return filled($call->vapi_call_id)
            && (
                $call->duration_seconds === null
                || ! filled($call->transcript)
                || ! filled($call->recording_url)
            );
    }
}
