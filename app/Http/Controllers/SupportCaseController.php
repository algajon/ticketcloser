<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\CaseEvent;
use App\Models\SupportCase;
use App\Models\Workspace;
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
        $case->load(['events' => fn($q) => $q->latest()]);
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

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'case_number' => 'TC-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'new',
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
}
