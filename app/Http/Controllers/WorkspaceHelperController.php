<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceHelperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class WorkspaceHelperController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function __construct(protected WorkspaceHelperService $helper)
    {
    }

    public function chat(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $rateKey = 'workspace-helper:' . $workspace->id . ':' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($rateKey, 20)) {
            return response()->json([
                'error' => 'Too many requests. Please wait a moment before asking again.',
            ], 429);
        }

        RateLimiter::hit($rateKey, 60);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:2000'],
            'page.title' => ['nullable', 'string', 'max:160'],
            'page.description' => ['nullable', 'string', 'max:500'],
            'page.path' => ['nullable', 'string', 'max:255'],
        ]);

        $actionCatalog = [
            'dashboard' => ['label' => 'Open dashboard', 'href' => route('app.dashboard')],
            'cases' => ['label' => 'Review cases', 'href' => route('app.tickets.index')],
            'assistants' => ['label' => 'Open assistants', 'href' => route('app.assistant.edit', $workspace)],
            'new_assistant' => $workspace->canCreateAssistants()
                ? ['label' => 'Create assistant', 'href' => route('app.assistant.create', $workspace)]
                : ['label' => 'View plans', 'href' => route('app.billing.plans')],
            'phone_numbers' => ['label' => 'Manage phone numbers', 'href' => route('app.phone_numbers.index', $workspace)],
            'calls' => ['label' => 'Open calls', 'href' => route('app.calls.index', $workspace)],
            'calls_analytics' => ['label' => 'View call analytics', 'href' => route('app.calls.analytics', $workspace)],
            'calendar' => ['label' => 'Open calendar', 'href' => route('app.calendar.index')],
            'billing' => ['label' => 'Open billing', 'href' => route('app.billing.index')],
            'settings' => ['label' => 'Open settings', 'href' => route('app.settings')],
            'workspaces' => ['label' => 'Switch workspaces', 'href' => route('app.workspaces.index')],
            'prompt_writer' => ['label' => 'Open prompt writer', 'href' => route('app.prompt-writer.index')],
        ];

        $result = $this->helper->reply(
            $workspace,
            $request->user(),
            $validated['message'],
            $validated['history'] ?? [],
            $validated['page'] ?? [],
            $actionCatalog
        );

        $actions = collect($result['actionKeys'] ?? [])
            ->map(fn ($key) => ['key' => $key] + $actionCatalog[$key])
            ->values()
            ->all();

        return response()->json([
            'reply' => $result['reply'],
            'actions' => $actions,
        ]);
    }
}
