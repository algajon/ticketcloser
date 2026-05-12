<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\CaseEvent;
use App\Models\SupportCase;
use App\Models\Workspace;
use App\Services\Tickets\TicketCreationService;
use Illuminate\Http\Request;

class SupportCaseController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $assistant = trim((string) $request->query('assistant', ''));
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $q);

        $assistants = AssistantConfig::where('workspace_id', $workspace->id)->get();

        $cases = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->when($status !== '', fn($query) => $query->where('status', $status))
            ->when($assistant !== '', fn($query) => $query->where('assistant_config_id', $assistant))
            ->when($q !== '', function ($query) use ($escaped) {
                $query->where(function ($sub) use ($escaped) {
                    $sub->where('case_number', 'like', "%{$escaped}%")
                        ->orWhere('title', 'like', "%{$escaped}%")
                        ->orWhere('requester_phone', 'like', "%{$escaped}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', compact('workspace', 'cases', 'q', 'status', 'assistant', 'assistants'));
    }

    public function show(Request $request, Workspace $workspace, SupportCase $case)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($case->workspace_id !== $workspace->id, 404);
        $case->load([
            'events' => fn($q) => $q->latest(),
            'suggestedEvents',
            'calendarEvents',
            'contact',
        ]);
        return view('tickets.show', compact('workspace', 'case'));
    }

    public function create(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        return view('tickets.create', compact('workspace'));
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,normal,high,critical',
        ]);

        $case = app(TicketCreationService::class)->createForWorkspace($workspace, [
            ...$data,
            'source' => 'manual',
        ]);

        return redirect()->route('app.cases.show', [$workspace->slug, $case]);
    }

    public function updateStatus(Request $request, Workspace $workspace, SupportCase $case)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($case->workspace_id !== $workspace->id, 404);

        $data = $request->validate([
            'status' => 'required|in:' . implode(',', SupportCase::STATUSES),
        ]);

        $oldStatus = $case->status;
        $case->update(['status' => $data['status']]);

        CaseEvent::create([
            'workspace_id' => $workspace->id,
            'support_case_id' => $case->id,
            'actor_user_id' => $request->user()->id,
            'type' => 'status_changed',
            'data' => ['from' => $oldStatus, 'to' => $data['status']],
        ]);

        return back()->with('success', 'Status updated.');
    }

    public function updateWorkflow(Request $request, Workspace $workspace, SupportCase $case)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($case->workspace_id !== $workspace->id, 404);

        $isPropertyManagement = $workspace->use_case === 'property_management';
        abort_unless($isPropertyManagement, 404);

        $data = $request->validate([
            'ops_stage' => 'nullable|in:' . implode(',', SupportCase::PROPERTY_MANAGEMENT_OPS_STAGES),
            'vendor_name' => 'nullable|string|max:120',
            'vendor_phone' => 'nullable|string|max:30',
            'preferred_visit_window' => 'nullable|string|max:160',
            'access_notes' => 'nullable|string|max:2000',
        ]);

        $changes = [];

        foreach (['ops_stage', 'vendor_name', 'vendor_phone', 'preferred_visit_window', 'access_notes'] as $field) {
            $incoming = trim((string) ($data[$field] ?? ''));
            $incoming = $incoming !== '' ? $incoming : null;

            if ($case->{$field} !== $incoming) {
                $changes[$field] = ['from' => $case->{$field}, 'to' => $incoming];
                $case->{$field} = $incoming;
            }
        }

        if (filled($case->ops_stage)) {
            $statusForStage = match ($case->ops_stage) {
                SupportCase::OPS_STAGE_DISPATCHED, SupportCase::OPS_STAGE_SCHEDULED => SupportCase::STATUS_IN_PROGRESS,
                SupportCase::OPS_STAGE_WAITING_ON_RESIDENT => SupportCase::STATUS_WAITING,
                SupportCase::OPS_STAGE_COMPLETED => SupportCase::STATUS_RESOLVED,
                default => $case->priority === SupportCase::PRIORITY_CRITICAL ? SupportCase::STATUS_TRIAGED : $case->status,
            };

            if ($statusForStage !== $case->status) {
                $changes['status'] = ['from' => $case->status, 'to' => $statusForStage];
                $case->status = $statusForStage;
            }
        }

        if ($changes !== []) {
            $case->save();

            CaseEvent::create([
                'workspace_id' => $workspace->id,
                'support_case_id' => $case->id,
                'actor_user_id' => $request->user()->id,
                'type' => 'workflow_updated',
                'data' => $changes,
            ]);
        }

        return back()->with('success', $changes !== [] ? 'Maintenance workflow updated.' : 'No workflow changes were needed.');
    }
}
