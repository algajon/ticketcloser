<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\CalendarEvent;
use App\Models\CalendarConnection;
use App\Models\CallEvent;
use App\Models\SupportCase;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiCallSyncService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();

        if (! $workspace) {
            return view('dashboard', [
                'workspace' => null,
                'dashboard' => null,
            ]);
        }

        app(VapiCallSyncService::class)->hydrateMissingWorkspaceCalls($workspace, 8);

        $today = now()->toDateString();
        $weekAgo = now()->subDays(7);

        $caseStats = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->selectRaw(
                "COUNT(*) as total_cases,
                 SUM(CASE WHEN status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as open_count,
                 SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as new_today,
                 SUM(CASE WHEN priority = 'critical' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as critical_count",
                [$today]
            )
            ->first();

        $assistantStats = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->selectRaw(
                "COUNT(*) as total_assistants,
                 SUM(CASE WHEN vapi_assistant_id IS NOT NULL AND vapi_assistant_id <> '' THEN 1 ELSE 0 END) as synced_assistants"
            )
            ->first();

        $phoneStats = WorkspacePhoneNumber::query()
            ->where('workspace_id', $workspace->id)
            ->selectRaw(
                "COUNT(*) as total_numbers,
                 SUM(CASE WHEN e164 IS NOT NULL AND e164 <> '' THEN 1 ELSE 0 END) as live_numbers"
            )
            ->first();

        $callStats = CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->selectRaw(
                'COUNT(*) as total_calls,
                 COALESCE(SUM(duration_seconds), 0) as total_seconds,
                 SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as calls_week',
                [$weekAgo]
            )
            ->first();

        $hasCalendarConnection = CalendarConnection::query()
            ->where('workspace_id', $workspace->id)
            ->where(function ($query) {
                $query
                    ->whereNotNull('tokens_encrypted')
                    ->orWhereNotNull('calendly_scheduling_link');
            })
            ->exists();

        $recentCases = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->latest()
            ->limit(4)
            ->get([
                'id',
                'case_number',
                'title',
                'category',
                'priority',
                'status',
                'requester_phone',
                'source',
                'created_at',
            ]);

        $recentCalls = CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->latest()
            ->limit(3)
            ->get([
                'id',
                'from_number',
                'duration_seconds',
                'transcript',
                'recording_url',
                'created_at',
            ]);

        $hasSyncedAssistant = (int) ($assistantStats->synced_assistants ?? 0) > 0;
        $livePhoneCount = (int) ($phoneStats->live_numbers ?? 0);
        $hasWorkflowSelection = filled($workspace->use_case) && ($workspace->use_case !== 'other' || filled($workspace->use_case_details));
        $hasRecentCall = (int) ($callStats->total_calls ?? 0) > 0;
        $hasRecentCase = (int) ($caseStats->total_cases ?? 0) > 0;
        $launchReady = $hasWorkflowSelection && $hasSyncedAssistant && $livePhoneCount > 0;
        $isPropertyManagement = $workspace->use_case === 'property_management';

        $maintenanceStats = null;
        $urgentQueue = collect();
        $nextVisit = null;

        if ($isPropertyManagement) {
            $maintenanceStats = SupportCase::query()
                ->where('workspace_id', $workspace->id)
                ->selectRaw(
                    "SUM(CASE WHEN priority IN ('critical', 'high') AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as urgent_review_count,
                     SUM(CASE WHEN ops_stage = 'dispatched' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as dispatched_count,
                     SUM(CASE WHEN ops_stage = 'scheduled' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as scheduled_count,
                     SUM(CASE WHEN ops_stage = 'waiting_on_resident' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as waiting_on_resident_count"
                )
                ->first();

            $urgentQueue = SupportCase::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotIn('status', SupportCase::closedStatuses())
                ->where(function ($query) {
                    $query->whereIn('priority', [SupportCase::PRIORITY_CRITICAL, SupportCase::PRIORITY_HIGH])
                        ->orWhere('ops_stage', SupportCase::OPS_STAGE_URGENT_REVIEW);
                })
                ->with(['contact:id,workspace_id,name,property_code,unit'])
                ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 ELSE 2 END")
                ->orderByDesc('created_at')
                ->limit(4)
                ->get([
                    'id',
                    'workspace_id',
                    'contact_id',
                    'case_number',
                    'title',
                    'priority',
                    'status',
                    'ops_stage',
                    'requester_phone',
                    'preferred_visit_window',
                    'vendor_name',
                    'structured_payload',
                    'created_at',
                ]);

            $nextVisit = CalendarEvent::query()
                ->where('workspace_id', $workspace->id)
                ->where('starts_at', '>=', now())
                ->with([
                    'supportCase:id,workspace_id,case_number,title',
                    'contact:id,workspace_id,name,property_code,unit',
                ])
                ->orderBy('starts_at')
                ->first();
        }

        $checks = [
            [
                'label' => 'Choose your workflow',
                'description' => 'Tell tickIt what kinds of calls you want to automate.',
                'done' => $hasWorkflowSelection,
                'href' => route('app.onboarding.company'),
                'action' => 'Review workflow',
            ],
            [
                'label' => 'Create your first assistant',
                'description' => 'Review the prefilled assistant and sync it live.',
                'done' => $hasSyncedAssistant,
                'href' => route('app.assistant.create', $workspace),
                'action' => 'Build assistant',
            ],
            [
                'label' => 'Connect your number',
                'description' => 'Provision a number so callers can reach the assistant.',
                'done' => $livePhoneCount > 0,
                'href' => route('app.phone_numbers.index', $workspace),
                'action' => 'Connect number',
            ],
            [
                'label' => 'Connect your calendar',
                'description' => 'Make follow-up booking available from the start.',
                'done' => $hasCalendarConnection,
                'href' => route('app.calendar.settings'),
                'action' => 'Connect calendar',
            ],
            [
                'label' => 'Make a test call',
                'description' => 'Run one call so you can confirm the transcript and workflow.',
                'done' => $hasRecentCall,
                'href' => route('app.phone_numbers.index', $workspace),
                'action' => 'See live number',
            ],
            [
                'label' => 'Review the first ticket',
                'description' => 'Check that the ticket looks right and the follow-up is clear.',
                'done' => $hasRecentCase,
                'href' => route('app.tickets.index'),
                'action' => 'Open tickets',
            ],
        ];

        $setupComplete = collect($checks)->every('done');
        $completeCount = collect($checks)->where('done', true)->count();
        $nextCheck = collect($checks)->first(fn (array $check) => ! $check['done']);
        $assistantCount = (int) ($assistantStats->total_assistants ?? 0);
        $callCountWeek = (int) ($callStats->calls_week ?? 0);

        $attentionItems = collect();

        if ($launchReady && ! $hasRecentCall) {
            $attentionItems->push([
                'title' => 'Your assistant is live',
                'copy' => 'The setup is ready. Make one test call now to confirm the full flow from call to ticket.',
                'tone' => 'success',
                'href' => route('app.phone_numbers.index', $workspace),
                'action' => 'See live number',
            ]);
        } elseif (! $setupComplete) {
            $attentionItems->push([
                'title' => 'Finish setup',
                'copy' => 'Complete the remaining readiness steps before relying on live traffic.',
                'tone' => 'warning',
                'href' => $nextCheck['href'] ?? route('app.onboarding.company'),
                'action' => $nextCheck['action'] ?? 'Continue setup',
            ]);
        }

        if ((int) ($caseStats->critical_count ?? 0) > 0) {
            $criticalCount = (int) $caseStats->critical_count;
            $attentionItems->push([
                'title' => $criticalCount.' critical '.\Illuminate\Support\Str::plural('ticket', $criticalCount).' open',
                'copy' => 'High-severity issues still need attention in the queue.',
                'tone' => 'danger',
                'href' => route('app.tickets.index', ['status' => 'new']),
                'action' => 'Open queue',
            ]);
        }

        if ($assistantCount > 0 && $livePhoneCount === 0) {
            $attentionItems->push([
                'title' => 'No live number assigned',
                'copy' => 'Assistants are configured, but callers still do not have a line to reach them.',
                'tone' => 'info',
                'href' => route('app.phone_numbers.index', $workspace),
                'action' => 'Add number',
            ]);
        }

        if ($recentCalls->isEmpty()) {
            $attentionItems->push([
                'title' => 'Run a test call',
                'copy' => 'Validate transcripts, ticket creation, and routing with one fresh call.',
                'tone' => 'primary',
                'href' => route('app.assistant.edit', $workspace),
                'action' => 'Open assistants',
            ]);
        }

        $dashboard = [
            'checks' => $checks,
            'setup_complete' => $setupComplete,
            'launch_ready' => $launchReady,
            'is_property_management' => $isPropertyManagement,
            'setup_percent' => count($checks) ? (int) round(($completeCount / count($checks)) * 100) : 0,
            'complete_count' => $completeCount,
            'open_count' => (int) ($caseStats->open_count ?? 0),
            'new_today' => (int) ($caseStats->new_today ?? 0),
            'assistant_count' => $assistantCount,
            'synced_assistant_count' => (int) ($assistantStats->synced_assistants ?? 0),
            'has_synced_assistant' => $hasSyncedAssistant,
            'live_phone_count' => $livePhoneCount,
            'has_calendar_connection' => $hasCalendarConnection,
            'has_recent_call' => $hasRecentCall,
            'has_recent_case' => $hasRecentCase,
            'use_case_label' => $workspace->useCaseLabel(),
            'call_count_week' => $callCountWeek,
            'minutes_used' => (int) ceil(((int) ($callStats->total_seconds ?? 0)) / 60),
            'recent_cases' => $recentCases,
            'recent_calls' => $recentCalls,
            'primary_attention' => $attentionItems->first(),
            'secondary_attention' => $attentionItems->slice(1, 2)->values(),
            'maintenance_stats' => $maintenanceStats,
            'urgent_queue' => $urgentQueue,
            'next_visit' => $nextVisit,
        ];

        return view('dashboard', compact('workspace', 'dashboard'));
    }
}
