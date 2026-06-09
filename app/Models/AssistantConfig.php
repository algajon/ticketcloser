<?php

namespace App\Models;

use App\Support\RegionalPilotStackCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantConfig extends Model
{
    public const DEFAULT_MODEL = 'gpt-4o-mini';

    protected $fillable = [
        'workspace_id',
        'name',
        'system_prompt',
        'voice_provider',
        'voice_id',
        'language_code',
        'model_name',
        'first_message',
        'vapi_tool_id',
        'vapi_booking_tool_id',
        'vapi_lookup_tool_id',
        'vapi_case_lookup_tool_id',
        'vapi_assistant_id',
        'is_active',
        'fallback_phone',
        'intake_params',
        'preset_key',
        'override_params',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'intake_params' => 'array',
        'override_params' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public static function modelOptions(): array
    {
        return [
            [
                'value' => 'gpt-4o-mini',
                'label' => 'Standard',
                'headline' => 'gpt-4o-mini',
                'costLabel' => 'Lowest cost',
                'qualityLabel' => 'Best low-cost default',
                'description' => 'Good quality for most assistants. Keeps costs down while still handling case creation and scheduling well.',
                'voiceMode' => 'standard',
            ],
            [
                'value' => 'gpt-4.1-mini',
                'label' => 'Better',
                'headline' => 'gpt-4.1-mini',
                'costLabel' => 'Low to medium cost',
                'qualityLabel' => 'Sharper follow-through',
                'description' => 'More reliable on instructions, summaries, and tool sequencing than the lowest-cost tier.',
                'voiceMode' => 'standard',
            ],
            [
                'value' => 'gpt-4.1',
                'label' => 'Premium',
                'headline' => 'gpt-4.1',
                'costLabel' => 'Higher cost',
                'qualityLabel' => 'Best non-realtime quality',
                'description' => 'Best for stricter prompts, smoother case handling, and higher-trust caller experiences without switching to realtime audio.',
                'voiceMode' => 'standard',
            ],
            [
                'value' => 'gpt-realtime-2025-08-28',
                'label' => 'Premium voice',
                'headline' => 'gpt-realtime',
                'costLabel' => 'Highest cost',
                'qualityLabel' => 'Most natural phone feel',
                'description' => 'Native speech-to-speech. Best choice when you want the most fluid, premium-sounding calls.',
                'voiceMode' => 'realtime',
            ],
        ];
    }

    public static function normalizedModelName(?string $modelName): string
    {
        $modelName = trim((string) $modelName);
        $allowed = collect(self::modelOptions())->pluck('value')->all();

        return in_array($modelName, $allowed, true) ? $modelName : self::DEFAULT_MODEL;
    }

    public static function isRealtimeModelName(?string $modelName): bool
    {
        return str_starts_with(self::normalizedModelName($modelName), 'gpt-realtime');
    }

    public function operatorRoutingEnabled(): bool
    {
        return filter_var(data_get($this->intake_params ?? [], 'operator.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function operatorRouteLanguageCodes(): array
    {
        $routes = data_get($this->intake_params ?? [], 'operator.routes', []);

        if (! is_array($routes)) {
            return [];
        }

        return collect($routes)
            ->filter(fn ($route) => is_array($route))
            ->pluck('language_code')
            ->map(fn ($languageCode) => RegionalPilotStackCatalog::normalizeLanguageCode(is_scalar($languageCode) ? (string) $languageCode : null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function usesMultilingualTranscription(?string $defaultLanguageCode = null): bool
    {
        $configuredCode = RegionalPilotStackCatalog::normalizeLanguageCode(
            $this->language_code,
            $defaultLanguageCode ?: 'en-US',
        );

        if ($configuredCode === 'multi') {
            return true;
        }

        if (! $this->operatorRoutingEnabled()) {
            return false;
        }

        $routeLanguageCodes = $this->operatorRouteLanguageCodes();

        return count($routeLanguageCodes) > 1
            || ($configuredCode && in_array($configuredCode, $routeLanguageCodes, true) === false && $routeLanguageCodes !== []);
    }

    public function transcriberLanguageCode(?string $defaultLanguageCode = null): string
    {
        if ($this->usesMultilingualTranscription($defaultLanguageCode)) {
            return 'multi';
        }

        return RegionalPilotStackCatalog::normalizeLanguageCode(
            $this->language_code,
            $defaultLanguageCode ?: 'en-US',
        ) ?: 'en-US';
    }
}
