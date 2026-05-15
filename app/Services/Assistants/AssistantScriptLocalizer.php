<?php

namespace App\Services\Assistants;

use App\Support\RegionalPilotStackCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AssistantScriptLocalizer
{
    public function localizePrompt(?string $text, ?string $languageCode, array $context = []): string
    {
        return $this->localizeText($text, $languageCode, 'system prompt', $context);
    }

    public function localizeOpeningLine(?string $text, ?string $languageCode, array $context = []): string
    {
        return $this->localizeText($text, $languageCode, 'opening line', $context);
    }

    private function localizeText(?string $text, ?string $languageCode, string $contentType, array $context): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        $languageCode = RegionalPilotStackCatalog::normalizeLanguageCode($languageCode, 'en-US') ?: 'en-US';
        $apiKey = trim((string) config('services.openai.api_key', ''));

        if ($apiKey === '') {
            return $text;
        }

        $workspaceName = trim((string) ($context['workspace_name'] ?? ''));
        $assistantName = trim((string) ($context['assistant_name'] ?? ''));
        $cacheKey = 'assistant-script-localizer:v1:' . sha1(implode('|', [
            $contentType,
            $languageCode,
            $workspaceName,
            $assistantName,
            $text,
        ]));

        return Cache::remember($cacheKey, now()->addDays(30), function () use (
            $apiKey,
            $text,
            $languageCode,
            $contentType,
            $workspaceName,
            $assistantName
        ): string {
            try {
                return $this->translateText(
                    apiKey: $apiKey,
                    text: $text,
                    languageCode: $languageCode,
                    contentType: $contentType,
                    workspaceName: $workspaceName,
                    assistantName: $assistantName,
                );
            } catch (\Throwable $e) {
                Log::warning('AssistantScriptLocalizer: translation failed, using original text', [
                    'language_code' => $languageCode,
                    'content_type' => $contentType,
                    'workspace_name' => $workspaceName,
                    'assistant_name' => $assistantName,
                    'error' => $e->getMessage(),
                ]);

                return $text;
            }
        });
    }

    private function translateText(
        string $apiKey,
        string $text,
        string $languageCode,
        string $contentType,
        string $workspaceName,
        string $assistantName,
    ): string {
        $baseUrl = config('services.openai.base_url', 'https://api.openai.com/v1');
        $model = config('services.openai.model', 'gpt-4o-mini');
        $targetLanguage = RegionalPilotStackCatalog::languageLabel($languageCode, 'en-US') ?: $languageCode;
        $maxTokens = max(250, min(2200, strlen($text) * 3));

        $instructions = implode("\n", array_filter([
            'You localize voice assistant copy for business phone calls.',
            "Target language: {$targetLanguage} ({$languageCode}).",
            "Content type: {$contentType}.",
            $workspaceName !== '' ? "Workspace name: {$workspaceName}." : null,
            $assistantName !== '' ? "Assistant name: {$assistantName}." : null,
            'If the text is already in the target language, return it unchanged.',
            'Keep the same meaning, tone, structure, and level of detail.',
            'Preserve business names, person names, product names, ticket labels, tool names, case numbers, phone numbers, URLs, and placeholders exactly.',
            'Do not add commentary, quotes, or markdown fences.',
            'Return only the final localized text.',
            '',
            'Text to localize:',
            $text,
        ]));

        $response = Http::withToken($apiKey)
            ->baseUrl($baseUrl)
            ->timeout(30)
            ->post('/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a precise translation and localization assistant.'],
                    ['role' => 'user', 'content' => $instructions],
                ],
                'temperature' => 0.1,
                'max_tokens' => $maxTokens,
            ])
            ->throw();

        $content = trim((string) $response->json('choices.0.message.content', ''));

        if ($content === '') {
            throw new \RuntimeException('Localization API returned an empty response.');
        }

        return $content;
    }
}
