<?php

namespace App\Services\Tickets;

use App\Models\AssistantConfig;
use App\Models\CallEvent;
use App\Models\Contact;
use App\Models\Queue;
use App\Models\SupportCase;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketCreationService
{
    public function createForWorkspace(Workspace $workspace, array $attributes, array $context = []): SupportCase
    {
        return DB::transaction(function () use ($workspace, $attributes, $context) {
            $payload = $this->normalizeAttributes($attributes);
            $call = $context['call'] ?? [];

            $case = $this->resolveExistingCase($workspace, $payload['external_call_id']);

            if (!$case) {
                $case = new SupportCase([
                    'workspace_id' => $workspace->id,
                    'case_number' => $this->generateCaseNumber(),
                ]);
            }

            $case->fill([
                'assistant_config_id' => $this->resolveAssistantConfigId($workspace, $context),
                'title' => $payload['title'],
                'description' => $payload['description'],
                'category' => $payload['category'],
                'priority' => $payload['priority'],
                'status' => $payload['status'],
                'requester_phone' => $payload['requester_phone'],
                'requester_email' => $payload['requester_email'],
                'queue_id' => $this->resolveQueueId($workspace, $payload['queue']),
                'structured_payload' => $payload['structured_payload'],
                'source' => $payload['source'],
                'external_call_id' => $payload['external_call_id'],
            ]);

            $this->applyCallContext($case, $call);
            $case->save();

            $contact = $this->resolveContact($workspace, $payload);
            if ($contact && $case->contact_id !== $contact->id) {
                $case->contact_id = $contact->id;
                $case->save();
            }

            $this->recordCallEvent($workspace, $case, $call);

            return $case->fresh();
        });
    }

    private function normalizeAttributes(array $attributes): array
    {
        return [
            'title' => trim((string) ($attributes['title'] ?? '')) ?: 'New ticket',
            'description' => trim((string) ($attributes['description'] ?? '')),
            'category' => trim((string) ($attributes['category'] ?? '')) ?: 'general',
            'priority' => $this->normalizePriority($attributes['priority'] ?? null),
            'status' => trim((string) ($attributes['status'] ?? '')) ?: SupportCase::STATUS_NEW,
            'requester_phone' => $this->normalizePhone($attributes['requesterPhone'] ?? $attributes['requester_phone'] ?? null),
            'requester_email' => $this->normalizeNullableString($attributes['requesterEmail'] ?? $attributes['requester_email'] ?? null),
            'source' => trim((string) ($attributes['source'] ?? '')) ?: SupportCase::SOURCE_VOICE,
            'external_call_id' => $this->normalizeNullableString($attributes['externalCallId'] ?? $attributes['external_call_id'] ?? null),
            'queue' => $attributes['queue'] ?? null,
            'structured_payload' => is_array($attributes['structuredPayload'] ?? null) ? $attributes['structuredPayload'] : null,
            'requester_name' => $this->normalizeNullableString($attributes['requesterName'] ?? $attributes['requester_name'] ?? null),
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

    private function resolveAssistantConfigId(Workspace $workspace, array $context): ?int
    {
        if (!empty($context['assistant_config_id'])) {
            return (int) $context['assistant_config_id'];
        }

        $vapiAssistantId = $context['vapi_assistant_id'] ?? null;
        if (!$vapiAssistantId) {
            return null;
        }

        return AssistantConfig::where('workspace_id', $workspace->id)
            ->where('vapi_assistant_id', $vapiAssistantId)
            ->value('id');
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

    private function resolveContact(Workspace $workspace, array $payload): ?Contact
    {
        if (!$payload['requester_phone']) {
            return null;
        }

        $phoneSearch = preg_replace('/\D+/', '', $payload['requester_phone']);
        if (strlen($phoneSearch) < 6) {
            return null;
        }

        $contact = Contact::where('workspace_id', $workspace->id)
            ->where('phone_e164', 'like', '%' . $phoneSearch . '%')
            ->first();

        if (!$contact) {
            $contact = new Contact([
                'workspace_id' => $workspace->id,
                'phone_e164' => $payload['requester_phone'],
            ]);
        }

        if (!$contact->name && $payload['requester_name']) {
            $contact->name = $payload['requester_name'];
        }

        if (!$contact->email && $payload['requester_email']) {
            $contact->email = $payload['requester_email'];
        }

        $contact->save();

        return $contact;
    }

    private function applyCallContext(SupportCase $case, array $call): void
    {
        if (isset($call['transcript'])) {
            $case->transcript = is_string($call['transcript']) ? $call['transcript'] : json_encode($call['transcript']);
        }

        if (!empty($call['recordingUrl'])) {
            $case->recording_url = $call['recordingUrl'];
        }

        if (!$case->external_call_id && !empty($call['id'])) {
            $case->external_call_id = $call['id'];
        }
    }

    private function recordCallEvent(Workspace $workspace, SupportCase $case, array $call): void
    {
        $vapiCallId = $call['id'] ?? $case->external_call_id;
        if (!$vapiCallId) {
            return;
        }

        CallEvent::updateOrCreate(
            ['vapi_call_id' => $vapiCallId],
            [
                'workspace_id' => $workspace->id,
                'queue_id' => $case->queue_id,
                'from_number' => $call['from'] ?? null,
                'to_number' => $call['to'] ?? null,
                'duration_seconds' => $call['durationSeconds'] ?? null,
                'cost' => $call['cost'] ?? null,
                'transcript' => $case->transcript,
                'recording_url' => $case->recording_url,
                'meta' => $call,
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
