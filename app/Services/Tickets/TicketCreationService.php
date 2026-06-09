<?php

namespace App\Services\Tickets;

use App\Models\AssistantConfig;
use App\Models\CallEvent;
use App\Models\Queue;
use App\Models\SupportCase;
use App\Models\Workspace;
use App\Services\Contacts\ContactLinkingService;
use App\Support\RegionalPilotStackCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketCreationService
{
    public function __construct(
        private readonly ContactLinkingService $contacts,
    ) {
    }

    public function createForWorkspace(Workspace $workspace, array $attributes, array $context = []): SupportCase
    {
        return DB::transaction(function () use ($workspace, $attributes, $context) {
            $payload = $this->normalizeAttributes($workspace, $attributes);
            $call = $context['call'] ?? [];
            $assistantConfig = $this->resolveAssistantConfig($workspace, $context);

            $case = $this->resolveExistingCase($workspace, $payload['external_call_id']);

            if (!$case) {
                $case = new SupportCase([
                    'workspace_id' => $workspace->id,
                    'case_number' => $this->generateCaseNumber(),
                ]);
            }

            $updates = [
                'assistant_config_id' => $assistantConfig?->id,
                'title' => $payload['title'],
                'description' => $payload['description'],
                'category' => $payload['category'],
                'priority' => $payload['priority'],
                'status' => $payload['status'],
                'requester_phone' => $payload['requester_phone'],
                'requester_email' => $payload['requester_email'],
                'queue_id' => $this->resolveQueueId($workspace, $payload['queue']),
                'structured_payload' => $this->mergeVoiceMetadata($payload['structured_payload'], $assistantConfig),
                'source' => $payload['source'],
                'external_call_id' => $payload['external_call_id'],
            ];

            foreach (['ops_stage', 'access_notes', 'preferred_visit_window', 'vendor_name', 'vendor_phone'] as $key) {
                if ($payload[$key] !== null) {
                    $updates[$key] = $payload[$key];
                }
            }

            $case->fill($updates);

            $this->applyCallContext($case, $call);
            $case->save();

            $contact = $this->resolveContact($workspace, $payload);
            if ($contact && $case->contact_id !== $contact->id) {
                $case->contact_id = $contact->id;
                $case->save();
            }

            $this->recordCallEvent($workspace, $case, $call, $assistantConfig);

            return $case->fresh();
        });
    }

    private function normalizeAttributes(Workspace $workspace, array $attributes): array
    {
        $structuredPayload = $this->normalizeStructuredPayload($attributes['structuredPayload'] ?? $attributes['structured_payload'] ?? null, $attributes);
        $priority = $this->normalizePriority($attributes['priority'] ?? null);
        $opsStage = $this->normalizeOpsStage(
            $workspace,
            $attributes['opsStage'] ?? $attributes['ops_stage'] ?? null,
            $priority
        );

        return [
            'title' => trim((string) ($attributes['title'] ?? '')) ?: 'New ticket',
            'description' => trim((string) ($attributes['description'] ?? '')),
            'category' => trim((string) ($attributes['category'] ?? '')) ?: 'general',
            'priority' => $priority,
            'status' => trim((string) ($attributes['status'] ?? '')) ?: SupportCase::STATUS_NEW,
            'requester_phone' => $this->normalizePhone($attributes['requesterPhone'] ?? $attributes['requester_phone'] ?? null),
            'requester_email' => $this->normalizeNullableString($attributes['requesterEmail'] ?? $attributes['requester_email'] ?? null),
            'source' => trim((string) ($attributes['source'] ?? '')) ?: SupportCase::SOURCE_VOICE,
            'external_call_id' => $this->normalizeNullableString($attributes['externalCallId'] ?? $attributes['external_call_id'] ?? null),
            'queue' => $attributes['queue'] ?? null,
            'structured_payload' => $structuredPayload,
            'requester_name' => $this->normalizeNullableString($attributes['requesterName'] ?? $attributes['requester_name'] ?? null),
            'ops_stage' => $opsStage,
            'access_notes' => $this->normalizeNullableString($attributes['accessNotes'] ?? $attributes['access_notes'] ?? data_get($structuredPayload, 'accessNotes') ?? data_get($structuredPayload, 'access_details')),
            'preferred_visit_window' => $this->normalizeNullableString($attributes['preferredVisitWindow'] ?? $attributes['preferred_visit_window'] ?? data_get($structuredPayload, 'preferredVisitWindow') ?? data_get($structuredPayload, 'bestTimeForFollowUp')),
            'vendor_name' => $this->normalizeNullableString($attributes['vendorName'] ?? $attributes['vendor_name'] ?? null),
            'vendor_phone' => $this->normalizePhone($attributes['vendorPhone'] ?? $attributes['vendor_phone'] ?? null),
        ];
    }

    private function resolveExistingCase(Workspace $workspace, ?string $externalCallId): ?SupportCase
    {
        if (!$externalCallId) {
            return null;
        }

        return SupportCase::where('workspace_id', $workspace->id)
            ->where('external_call_id', $externalCallId)
            ->first();
    }

    private function resolveAssistantConfig(Workspace $workspace, array $context): ?AssistantConfig
    {
        if (!empty($context['assistant_config_id'])) {
            return AssistantConfig::where('workspace_id', $workspace->id)
                ->where('id', (int) $context['assistant_config_id'])
                ->first();
        }

        $vapiAssistantId = $context['vapi_assistant_id'] ?? null;
        if (!$vapiAssistantId) {
            return null;
        }

        return AssistantConfig::where('workspace_id', $workspace->id)
            ->where('vapi_assistant_id', $vapiAssistantId)
            ->first();
    }

    private function resolveQueueId(Workspace $workspace, mixed $queue): ?int
    {
        if (!$queue) {
            return null;
        }

        return Queue::where('workspace_id', $workspace->id)
            ->where(function ($query) use ($queue) {
                $query->where('id', $queue)
                    ->orWhere('name', $queue);
            })
            ->value('id');
    }

    private function resolveContact(Workspace $workspace, array $payload): ?\App\Models\Contact
    {
        return $this->contacts->resolveForWorkspace(
            $workspace,
            $payload['requester_phone'],
            $payload['requester_name'],
            $payload['requester_email'],
            $payload['structured_payload']['propertyCode'] ?? $payload['structured_payload']['property_code'] ?? null,
            $payload['structured_payload']['unit'] ?? $payload['structured_payload']['unitNumber'] ?? null,
            $payload['access_notes'],
        );
    }

    private function normalizeStructuredPayload(mixed $structuredPayload, array $attributes): ?array
    {
        $payload = is_array($structuredPayload) ? $structuredPayload : [];

        $fieldMap = [
            'propertyCode' => [$attributes['propertyCode'] ?? null, $attributes['property_code'] ?? null],
            'unit' => [$attributes['unit'] ?? null, $attributes['unitNumber'] ?? null, $attributes['unit_number'] ?? null],
            'accessNotes' => [$attributes['accessNotes'] ?? null, $attributes['access_notes'] ?? null, $attributes['accessDetails'] ?? null, $attributes['access_details'] ?? null],
            'preferredVisitWindow' => [$attributes['preferredVisitWindow'] ?? null, $attributes['preferred_visit_window'] ?? null, $attributes['bestTimeForFollowUp'] ?? null],
        ];

        foreach ($fieldMap as $key => $candidates) {
            foreach ($candidates as $candidate) {
                $candidate = $this->normalizeNullableString($candidate);

                if ($candidate !== null) {
                    $payload[$key] = $candidate;
                    break;
                }
            }
        }

        return $payload !== [] ? $payload : null;
    }

    private function mergeVoiceMetadata(?array $structuredPayload, ?AssistantConfig $assistantConfig): ?array
    {
        if (! $assistantConfig) {
            return $structuredPayload;
        }

        $configuredCode = RegionalPilotStackCatalog::normalizeLanguageCode($assistantConfig->language_code);
        $transcriberCode = $assistantConfig->transcriberLanguageCode($configuredCode ?: 'en-US');
        $transcriber = RegionalPilotStackCatalog::transcriberProfile($transcriberCode);

        $payload = $structuredPayload ?? [];
        $payload['voice_metadata'] = array_filter(array_replace_recursive(
            $payload['voice_metadata'] ?? [],
            [
                'configured' => array_filter([
                    'code' => $configuredCode,
                    'label' => RegionalPilotStackCatalog::languageLabel($configuredCode),
                    'source_label' => 'Assistant default',
                ], fn ($value) => filled($value)),
                'transcriber' => array_filter([
                    'provider' => $transcriber['provider'] ?? null,
                    'model' => $transcriber['model'] ?? null,
                    'language' => $transcriber['language'] ?? null,
                    'label' => $transcriber['label'] ?? null,
                ], fn ($value) => filled($value)),
            ]
        ), fn ($value) => $value !== null && $value !== []);

        return $payload !== [] ? $payload : null;
    }

    private function normalizeOpsStage(Workspace $workspace, mixed $opsStage, string $priority): ?string
    {
        $opsStage = strtolower(trim((string) $opsStage));

        if ($workspace->use_case !== 'property_management') {
            return $opsStage !== '' ? $opsStage : null;
        }

        if (in_array($opsStage, SupportCase::PROPERTY_MANAGEMENT_OPS_STAGES, true)) {
            return $opsStage;
        }

        return in_array($priority, [SupportCase::PRIORITY_CRITICAL, SupportCase::PRIORITY_HIGH], true)
            ? SupportCase::OPS_STAGE_URGENT_REVIEW
            : SupportCase::OPS_STAGE_NEW_INTAKE;
    }

    private function applyCallContext(SupportCase $case, array $call): void
    {
        $transcript = data_get($call, 'artifact.transcript') ?? $call['transcript'] ?? null;
        if ($transcript !== null) {
            $case->transcript = is_string($transcript) ? $transcript : json_encode($transcript);
        }

        $recordingUrl = data_get($call, 'artifact.recording') ?? data_get($call, 'recordingUrl');
        if (is_array($recordingUrl)) {
            $recordingUrl = $recordingUrl['url'] ?? $recordingUrl['stereoUrl'] ?? $recordingUrl['monoUrl'] ?? null;
        }

        if (!empty($recordingUrl)) {
            $case->recording_url = $recordingUrl;
        }

        if (!$case->external_call_id && !empty($call['id'])) {
            $case->external_call_id = $call['id'];
        }
    }

    private function recordCallEvent(Workspace $workspace, SupportCase $case, array $call, ?AssistantConfig $assistantConfig = null): void
    {
        $vapiCallId = $call['id'] ?? $case->external_call_id;
        if (!$vapiCallId) {
            return;
        }

        $meta = $call;
        $configuredCode = RegionalPilotStackCatalog::normalizeLanguageCode(
            $assistantConfig?->language_code,
            $workspace->preferredLanguageCode()
        );

        if ($configuredCode) {
            $transcriberCode = $assistantConfig?->transcriberLanguageCode($configuredCode) ?? $configuredCode;
            $transcriber = RegionalPilotStackCatalog::transcriberProfile($transcriberCode);
            $meta['language'] = array_filter([
                'configured' => array_filter([
                    'code' => $configuredCode,
                    'label' => RegionalPilotStackCatalog::languageLabel($configuredCode),
                    'source_label' => 'Assistant default',
                ], fn ($value) => filled($value)),
                'transcriber' => array_filter([
                    'provider' => $transcriber['provider'] ?? null,
                    'model' => $transcriber['model'] ?? null,
                    'language' => $transcriber['language'] ?? null,
                    'label' => $transcriber['label'] ?? null,
                ], fn ($value) => filled($value)),
            ], fn ($value) => $value !== null && $value !== []);
        }

        CallEvent::updateOrCreate(
            ['vapi_call_id' => $vapiCallId],
            [
                'workspace_id' => $workspace->id,
                'queue_id' => $case->queue_id,
                'from_number' => $this->normalizePhone(data_get($call, 'customer.number') ?? ($call['from'] ?? null)),
                'to_number' => $this->normalizePhone(data_get($call, 'phoneNumber.number') ?? ($call['to'] ?? null)),
                'duration_seconds' => $call['durationSeconds'] ?? null,
                'cost' => $call['cost'] ?? null,
                'transcript' => $case->transcript,
                'recording_url' => $case->recording_url,
                'meta' => $meta,
            ]
        );
    }

    private function generateCaseNumber(): string
    {
        do {
            $caseNumber = 'TC-' . strtoupper(Str::random(8));
        } while (SupportCase::where('case_number', $caseNumber)->exists());

        return $caseNumber;
    }

    private function normalizePriority(mixed $priority): string
    {
        $priority = strtolower(trim((string) $priority));

        return in_array($priority, SupportCase::PRIORITIES, true)
            ? $priority
            : SupportCase::PRIORITY_NORMAL;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $phone = $this->normalizeNullableString($phone);
        if (!$phone) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $phone);

        return $digits !== '' ? $digits : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

}
