<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkspaceHelperService
{
    public function reply(
        Workspace $workspace,
        User $user,
        string $message,
        array $history,
        array $pageContext,
        array $actionCatalog
    ): array {
        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $model = config('services.openai.model', 'gpt-4o-mini');

        if ($apiKey) {
            try {
                return $this->callLlm($apiKey, $baseUrl, $model, $workspace, $user, $message, $history, $pageContext, $actionCatalog);
            } catch (\Throwable $e) {
                Log::warning('WorkspaceHelperService: LLM call failed, using fallback', [
                    'workspace_id' => $workspace->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->fallback($message, $pageContext, $actionCatalog);
    }

    protected function callLlm(
        string $apiKey,
        string $baseUrl,
        string $model,
        Workspace $workspace,
        User $user,
        string $message,
        array $history,
        array $pageContext,
        array $actionCatalog
    ): array {
        $workspaceSummary = [
            'workspace' => $workspace->name,
            'plan' => $workspace->planLabel(),
            'page_title' => $pageContext['title'] ?? 'Workspace',
            'page_description' => $pageContext['description'] ?? '',
            'page_path' => $pageContext['path'] ?? '',
        ];

        $catalogSummary = collect($actionCatalog)
            ->map(fn (array $action, string $key) => "{$key}: {$action['label']}")
            ->values()
            ->implode('; ');

        $recentHistory = collect($history)
            ->take(-6)
            ->map(function (array $entry) {
                $role = $entry['role'] ?? 'user';
                $content = trim((string) ($entry['content'] ?? ''));
                return strtoupper($role) . ': ' . $content;
            })
            ->implode("\n");

        $prompt = <<<PROMPT
You are tickIt's in-app workspace assistant. Help the user navigate the product, understand what is happening in the workspace, and choose the next best action. Be concise, practical, and product-aware.

Current context:
{$this->jsonEncode($workspaceSummary)}

Available action keys:
{$catalogSummary}

Recent conversation:
{$recentHistory}

User message:
{$message}

Return valid JSON with this exact shape:
{
  "reply": "short helpful answer",
  "actionKeys": ["key1", "key2"]
}

Rules:
- Keep the reply under 120 words.
- Recommend at most 3 action keys.
- Only use keys from the catalog.
- Prefer concrete next steps inside the app.
- Do not invent unsupported actions.
PROMPT;

        $response = Http::withToken($apiKey)
            ->baseUrl($baseUrl)
            ->timeout(25)
            ->post('/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a precise JSON API that returns only valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => 350,
                'temperature' => 0.5,
            ])
            ->throw();

        $content = (string) $response->json('choices.0.message.content', '');
        $decoded = json_decode($content, true);

        if (!is_array($decoded) || !isset($decoded['reply'])) {
            throw new \RuntimeException('Helper response was not valid JSON.');
        }

        $actionKeys = collect($decoded['actionKeys'] ?? [])
            ->filter(fn ($key) => is_string($key) && array_key_exists($key, $actionCatalog))
            ->values()
            ->all();

        return [
            'reply' => trim((string) $decoded['reply']),
            'actionKeys' => $actionKeys,
        ];
    }

    protected function fallback(string $message, array $pageContext, array $actionCatalog): array
    {
        $normalized = strtolower($message);

        $matches = match (true) {
            str_contains($normalized, 'assistant') || str_contains($normalized, 'prompt') => ['assistants', 'new_assistant'],
            str_contains($normalized, 'recording') || str_contains($normalized, 'transcript') || str_contains($normalized, 'call') => ['calls', 'calls_analytics'],
            str_contains($normalized, 'ticket') || str_contains($normalized, 'case') => ['cases'],
            str_contains($normalized, 'phone') || str_contains($normalized, 'number') => ['phone_numbers'],
            str_contains($normalized, 'meeting') || str_contains($normalized, 'calendar') => ['calendar'],
            str_contains($normalized, 'billing') || str_contains($normalized, 'plan') => ['billing'],
            str_contains($normalized, 'setting') || str_contains($normalized, 'workspace') => ['settings', 'workspaces'],
            default => ['dashboard'],
        };

        $available = array_values(array_filter($matches, fn ($key) => array_key_exists($key, $actionCatalog)));
        $pageTitle = $pageContext['title'] ?? 'this page';

        return [
            'reply' => "I can help from {$pageTitle}. The best next step is one of the actions below.",
            'actionKeys' => array_slice($available, 0, 3),
        ];
    }

    protected function jsonEncode(array $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
