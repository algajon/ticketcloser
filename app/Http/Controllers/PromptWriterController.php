<?php

namespace App\Http\Controllers;

use App\Models\PromptVersion;
use App\Models\Workspace;
use App\Services\PromptGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PromptWriterController extends Controller
{
    public function __construct(protected PromptGenerationService $generator)
    {
    }

    /**
     * Show the Prompt Writer UI.
     */
    public function index(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        $versions = $workspace
            ? PromptVersion::where('workspace_id', $workspace->id)
                ->orderByDesc('created_at')
                ->take(10)
                ->get()
            : collect();

        return view('prompt-writer.index', compact('workspace', 'versions'));
    }

    /**
     * Generate a new prompt.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'assistant_type' => 'required|in:maintenance,mortgage,support,leasing',
            'tone' => 'required|in:professional,friendly,strict',
            'strictness' => 'required|in:low,medium,high',
            'tools_enabled' => 'nullable|array',
            'tools_enabled.*' => 'string|max:64',
        ]);

        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 403, 'No active workspace.');

        // Rate limiting: max 10 per user per minute
        $key = 'prompt-generate:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'error' => "Too many requests. Try again in {$seconds} seconds."
            ], 429);
        }
        RateLimiter::hit($key, 60);

        $version = $this->generator->generate($validated, $workspace, $request->user()->id);

        Log::info('PromptWriter: prompt generated', [
            'workspace_id' => $workspace->id,
            'version_id' => $version->id,
        ]);

        return response()->json([
            'version_id' => $version->id,
            'markdown' => $version->output_markdown,
        ]);
    }

    /**
     * Save/name a version.
     */
    public function saveName(Request $request, PromptVersion $version)
    {
        abort_unless($version->workspace_id === $request->user()->currentWorkspace()?->id, 403);
        $request->validate(['name' => 'required|string|max:120']);
        $version->update(['name' => $request->name]);
        return response()->json(['ok' => true]);
    }

    /**
     * Delete a saved version.
     */
    public function destroy(Request $request, PromptVersion $version)
    {
        abort_unless($version->workspace_id === $request->user()->currentWorkspace()?->id, 403);
        $version->delete();
        return response()->json(['ok' => true]);
    }
}
