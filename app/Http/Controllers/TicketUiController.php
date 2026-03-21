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
                        ->orWhere('requester_phone', 'like', "%{$escaped}%")
                        ->orWhere('requester_email', 'like', "%{$escaped}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('tickets.index', compact('workspace', 'cases', 'q', 'status', 'assistant', 'assistants'));
    }

    public function show(Request $request, SupportCase $case)
    {
        $workspace = $this->workspaceOrFail($request);

        abort_if($case->workspace_id !== $workspace->id, 404);

        $case->load(['events' => fn($q) => $q->latest(), 'comments' => fn($q) => $q->latest(), 'suggestedEvents', 'calendarEvents']);

        return view('tickets.show', compact('workspace', 'case'));
    }
}
