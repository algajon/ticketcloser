<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceFeedback;
use Illuminate\Http\Request;

class WorkspaceFeedbackController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:0', 'max:5'],
            'feedback_text' => ['nullable', 'string', 'max:2000'],
            'assistant_config_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:64'],
            'context' => ['nullable', 'array'],
        ]);

        $assistantId = $validated['assistant_config_id'] ?? null;
        if ($assistantId) {
            $assistantExists = $workspace->assistantConfigs()->whereKey($assistantId)->exists();
            abort_unless($assistantExists, 404);
        }

        WorkspaceFeedback::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()?->id,
            'assistant_config_id' => $assistantId,
            'category' => $validated['category'] ?? 'assistant_setup',
            'rating' => (int) $validated['rating'],
            'feedback_text' => trim((string) ($validated['feedback_text'] ?? '')) ?: null,
            'context' => $validated['context'] ?? null,
        ]);

        $request->session()->forget('assistant_review_prompt');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Feedback saved. Thanks for helping improve the product.');
    }
}
