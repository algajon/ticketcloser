<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\SupportCase;
use Illuminate\Http\Request;

class TicketUiController extends Controller
{
    private function workspaceOrFail(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_if(!$workspace, 403, 'No workspace found for user.');
        return $workspace;
    }

    public function index(Request $request)
    {
        $workspace = $this->workspaceOrFail($request);

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $opsStage = trim((string) $request->query('ops_stage', ''));
        $assistant = trim((string) $request->query('assistant', ''));
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $q);
        $isPropertyManagement = $workspace->use_case === 'property_management';
        $opsStageOptions = SupportCase::opsStageOptionsFor($workspace);

        $assistants = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $assistantCaseCounts = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('assistant_config_id')
            ->selectRaw('assistant_config_id, COUNT(*) as aggregate')
            ->groupBy('assistant_config_id')
            ->pluck('aggregate', 'assistant_config_id');

        $totalCases = (int) SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->count();

        $cases = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->when($isPropertyManagement && $opsStage !== '', fn($query) => $query->where('ops_stage', $opsStage))
            ->when($assistant !== '', fn($query) => $query->where('assistant_config_id', $assistant))
            ->when($q !== '', function ($query) use ($escaped) {
                $query->where(function ($sub) use ($escaped) {
                    $sub->where('case_number', 'like', "%{$escaped}%")
                        ->orWhere('title', 'like', "%{$escaped}%")
                        ->orWhere('requester_phone', 'like', "%{$escaped}%")
                        ->orWhere('requester_email', 'like', "%{$escaped}%");
                });
            })
            ->with(['contact:id,workspace_id,name,property_code,unit'])
            ->when($isPropertyManagement, function ($query) {
                $query->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END")
                    ->orderByRaw("CASE ops_stage WHEN 'urgent_review' THEN 0 WHEN 'new_intake' THEN 1 WHEN 'dispatched' THEN 2 WHEN 'scheduled' THEN 3 WHEN 'waiting_on_resident' THEN 4 WHEN 'completed' THEN 5 ELSE 6 END")
                    ->orderByDesc('created_at');
            }, fn($query) => $query->latest())
            ->paginate(15)
            ->withQueryString();

        $queueSummary = $isPropertyManagement
            ? SupportCase::query()
                ->where('workspace_id', $workspace->id)
                ->selectRaw(
                    "COUNT(*) as total_open,
                     SUM(CASE WHEN priority IN ('critical', 'high') AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as urgent_count,
                     SUM(CASE WHEN ops_stage = 'dispatched' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as dispatched_count,
                     SUM(CASE WHEN ops_stage = 'scheduled' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as scheduled_count,
                     SUM(CASE WHEN ops_stage = 'waiting_on_resident' AND status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as waiting_on_resident_count"
                )
                ->first()
            : null;

        return view('tickets.index', compact(
            'workspace',
            'cases',
            'q',
            'status',
            'opsStage',
            'assistant',
            'assistants',
            'assistantCaseCounts',
            'totalCases',
            'isPropertyManagement',
            'opsStageOptions',
            'queueSummary'
        ));
    }

    public function show(Request $request, SupportCase $case)
    {
        $workspace = $this->workspaceOrFail($request);

        abort_if($case->workspace_id !== $workspace->id, 404);

        $case->load([
            'events' => fn($q) => $q->latest(),
            'comments' => fn($q) => $q->latest(),
            'suggestedEvents',
            'calendarEvents',
            'contact',
        ]);

        $isPropertyManagement = $workspace->use_case === 'property_management';
        $opsStageOptions = SupportCase::opsStageOptionsFor($workspace);
        $relatedResidentCases = $case->contact
            ? $case->contact->cases()
                ->where('support_cases.id', '!=', $case->id)
                ->latest()
                ->limit(3)
                ->get(['id', 'case_number', 'title', 'status', 'priority', 'created_at'])
            : collect();
        $vendorSuggestions = collect();

        if ($isPropertyManagement) {
            $propertyCode = $case->propertyDisplay();

            $candidateVendorCases = SupportCase::query()
                ->where('workspace_id', $workspace->id)
                ->where('id', '!=', $case->id)
                ->whereNotNull('vendor_name')
                ->with(['contact:id,workspace_id,property_code,unit'])
                ->when($case->category || $propertyCode, function ($query) use ($case, $propertyCode) {
                    $query->where(function ($scoped) use ($case, $propertyCode) {
                        if ($case->category) {
                            $scoped->where('category', $case->category);
                        }

                        if ($propertyCode) {
                            $scoped->orWhereHas('contact', fn($contactQuery) => $contactQuery->where('property_code', $propertyCode));
                        }
                    });
                })
                ->latest()
                ->limit(12)
                ->get();

            $vendorSuggestions = $candidateVendorCases
                ->groupBy(fn (SupportCase $suggestion) => strtolower(trim(($suggestion->vendor_name ?? '') . '|' . ($suggestion->vendor_phone ?? ''))))
                ->map(function ($group) use ($case, $propertyCode) {
                    /** @var \Illuminate\Support\Collection<int, \App\Models\SupportCase> $group */
                    $first = $group->first();
                    $sameCategoryCount = $case->category ? $group->where('category', $case->category)->count() : 0;
                    $samePropertyCount = $propertyCode
                        ? $group->filter(fn (SupportCase $item) => $item->contact?->property_code === $propertyCode)->count()
                        : 0;

                    return [
                        'vendor_name' => $first->vendor_name,
                        'vendor_phone' => $first->vendor_phone,
                        'ticket_count' => $group->count(),
                        'same_category_count' => $sameCategoryCount,
                        'same_property_count' => $samePropertyCount,
                        'latest_case' => $group->sortByDesc('created_at')->first(),
                    ];
                })
                ->sortByDesc(fn (array $item) => ($item['same_property_count'] * 10) + ($item['same_category_count'] * 5) + $item['ticket_count'])
                ->take(3)
                ->values();
        }

        return view('tickets.show', compact('workspace', 'case', 'isPropertyManagement', 'opsStageOptions', 'relatedResidentCases', 'vendorSuggestions'));
    }
}
