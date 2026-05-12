<?php

namespace App\Services\Vapi;

use App\Models\AssistantConfig;
use App\Models\CallEvent;
use App\Models\CreditLedger;
use App\Models\SupportCase;
use App\Models\UsageEvent;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use App\Services\Contacts\ContactLinkingService;
use App\Support\RegionalPilotStackCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VapiCallSyncService
{
    public function __construct(
        private readonly VapiClient $vapi,
        private readonly ContactLinkingService $contacts,
    ) {
    }

    public function syncFromWebhookPayload(Workspace $workspace, array $payload): ?CallEvent
    {
        $callId = data_get($payload, 'message.call.id');

        if (! $callId) {
            return null;
        }

        $context = $this->buildContextFromWebhookPayload($payload);

        if ($this->shouldFetchFullCall($context)) {
            $fetched = $this->fetchCall($callId);

            if ($fetched) {
                $context = $this->mergeContext($context, $this->buildContextFromCall($fetched));
            }
        }

        return $this->persistCallData($workspace, $callId, $context);
    }

    public function hydrateMissingWorkspaceCalls(Workspace $workspace, int $limit = 10): void
    {
        CallEvent::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('vapi_call_id')
            ->where(function ($query) {
                $query->whereNull('duration_seconds')
                    ->orWhereNull('transcript')
                    ->orWhere('transcript', '')
                    ->orWhereNull('recording_url')
                    ->orWhere('recording_url', '');
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->each(fn (CallEvent $callEvent) => $this->hydrateCallEvent($workspace, $callEvent));
    }

    public function hydrateCallEvent(Workspace $workspace, CallEvent $callEvent): ?CallEvent
    {
        if (! filled($callEvent->vapi_call_id)) {
            return $callEvent;
        }

        $fetched = $this->fetchCall($callEvent->vapi_call_id);

        if (! $fetched) {
            return $callEvent;
        }

        return $this->persistCallData(
            $workspace,
            $callEvent->vapi_call_id,
            $this->buildContextFromCall($fetched),
            $callEvent,
        );
    }

    private function persistCallData(Workspace $workspace, string $callId, array $context, ?CallEvent $existingCallEvent = null): ?CallEvent
    {
        $durationSeconds = $this->extractDurationSeconds($context);
        $cost = $this->extractCost($context);
        $occurredAt = $this->extractOccurredAt($context);
        $fromNumber = $this->extractFromNumber($context);
        $toNumber = $this->extractToNumber($context);
        $transcript = $this->extractTranscript($context);
        $recordingUrl = $this->extractRecordingUrl($context);
        $contactName = $this->extractContactName($context, $transcript);
        $contactEmail = $this->extractContactEmail($context);

        $case = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->where('external_call_id', $callId)
            ->first();
        $assistantConfig = $this->resolveAssistantConfig($workspace, $context, $case);
        $voiceMetadata = $this->extractVoiceMetadata($workspace, $context, $assistantConfig);

        $callEvent = $existingCallEvent
            ?? CallEvent::query()->where('vapi_call_id', $callId)->first()
            ?? new CallEvent(['vapi_call_id' => $callId]);

        $callEvent->workspace_id = $workspace->id;
        $callEvent->queue_id = $case?->queue_id ?? $callEvent->queue_id;
        $callEvent->from_number = $fromNumber ?: $callEvent->from_number;
        $callEvent->to_number = $toNumber ?: $callEvent->to_number;
        $callEvent->duration_seconds = $durationSeconds ?? $callEvent->duration_seconds;
        $callEvent->cost = $cost ?? $callEvent->cost;
        $callEvent->transcript = $transcript ?: $callEvent->transcript;
        $callEvent->recording_url = $recordingUrl ?: $callEvent->recording_url;
        $callEvent->meta = $this->compactMetadata(array_replace_recursive(is_array($callEvent->meta) ? $callEvent->meta : [], [
            'call' => $context['call'] ?? null,
            'artifact' => $context['artifact'] ?? null,
            'analysis' => $context['analysis'] ?? null,
            'endedReason' => $context['endedReason'] ?? null,
            'language' => $voiceMetadata,
        ]));

        if (! $callEvent->exists && $occurredAt) {
            $callEvent->created_at = $occurredAt->copy();
            $callEvent->updated_at = $occurredAt->copy();
        }

        $callEvent->save();

        if ($case) {
            $caseChanged = false;

            if ($transcript && $case->transcript !== $transcript) {
                $case->transcript = $transcript;
                $caseChanged = true;
            }

            if ($recordingUrl && $case->recording_url !== $recordingUrl) {
                $case->recording_url = $recordingUrl;
                $caseChanged = true;
            }

            if ($fromNumber && empty($case->requester_phone)) {
                $case->requester_phone = $fromNumber;
                $caseChanged = true;
            }

            if ($contactEmail && empty($case->requester_email)) {
                $case->requester_email = $contactEmail;
                $caseChanged = true;
            }

            $structuredPayload = $this->mergeCaseVoiceMetadata($case->structured_payload, $voiceMetadata);
            if ($structuredPayload !== $case->structured_payload) {
                $case->structured_payload = $structuredPayload;
                $caseChanged = true;
            }

            $summary = data_get($context, 'analysis.summary');
            if ($summary) {
                if (empty($case->description) || trim($case->description) === 'New case, no description') {
                    $case->description = $summary;
                    $caseChanged = true;
                }

                if (
                    empty($case->title)
                    || $case->title === 'New ticket'
                    || str_starts_with($case->title, 'New ticket')
                    || $case->title === 'New case'
                    || str_starts_with($case->title, 'New case')
                ) {
                    $case->title = Str::limit($summary, 80, '...');
                    $caseChanged = true;
                }
            }

            if ($caseChanged) {
                $case->save();
            }
        }

        $contactPhone = $case?->requester_phone ?: $fromNumber;
        $contact = $this->contacts->resolveForWorkspace($workspace, $contactPhone, $contactName, $contactEmail);

        if ($contact && $case && $case->contact_id !== $contact->id) {
            $case->contact_id = $contact->id;
            $case->save();
        }

        if ($durationSeconds > 0) {
            $existingUsage = UsageEvent::query()
                ->where('workspace_id', $workspace->id)
                ->where('event_type', 'call')
                ->where('metadata->vapi_call_id', $callId)
                ->exists();

            if (! $existingUsage) {
                UsageEvent::create([
                    'workspace_id' => $workspace->id,
                    'support_case_id' => $case?->id,
                    'minutes' => (int) ceil($durationSeconds / 60),
                    'event_type' => 'call',
                    'occurred_at' => $occurredAt ?? $callEvent->created_at ?? now(),
                    'metadata' => ['vapi_call_id' => $callId],
                ]);
            }
        }

        if ($cost > 0) {
            $costInCents = (int) round($cost * 100);

            if ($costInCents > 0) {
                DB::transaction(function () use ($workspace, $callId, $costInCents) {
                    $lockedWorkspace = Workspace::query()->lockForUpdate()->find($workspace->id);

                    if (! $lockedWorkspace) {
                        return;
                    }

                    $existingCreditDeduction = CreditLedger::query()
                        ->where('workspace_id', $lockedWorkspace->id)
                        ->where('type', 'call_deduction')
                        ->where('meta->vapi_call_id', $callId)
                        ->exists();

                    if (! $existingCreditDeduction) {
                        CreditLedger::create([
                            'workspace_id' => $lockedWorkspace->id,
                            'type' => 'call_deduction',
                            'amount' => -$costInCents,
                            'meta' => ['vapi_call_id' => $callId],
                        ]);

                        $lockedWorkspace->decrement('credits_balance', $costInCents);
                    }
                });
            }
        }

        $this->enforceFreePlanPhoneLimit($workspace);

        return $callEvent->fresh();
    }

    private function resolveAssistantConfig(Workspace $workspace, array $context, ?SupportCase $case): ?AssistantConfig
    {
        if ($case?->assistant_config_id) {
            return AssistantConfig::query()
                ->where('workspace_id', $workspace->id)
                ->where('id', $case->assistant_config_id)
                ->first();
        }

        $vapiAssistantId = trim((string) data_get($context, 'call.assistantId'));
        if ($vapiAssistantId !== '') {
            $assistantConfig = AssistantConfig::query()
                ->where('workspace_id', $workspace->id)
                ->where('vapi_assistant_id', $vapiAssistantId)
                ->first();

            if ($assistantConfig) {
                return $assistantConfig;
            }
        }

        $phoneNumberId = trim((string) data_get($context, 'call.phoneNumberId'));
        if ($phoneNumberId !== '') {
            $assistantId = WorkspacePhoneNumber::query()
                ->where('workspace_id', $workspace->id)
                ->where('vapi_phone_number_id', $phoneNumberId)
                ->value('assistant_id');

            if ($assistantId) {
                return AssistantConfig::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('id', $assistantId)
                    ->first();
            }
        }

        return null;
    }

    private function extractVoiceMetadata(Workspace $workspace, array $context, ?AssistantConfig $assistantConfig): array
    {
        $configuredCode = RegionalPilotStackCatalog::normalizeLanguageCode(
            $assistantConfig?->language_code,
            $workspace->preferredLanguageCode()
        );
        $transcriptLanguage = $this->extractTranscriptLanguageCode($context, $configuredCode);
        $transcriber = $this->extractTranscriberMetadata($context, $configuredCode);

        return $this->compactMetadata([
            'configured' => [
                'code' => $configuredCode,
                'label' => RegionalPilotStackCatalog::languageLabel($configuredCode),
                'source_label' => 'Assistant default',
            ],
            'transcript' => [
                'code' => $transcriptLanguage,
                'label' => RegionalPilotStackCatalog::languageLabel($transcriptLanguage, $configuredCode),
                'source_label' => $transcriptLanguage ? 'Detected from call' : null,
            ],
            'transcriber' => $transcriber,
        ]);
    }

    private function extractTranscriptLanguageCode(array $context, ?string $configuredCode): ?string
    {
        $candidates = [
            data_get($context, 'analysis.structuredData.languageCode'),
            data_get($context, 'analysis.languageCode'),
            data_get($context, 'call.languageCode'),
            data_get($context, 'artifact.languageCode'),
            data_get($context, 'artifact.transcriptLanguage'),
            data_get($context, 'call.artifact.transcriptLanguage'),
            data_get($context, 'call.transcriber.language'),
            data_get($context, 'call.monitor.transcriber.language'),
            data_get($context, 'analysis.structuredData.language'),
            data_get($context, 'analysis.language'),
            data_get($context, 'call.language'),
            data_get($context, 'artifact.language'),
        ];

        foreach ($candidates as $candidate) {
            $resolved = RegionalPilotStackCatalog::normalizeLanguageCode(is_scalar($candidate) ? (string) $candidate : null);

            if ($resolved) {
                return $resolved;
            }
        }

        return null;
    }

    private function extractTranscriberMetadata(array $context, ?string $configuredCode): array
    {
        $provider = $this->firstFilledString([
            data_get($context, 'call.transcriber.provider'),
            data_get($context, 'call.monitor.transcriber.provider'),
            data_get($context, 'call.transcriberPlan.provider'),
        ]);
        $model = $this->firstFilledString([
            data_get($context, 'call.transcriber.model'),
            data_get($context, 'call.monitor.transcriber.model'),
            data_get($context, 'call.transcriberPlan.model'),
        ]);

        if ($provider === null && $configuredCode) {
            $fallback = RegionalPilotStackCatalog::transcriberProfile($configuredCode);
            $provider = $fallback['provider'] ?? null;
            $model = $model ?? ($fallback['model'] ?? null);
        }

        if ($provider === null && $model === null) {
            return [];
        }

        $label = trim(collect([$provider ? ucfirst($provider) : null, $model])->filter()->implode(' '));

        return $this->compactMetadata([
            'provider' => $provider,
            'model' => $model,
            'label' => $label !== '' ? $label : null,
        ]);
    }

    private function mergeCaseVoiceMetadata(?array $structuredPayload, array $voiceMetadata): ?array
    {
        if ($voiceMetadata === []) {
            return $structuredPayload;
        }

        $payload = $structuredPayload ?? [];
        $payload['voice_metadata'] = $this->compactMetadata(array_replace_recursive(
            $payload['voice_metadata'] ?? [],
            $voiceMetadata
        ));

        return $payload !== [] ? $payload : null;
    }

    private function compactMetadata(array $payload): array
    {
        $filtered = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $value = $this->compactMetadata($value);
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    private function firstFilledString(array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $value = trim((string) $candidate);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function enforceFreePlanPhoneLimit(Workspace $workspace): void
    {
        if (! $workspace->isFreePlan() || ! $workspace->hasReachedVoiceMinuteLimit()) {
            return;
        }

        WorkspacePhoneNumber::query()
            ->where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    private function buildContextFromWebhookPayload(array $payload): array
    {
        return [
            'call' => (array) data_get($payload, 'message.call', []),
            'artifact' => (array) data_get($payload, 'message.artifact', []),
            'analysis' => (array) data_get($payload, 'message.analysis', []),
            'endedReason' => data_get($payload, 'message.endedReason'),
        ];
    }

    private function buildContextFromCall(array $call): array
    {
        return [
            'call' => $call,
            'artifact' => (array) ($call['artifact'] ?? []),
            'analysis' => (array) ($call['analysis'] ?? []),
            'endedReason' => $call['endedReason'] ?? null,
        ];
    }

    private function mergeContext(array $base, array $overlay): array
    {
        return [
            'call' => array_replace_recursive($base['call'] ?? [], $overlay['call'] ?? []),
            'artifact' => array_replace_recursive($base['artifact'] ?? [], $overlay['artifact'] ?? []),
            'analysis' => array_replace_recursive($base['analysis'] ?? [], $overlay['analysis'] ?? []),
            'endedReason' => $base['endedReason'] ?? $overlay['endedReason'] ?? null,
        ];
    }

    private function shouldFetchFullCall(array $context): bool
    {
        return $this->extractDurationSeconds($context) === null
            || ! filled($this->extractTranscript($context))
            || ! filled($this->extractRecordingUrl($context))
            || ! filled($this->extractFromNumber($context));
    }

    private function fetchCall(string $callId): ?array
    {
        if (! filled(config('services.vapi.key'))) {
            return null;
        }

        try {
            return $this->vapi->getCall($callId);
        } catch (\Throwable $e) {
            Log::warning('VAPI_CALL_FETCH_FAILED', [
                'callId' => $callId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function extractDurationSeconds(array $context): ?int
    {
        $candidates = [
            data_get($context, 'call.durationSeconds'),
            data_get($context, 'call.duration'),
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                $seconds = (int) round((float) $candidate);

                if ($seconds > 0) {
                    return $seconds;
                }
            }
        }

        $startedAt = data_get($context, 'call.startedAt')
            ?? data_get($context, 'call.createdAt');
        $endedAt = data_get($context, 'call.endedAt')
            ?? data_get($context, 'call.updatedAt');

        if (is_string($startedAt) && is_string($endedAt)) {
            try {
                $started = Carbon::parse($startedAt);
                $ended = Carbon::parse($endedAt);

                if ($ended->greaterThan($started)) {
                    return $started->diffInSeconds($ended);
                }
            } catch (\Throwable) {
                // Ignore malformed timestamps.
            }
        }

        return null;
    }

    private function extractCost(array $context): ?float
    {
        $candidate = data_get($context, 'call.cost');

        if (is_numeric($candidate)) {
            return (float) $candidate;
        }

        return null;
    }

    private function extractOccurredAt(array $context): ?Carbon
    {
        $candidates = [
            data_get($context, 'call.startedAt'),
            data_get($context, 'call.endedAt'),
            data_get($context, 'call.createdAt'),
            data_get($context, 'call.updatedAt'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            try {
                return Carbon::parse($candidate);
            } catch (\Throwable) {
                // Ignore malformed timestamps and continue to the next candidate.
            }
        }

        return null;
    }

    private function extractFromNumber(array $context): ?string
    {
        return $this->normalizePhone(
            data_get($context, 'call.customer.number')
            ?? data_get($context, 'call.customer.phoneNumber')
            ?? data_get($context, 'artifact.variableValues.customer.number')
            ?? data_get($context, 'artifact.variableValues.call.customer.number')
            ?? data_get($context, 'call.from')
        );
    }

    private function extractToNumber(array $context): ?string
    {
        return $this->normalizePhone(
            data_get($context, 'call.phoneNumber.number')
            ?? data_get($context, 'artifact.variableValues.phoneNumber.number')
            ?? data_get($context, 'artifact.variableValues.call.phoneNumber.number')
            ?? data_get($context, 'call.to')
        );
    }

    private function extractTranscript(array $context): ?string
    {
        $transcript = data_get($context, 'artifact.transcript')
            ?? data_get($context, 'call.artifact.transcript');

        if (is_string($transcript) && trim($transcript) !== '') {
            return trim($transcript);
        }

        if (is_array($transcript) && count($transcript) > 0) {
            return $this->flattenMessages($transcript);
        }

        $messages = data_get($context, 'artifact.messages')
            ?? data_get($context, 'call.artifact.messages');

        if (is_array($messages) && count($messages) > 0) {
            return $this->flattenMessages($messages);
        }

        $openAiMessages = data_get($context, 'artifact.messagesOpenAIFormatted')
            ?? data_get($context, 'call.artifact.messagesOpenAIFormatted');

        if (is_array($openAiMessages) && count($openAiMessages) > 0) {
            $normalized = collect($openAiMessages)
                ->map(fn (array $entry) => [
                    'role' => $entry['role'] ?? null,
                    'message' => $entry['content'] ?? $entry['message'] ?? null,
                ])
                ->all();

            return $this->flattenMessages($normalized);
        }

        $fallback = data_get($context, 'call.transcript');

        if (is_string($fallback) && trim($fallback) !== '') {
            return trim($fallback);
        }

        if (is_array($fallback) && count($fallback) > 0) {
            return $this->flattenMessages($fallback);
        }

        return null;
    }

    private function extractRecordingUrl(array $context): ?string
    {
        $candidates = [
            data_get($context, 'artifact.recording'),
            data_get($context, 'call.artifact.recording'),
            data_get($context, 'artifact.recordingUrl'),
            data_get($context, 'call.recordingUrl'),
            data_get($context, 'call.stereoRecordingUrl'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }

            if (is_array($candidate)) {
                foreach (['url', 'stereoUrl', 'monoUrl', 'combinedUrl', 'mp3Url', 'wavUrl', 'videoUrl'] as $key) {
                    $value = $candidate[$key] ?? data_get($candidate, "mono.{$key}");

                    if (is_string($value) && trim($value) !== '') {
                        return trim($value);
                    }
                }

                $monoCombined = data_get($candidate, 'mono.combinedUrl');
                if (is_string($monoCombined) && trim($monoCombined) !== '') {
                    return trim($monoCombined);
                }
            }
        }

        return null;
    }

    private function extractContactName(array $context, ?string $transcript): ?string
    {
        $candidates = [
            data_get($context, 'artifact.variableValues.customerName'),
            data_get($context, 'artifact.variableValues.requesterName'),
            data_get($context, 'artifact.variableValues.callerName'),
            data_get($context, 'artifact.variableValues.name'),
            data_get($context, 'analysis.structuredData.customerName'),
            data_get($context, 'analysis.structuredData.requesterName'),
            data_get($context, 'analysis.structuredData.callerName'),
            data_get($context, 'analysis.structuredData.name'),
        ];

        foreach ($candidates as $candidate) {
            $name = $this->normalizeNameCandidate($candidate);
            if ($name) {
                return $name;
            }
        }

        $transcriptEntries = data_get($context, 'artifact.transcript');
        if (is_array($transcriptEntries)) {
            foreach ($transcriptEntries as $entry) {
                if (($entry['role'] ?? null) !== 'user') {
                    continue;
                }

                $name = $this->nameFromText((string) ($entry['message'] ?? ''));
                if ($name) {
                    return $name;
                }
            }
        }

        $messages = data_get($context, 'artifact.messages');
        if (is_array($messages)) {
            foreach ($messages as $message) {
                if (($message['role'] ?? null) !== 'user') {
                    continue;
                }

                $name = $this->nameFromText((string) ($message['message'] ?? ''));
                if ($name) {
                    return $name;
                }
            }
        }

        return $transcript ? $this->nameFromCallerTranscript($transcript) : null;
    }

    private function extractContactEmail(array $context): ?string
    {
        $candidates = [
            data_get($context, 'artifact.variableValues.email'),
            data_get($context, 'artifact.variableValues.requesterEmail'),
            data_get($context, 'artifact.variableValues.callerEmail'),
            data_get($context, 'analysis.structuredData.email'),
            data_get($context, 'analysis.structuredData.requesterEmail'),
            data_get($context, 'analysis.structuredData.callerEmail'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate)) {
                continue;
            }

            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return null;
    }

    private function flattenMessages(array $messages): ?string
    {
        $lines = collect($messages)
            ->map(function (array $entry) {
                $message = trim((string) ($entry['message'] ?? $entry['content'] ?? ''));
                if ($message === '') {
                    return null;
                }

                $role = strtolower((string) ($entry['role'] ?? 'speaker'));
                $speaker = match ($role) {
                    'assistant', 'bot', 'system' => 'Assistant',
                    'user', 'customer', 'caller' => 'Caller',
                    default => Str::headline($role),
                };

                return "{$speaker}: {$message}";
            })
            ->filter()
            ->values()
            ->all();

        return count($lines) > 0 ? implode("\n", $lines) : null;
    }

    private function nameFromText(string $text): ?string
    {
        $patterns = [
            '/\bmy name is ([a-z][a-z\'-]+(?:\s+[a-z][a-z\'-]+){0,2})\b/i',
            '/\bthis is ([a-z][a-z\'-]+(?:\s+[a-z][a-z\'-]+){0,2})\b/i',
            '/\bi am ([a-z][a-z\'-]+(?:\s+[a-z][a-z\'-]+){0,2})\b/i',
            '/\bi\'m ([a-z][a-z\'-]+(?:\s+[a-z][a-z\'-]+){0,2})\b/i',
            '/\byou(?:\'re| are) speaking with ([a-z][a-z\'-]+(?:\s+[a-z][a-z\'-]+){0,2})\b/i',
            '/\bspeaking(?: with)? ([a-z][a-z\'-]+(?:\s+[a-z][a-z\'-]+){0,2})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches) === 1) {
                $parts = preg_split('/\s+/', trim((string) ($matches[1] ?? ''))) ?: [];

                for ($length = count($parts); $length >= 1; $length--) {
                    $candidate = $this->normalizeNameCandidate(implode(' ', array_slice($parts, 0, $length)));

                    if ($candidate) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    private function nameFromCallerTranscript(string $transcript): ?string
    {
        $lines = preg_split('/\r\n|\r|\n/', $transcript) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (! preg_match('/^(Caller|User|Customer)\s*:\s*(.+)$/i', $trimmed, $matches)) {
                continue;
            }

            $name = $this->nameFromText((string) ($matches[2] ?? ''));

            if ($name) {
                return $name;
            }
        }

        return null;
    }

    private function normalizeNameCandidate(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim((string) preg_replace('/[^\pL\s\'-]+/u', ' ', $value));
        $value = preg_replace('/\s+/', ' ', $value);

        if (! $value) {
            return null;
        }

        $parts = array_values(array_filter(explode(' ', $value)));
        $invalidTokens = [
            'about', 'afternoon', 'and', 'appointment', 'assistant', 'bring', 'bringing',
            'broken', 'call', 'calling', 'coming', 'cosmetic', 'current', 'direction',
            'for', 'from', 'gonna',
            'going', 'got', 'having', 'help', 'here', 'issue', 'it', 'just', 'leak',
            'leaking', 'looking', 'maintenance', 'meeting', 'morning', 'need', 'paint',
            'preset', 'problem', 'prompt', 'scheduling', 'sink', 'support', 'system',
            'teeth', 'there', 'time', 'to', 'today', 'tomorrow', 'trying', 'wall',
            'water', 'with', 'workflow', 'yesterday',
        ];

        if (count($parts) === 0 || count($parts) > 3) {
            return null;
        }

        foreach ($parts as $part) {
            $normalized = strtolower($part);

            if (strlen($normalized) < 2 || in_array($normalized, $invalidTokens, true)) {
                return null;
            }
        }

        return Str::title(implode(' ', array_map('strtolower', $parts)));
    }

    private function normalizePhone(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $value);

        return $digits !== '' ? $digits : null;
    }
}
