<?php

namespace App\Services\Vapi;

use App\Models\AssistantConfig;
use App\Models\AssistantPreset;
use App\Models\VoiceConfig;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use App\Services\Assistants\AssistantScriptLocalizer;
use App\Support\RegionalPilotStackCatalog;
use Illuminate\Support\Facades\DB;

class VapiProvisioningService
{
    private const HUMANE_DEFAULT_WAIT_SECONDS = 0.7;
    private const HUMANE_MIN_WAIT_SECONDS = 0.5;
    private const HUMANE_MAX_WAIT_SECONDS = 1.5;
    private const HUMANE_DEFAULT_INTERRUPT_WORDS = 2;
    private const HUMANE_MIN_INTERRUPT_WORDS = 1;
    private const HUMANE_MAX_INTERRUPT_WORDS = 8;
    private const HUMANE_DEFAULT_VOICE_SECONDS = 0.25;
    private const HUMANE_MIN_VOICE_SECONDS = 0.15;
    private const HUMANE_MAX_VOICE_SECONDS = 0.5;
    private const HUMANE_DEFAULT_BACKOFF_SECONDS = 1.2;
    private const HUMANE_MIN_BACKOFF_SECONDS = 1.0;
    private const HUMANE_MAX_BACKOFF_SECONDS = 2.5;
    private const HUMANE_DEFAULT_VOICE_SPEED = 1.0;
    private const HUMANE_MIN_VOICE_SPEED = 0.94;
    private const HUMANE_MAX_VOICE_SPEED = 1.04;

    public function __construct(
        private readonly VapiClient $vapi,
        private readonly ?AssistantScriptLocalizer $scriptLocalizer = null,
    ) {
    }

    public function provisionAssistantAndTool(Workspace $workspace, array $input): AssistantConfig
    {
        $config = AssistantConfig::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => ($input['name'] ?? 'tickIt Assistant')],
            ['name' => $input['name'] ?? 'tickIt Assistant']
        );

        return $this->provisionAssistantAndToolForConfig($config, $workspace, $input);
    }

    public function provisionAssistantAndToolForConfig(AssistantConfig $config, Workspace $workspace, array $input): AssistantConfig
    {
        $resolvedModelName = AssistantConfig::normalizedModelName($input['model_name'] ?? $config->model_name);
        $resolvedPresetKey = AssistantPreset::normalizeKey($input['preset_key'] ?? $config->preset_key);
        $resolvedVoiceProvider = $input['voice_provider'] ?? $config->voice_provider;
        $resolvedVoiceId = $input['voice_id'] ?? $config->voice_id;
        $resolvedLanguageCode = $this->normalizeLanguageCode(
            $input['language_code'] ?? $config->language_code,
            $resolvedVoiceId,
            $workspace,
        );
        [$resolvedVoiceProvider, $resolvedVoiceId] = $this->normalizePreferredVoiceSelection(
            $workspace,
            $resolvedLanguageCode,
            $resolvedVoiceProvider,
            $resolvedVoiceId,
            $resolvedModelName,
            $resolvedPresetKey,
        );

        $config->fill([
            'name' => $input['name'] ?? $config->name,
            'first_message' => $input['first_message'] ?? $config->first_message,
            'system_prompt' => $input['system_prompt'] ?? $config->system_prompt,
            'voice_provider' => $resolvedVoiceProvider,
            'voice_id' => $resolvedVoiceId,
            'language_code' => $resolvedLanguageCode,
            'model_name' => $resolvedModelName,
            'preset_key' => $resolvedPresetKey,
            'override_params' => $this->normalizeHumaneTimingOverrides(
                $input['override_params'] ?? $config->override_params ?? []
            ),
            'intake_params' => $input['intake_params'] ?? $config->intake_params,
            'is_active' => $input['is_active'] ?? true,
        ])->save();

        $this->applyWorkspaceGuardrails($config, $workspace);

        $integrationToken = $workspace->integration_token
            ?? throw new \RuntimeException('Workspace integration token not found.');

        $toolPayload = $this->buildCreateCaseToolPayload(
            workspaceSlug: $workspace->slug,
            integrationToken: $integrationToken
        );

        if ($config->vapi_tool_id) {
            $updatePayload = $toolPayload;
            unset($updatePayload['type']);
            $this->vapi->updateTool($config->vapi_tool_id, $updatePayload);
        } else {
            $tool = $this->vapi->createTool($toolPayload);
            $config->vapi_tool_id = $tool['id'] ?? null;
            $config->save();
        }

        $bookingToolPayload = $this->buildBookMeetingToolPayload(
            workspaceSlug: $workspace->slug,
            integrationToken: $integrationToken
        );

        if ($config->vapi_booking_tool_id) {
            $updatePayload = $bookingToolPayload;
            unset($updatePayload['type']);
            $this->vapi->updateTool($config->vapi_booking_tool_id, $updatePayload);
        } else {
            $tool = $this->vapi->createTool($bookingToolPayload);
            $config->vapi_booking_tool_id = $tool['id'] ?? null;
            $config->save();
        }

        $lookupToolPayload = $this->buildLookupContactToolPayload(
            workspaceSlug: $workspace->slug,
            integrationToken: $integrationToken
        );

        if ($config->vapi_lookup_tool_id) {
            $updatePayload = $lookupToolPayload;
            unset($updatePayload['type']);
            $this->vapi->updateTool($config->vapi_lookup_tool_id, $updatePayload);
        } else {
            $tool = $this->vapi->createTool($lookupToolPayload);
            $config->vapi_lookup_tool_id = $tool['id'] ?? null;
            $config->save();
        }

        $caseLookupToolPayload = $this->buildLookupCaseToolPayload(
            workspaceSlug: $workspace->slug,
            integrationToken: $integrationToken
        );

        if ($config->vapi_case_lookup_tool_id) {
            $updatePayload = $caseLookupToolPayload;
            unset($updatePayload['type']);
            $this->vapi->updateTool($config->vapi_case_lookup_tool_id, $updatePayload);
        } else {
            $tool = $this->vapi->createTool($caseLookupToolPayload);
            $config->vapi_case_lookup_tool_id = $tool['id'] ?? null;
            $config->save();
        }

        $assistantPayload = $this->buildAssistantPayload(
            $config,
            $config->vapi_tool_id,
            $config->vapi_booking_tool_id,
            $config->vapi_lookup_tool_id,
            $config->vapi_case_lookup_tool_id,
            $workspace
        );

        if ($config->vapi_assistant_id) {
            $this->vapi->updateAssistant($config->vapi_assistant_id, $assistantPayload);
        } else {
            $assistant = $this->vapi->createAssistant($assistantPayload);
            $config->vapi_assistant_id = $assistant['id'] ?? null;
            $config->save();
        }

        return $config->fresh();
    }

    public function provisionPhoneNumber(Workspace $workspace, array $input): WorkspacePhoneNumber
    {
        if (! empty($input['assistant_id'])) {
            $assistantConfig = AssistantConfig::where('workspace_id', $workspace->id)
                ->where('id', $input['assistant_id'])
                ->firstOrFail();
        } else {
            $assistantConfig = AssistantConfig::where('workspace_id', $workspace->id)->firstOrFail();
        }

        $plan = $workspace->activePlan();
        $phoneLimit = $workspace->bypassesPlanLimits()
            ? -1
            : (int) ($plan['max_phone_numbers'] ?? -1);

        if ($phoneLimit !== -1) {
            $existingNumberCount = WorkspacePhoneNumber::query()
                ->where('workspace_id', $workspace->id)
                ->whereNotNull('vapi_phone_number_id')
                ->where(function ($query) use ($assistantConfig) {
                    $query->whereNull('assistant_id')
                        ->orWhere('assistant_id', '!=', $assistantConfig->id);
                })
                ->count();

            $existingForAssistant = WorkspacePhoneNumber::query()
                ->where('workspace_id', $workspace->id)
                ->where('assistant_id', $assistantConfig->id)
                ->whereNotNull('vapi_phone_number_id')
                ->exists();

            if (! $existingForAssistant && $existingNumberCount >= $phoneLimit) {
                throw new \RuntimeException('This plan has reached its phone number limit.');
            }
        }

        if (! $assistantConfig->vapi_assistant_id) {
            throw new \RuntimeException('You must sync the assistant before provisioning a phone number.');
        }

        $record = WorkspacePhoneNumber::firstOrCreate([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistantConfig->id,
        ]);
        $previousProvisioningMode = $record->provisioning_mode;
        $previousCredentialId = $record->vapi_credential_id;
        $record->assistant_id = $assistantConfig->id;
        $record->provisioning_mode = $input['provisioning_mode'] ?? $record->provisioning_mode ?? $workspace->preferredPhoneSetupMode();
        $record->external_provider = $input['external_provider'] ?? $record->external_provider ?? $workspace->preferredExternalPhoneProvider();
        $record->vapi_credential_id = $input['vapi_credential_id'] ?? $record->vapi_credential_id ?? $workspace->default_vapi_credential_id;

        $areaCode = isset($input['area_code'])
            ? preg_replace('/\D+/', '', (string) $input['area_code'])
            : null;

        if ($record->provisioning_mode !== 'vapi_instant') {
            if (array_key_exists('forwarding_number', $input)) {
                $record->forwarding_number = $this->normalizeStoredPhoneNumber($input['forwarding_number']);
            }

            if (filled($record->vapi_credential_id)) {
                $payload = [
                    'provider' => 'byo-phone-number',
                    'credentialId' => $record->vapi_credential_id,
                    'name' => $workspace->name . ' Support',
                    'assistantId' => $assistantConfig->vapi_assistant_id,
                    'serverUrl' => config('services.vapi.webhook_url'),
                ];

                $routingNumber = preg_replace('/[^0-9+]/', '', (string) ($record->forwarding_number ?? ''));
                if ($routingNumber !== '') {
                    $payload['number'] = $routingNumber;
                }

                $pn = $this->syncImportedPhoneNumber(
                    $record,
                    $payload,
                    $routingNumber,
                    filled($previousCredentialId) && $previousProvisioningMode !== 'vapi_instant',
                );

                $record->vapi_phone_number_id = $pn['id'] ?? $record->vapi_phone_number_id;
                $record->e164 = $pn['number'] ?? $pn['sipUri'] ?? $routingNumber ?: $record->e164;
                $record->assistant_id = $assistantConfig->id;
                $record->save();
            } elseif (
                $record->provisioning_mode === 'existing_business_number'
                && ! empty($input['auto_forwarding_target'])
            ) {
                if (! $record->vapi_phone_number_id) {
                    $pn = $this->createVapiNumber($record, $workspace, $assistantConfig, $areaCode);
                } else {
                    $pn = $this->vapi->updatePhoneNumber($record->vapi_phone_number_id, [
                        'name' => $workspace->name . ' Support',
                        'serverUrl' => config('services.vapi.webhook_url'),
                        'assistantId' => $assistantConfig->vapi_assistant_id,
                    ]);
                    $record->e164 = $pn['number'] ?? $pn['sipUri'] ?? $record->e164;
                    $record->assistant_id = $assistantConfig->id;
                    $record->save();
                }
            }

            $record->is_active = filled($record->vapi_phone_number_id) || filled($record->forwarding_number) || filled($record->e164);
            $record->save();

            if (filled($record->vapi_phone_number_id) && filled($record->e164)) {
                $this->syncAssistantPayload($assistantConfig->fresh() ?? $assistantConfig, $workspace);
            }

            return $record->fresh();
        }

        $record->is_active = true;

        if (! $record->vapi_phone_number_id) {
            $pn = $this->createVapiNumber($record, $workspace, $assistantConfig, $areaCode);
        } else {
            try {
                $pn = $this->vapi->updatePhoneNumber($record->vapi_phone_number_id, [
                    'name' => $workspace->name . ' Support',
                    'serverUrl' => config('services.vapi.webhook_url'),
                    'assistantId' => $assistantConfig->vapi_assistant_id,
                ]);
                $record->e164 = $pn['number'] ?? $pn['sipUri'] ?? $record->e164;
                $record->assistant_id = $assistantConfig->id;
                $record->save();
            } catch (\Illuminate\Http\Client\RequestException $e) {
                try {
                    $this->vapi->deletePhoneNumber($record->vapi_phone_number_id);
                } catch (\Throwable) {
                    // Ignore missing phone numbers in Vapi.
                }

                $record->vapi_phone_number_id = null;
                $record->e164 = null;
                $record->activation_started_at = null;
                $record->save();

                $pn = $this->createVapiNumber($record, $workspace, $assistantConfig, $areaCode);
            }
        }

        if (array_key_exists('forwarding_number', $input)) {
            $record->forwarding_number = $this->normalizeStoredPhoneNumber($input['forwarding_number']);
            $record->save();
        }

        if (filled($record->vapi_phone_number_id) && filled($record->e164)) {
            $this->syncAssistantPayload($assistantConfig->fresh() ?? $assistantConfig, $workspace);
        }

        return $record->fresh();
    }

    public function deleteAssistantAndLinkedResources(Workspace $workspace, AssistantConfig $assistant): void
    {
        $phoneNumbers = WorkspacePhoneNumber::query()
            ->where('workspace_id', $workspace->id)
            ->where('assistant_id', $assistant->id)
            ->get();

        foreach ($phoneNumbers as $phoneNumber) {
            if (filled($phoneNumber->vapi_phone_number_id)) {
                $this->vapi->deletePhoneNumber($phoneNumber->vapi_phone_number_id);
            }
        }

        if (filled($assistant->vapi_assistant_id)) {
            $this->vapi->deleteAssistant($assistant->vapi_assistant_id);
        }

        if (filled($assistant->vapi_tool_id)) {
            $this->vapi->deleteTool($assistant->vapi_tool_id);
        }

        if (filled($assistant->vapi_booking_tool_id)) {
            $this->vapi->deleteTool($assistant->vapi_booking_tool_id);
        }

        if (filled($assistant->vapi_lookup_tool_id)) {
            $this->vapi->deleteTool($assistant->vapi_lookup_tool_id);
        }

        if (filled($assistant->vapi_case_lookup_tool_id)) {
            $this->vapi->deleteTool($assistant->vapi_case_lookup_tool_id);
        }

        DB::transaction(function () use ($assistant, $phoneNumbers): void {
            foreach ($phoneNumbers as $phoneNumber) {
                $phoneNumber->delete();
            }

            $assistant->delete();
        });
    }

    public function deletePhoneNumber(Workspace $workspace, WorkspacePhoneNumber $phoneNumber): void
    {
        if ($phoneNumber->workspace_id !== $workspace->id) {
            throw new \RuntimeException('Phone number does not belong to this workspace.');
        }

        if (filled($phoneNumber->vapi_phone_number_id)) {
            $this->vapi->deletePhoneNumber($phoneNumber->vapi_phone_number_id);
        }

        $phoneNumber->delete();
    }

    private function createVapiNumber(
        WorkspacePhoneNumber $record,
        Workspace $workspace,
        AssistantConfig $assistantConfig,
        ?string $areaCode
    ): array {
        $resolvedAreaCode = $areaCode ?: $this->inferInstantProvisioningAreaCode($record);

        $payload = [
            'provider' => 'vapi',
            'name' => $workspace->name . ' Support',
            'serverUrl' => config('services.vapi.webhook_url'),
            'assistantId' => $assistantConfig->vapi_assistant_id,
        ];

        if ($resolvedAreaCode && strlen($resolvedAreaCode) === 3) {
            $payload['numberDesiredAreaCode'] = $resolvedAreaCode;
        }

        $pn = $this->vapi->createPhoneNumber($payload);
        $record->vapi_phone_number_id = $pn['id'] ?? null;
        $record->e164 = $pn['number'] ?? $pn['sipUri'] ?? null;
        $record->activation_started_at = filled($record->vapi_phone_number_id) && filled($record->e164)
            ? now()
            : null;
        $record->assistant_id = $assistantConfig->id;
        $record->save();

        return $pn;
    }

    private function syncImportedPhoneNumber(
        WorkspacePhoneNumber $record,
        array $payload,
        string $routingNumber,
        bool $canUpdateExistingImportedPhone
    ): array {
        if (! $record->vapi_phone_number_id) {
            return $this->vapi->createPhoneNumber($payload);
        }

        if (! $canUpdateExistingImportedPhone) {
            $this->deleteRemotePhoneNumberQuietly($record->vapi_phone_number_id);

            $record->vapi_phone_number_id = null;
            $record->e164 = null;
            $record->activation_started_at = null;
            $record->save();

            return $this->vapi->createPhoneNumber($payload);
        }

        try {
            return $this->vapi->updatePhoneNumber($record->vapi_phone_number_id, $payload);
        } catch (\Illuminate\Http\Client\RequestException) {
            $this->deleteRemotePhoneNumberQuietly($record->vapi_phone_number_id);

            $record->vapi_phone_number_id = null;
            $record->e164 = null;
            $record->activation_started_at = null;
            $record->save();

            $createPayload = $payload;
            if ($routingNumber !== '') {
                $createPayload['number'] = $routingNumber;
            }

            return $this->vapi->createPhoneNumber($createPayload);
        }
    }

    private function deleteRemotePhoneNumberQuietly(?string $phoneNumberId): void
    {
        if (! filled($phoneNumberId)) {
            return;
        }

        try {
            $this->vapi->deletePhoneNumber($phoneNumberId);
        } catch (\Throwable) {
            // Ignore missing phone numbers in Vapi when we are rebuilding the binding.
        }
    }

    private function normalizeStoredPhoneNumber(?string $phoneNumber): ?string
    {
        $normalized = trim((string) $phoneNumber);

        return $normalized !== '' ? $normalized : null;
    }

    private function inferInstantProvisioningAreaCode(WorkspacePhoneNumber $record): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $record->e164);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return substr($digits, 1, 3);
        }

        if (strlen($digits) === 10) {
            return substr($digits, 0, 3);
        }

        return null;
    }

    private function buildCreateCaseToolPayload(string $workspaceSlug, string $integrationToken): array
    {
        $webhookUrl = config('services.vapi.webhook_url');

        return [
            'type' => 'function',
            'async' => false,
            'function' => [
                'name' => 'createCase',
                'description' => 'Create a support case in tickIt after the caller confirms the summary. If the caller also wants a meeting, create the case first, wait for the returned case number, and only then move to scheduling.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'Short issue title (one sentence)'],
                        'description' => ['type' => 'string', 'description' => 'Full description of the issue as reported by the caller'],
                        'requesterName' => ['type' => 'string', 'description' => 'Caller name if they shared it during the conversation'],
                        'requesterEmail' => ['type' => 'string', 'description' => 'Caller email if they shared it and it matters for follow-up'],
                        'category' => ['type' => 'string', 'description' => 'Category such as maintenance, plumbing, hvac, electrical, lockout, billing, technical, or other'],
                        'priority' => ['type' => 'string', 'description' => 'Priority: low, normal, high, or critical'],
                        'propertyCode' => ['type' => 'string', 'description' => 'Property, building, or address when the business needs that context'],
                        'unit' => ['type' => 'string', 'description' => 'Unit, suite, or apartment number when relevant'],
                        'accessNotes' => ['type' => 'string', 'description' => 'Access details, lockbox notes, or entry instructions when relevant'],
                        'preferredVisitWindow' => ['type' => 'string', 'description' => 'Preferred follow-up or visit window when the caller gives one'],
                    ],
                    'required' => ['title', 'description'],
                ],
            ],
            'server' => [
                'url' => $webhookUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $integrationToken,
                    'X-Workspace-Slug' => $workspaceSlug,
                ],
            ],
            'parameters' => [
                ['key' => 'requesterPhone', 'value' => '{{ customer.number }}'],
                ['key' => 'source', 'value' => 'voice'],
            ],
            'variableExtractionPlan' => [
                'aliases' => [
                    ['key' => 'createdCaseId', 'value' => '{{ $.id }}'],
                    ['key' => 'createdCaseNumber', 'value' => '{{ $.caseNumber }}'],
                ],
            ],
        ];
    }

    private function buildBookMeetingToolPayload(string $workspaceSlug, string $integrationToken): array
    {
        $webhookUrl = config('services.vapi.webhook_url');

        return [
            'type' => 'function',
            'async' => false,
            'function' => [
                'name' => 'bookMeeting',
                'description' => 'Book a follow-up meeting only after createCase has already returned a case number for this conversation. If the caller asks to schedule before a case exists, explain that you will log the request first and then book the follow-up immediately after. Never call this in parallel with createCase.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'caseId' => ['type' => 'string', 'description' => 'The Case Number returned by the createCase function for this same conversation.'],
                        'dateTime' => ['type' => 'string', 'description' => 'The requested datetime in ISO 8601 format (for example 2026-03-05T14:00:00Z). Convert relative dates like "tomorrow at 3 PM" into an exact timestamp before calling the tool.'],
                        'timezone' => ['type' => 'string', 'description' => 'IANA timezone for the requested meeting time when known, for example America/New_York.'],
                    ],
                    'required' => ['caseId', 'dateTime'],
                ],
            ],
            'server' => [
                'url' => $webhookUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $integrationToken,
                    'X-Workspace-Slug' => $workspaceSlug,
                ],
            ],
            'parameters' => [
                ['key' => 'caseId', 'value' => '{{ createdCaseNumber }}'],
            ],
        ];
    }

    private function syncAssistantPayload(AssistantConfig $config, Workspace $workspace): void
    {
        if (! $config->vapi_assistant_id) {
            return;
        }

        $this->vapi->updateAssistant($config->vapi_assistant_id, $this->buildAssistantPayload(
            $config,
            $config->vapi_tool_id,
            $config->vapi_booking_tool_id,
            $config->vapi_lookup_tool_id,
            $config->vapi_case_lookup_tool_id,
            $workspace
        ));
    }

    private function smsToolForAssistant(AssistantConfig $config): ?array
    {
        $from = WorkspacePhoneNumber::query()
            ->where('workspace_id', $config->workspace_id)
            ->where('assistant_id', $config->id)
            ->where('is_active', true)
            ->whereNotNull('vapi_phone_number_id')
            ->whereNotNull('e164')
            ->latest('id')
            ->value('e164');

        $from = $this->normalizeSmsPhoneNumber(is_scalar($from) ? (string) $from : null);

        if ($from === null) {
            return null;
        }

        return [
            'type' => 'sms',
            'metadata' => [
                'from' => $from,
            ],
        ];
    }

    private function normalizeSmsPhoneNumber(?string $phoneNumber): ?string
    {
        $phoneNumber = trim((string) $phoneNumber);

        if ($phoneNumber === '') {
            return null;
        }

        $normalized = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (! is_string($normalized) || $normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '+')) {
            return $normalized;
        }

        return strlen($normalized) >= 10 ? '+'.$normalized : null;
    }

    private function buildLookupContactToolPayload(string $workspaceSlug, string $integrationToken): array
    {
        $webhookUrl = config('services.vapi.webhook_url');

        return [
            'type' => 'function',
            'async' => false,
            'function' => [
                'name' => 'lookupContact',
                'description' => 'Look up an existing contact by caller phone number before asking for details the business may already have on file. If you call this without a phone number, tickIt will use the live caller number automatically. Use this only when caller context is not already provided in your system note, or when the caller asks what details are already on file. Never narrate this lookup out loud.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'phone' => ['type' => 'string', 'description' => 'Caller phone number if you need to override the default metadata phone number.'],
                        'email' => ['type' => 'string', 'description' => 'Caller email if they mention it and you want to look them up by email.'],
                    ],
                ],
            ],
            'server' => [
                'url' => $webhookUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $integrationToken,
                    'X-Workspace-Slug' => $workspaceSlug,
                ],
            ],
            'parameters' => [
                ['key' => 'phone', 'value' => '{{ customer.number }}'],
            ],
        ];
    }

    private function buildLookupCaseToolPayload(string $workspaceSlug, string $integrationToken): array
    {
        $webhookUrl = config('services.vapi.webhook_url');

        return [
            'type' => 'function',
            'async' => false,
            'function' => [
                'name' => 'lookupCase',
                'description' => 'Look up recent cases for the current caller or a known contact so you can sound familiar with their history. Use this silently only when recent case history would genuinely help, such as when the caller asks about a previous issue or when the system note does not already provide enough history. Never say "just a sec", "one moment", or similar filler before using it.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'contactId' => ['type' => 'string', 'description' => 'The contact ID returned by lookupContact when available.'],
                        'phone' => ['type' => 'string', 'description' => 'Caller phone number if you need to override the default metadata phone number.'],
                        'email' => ['type' => 'string', 'description' => 'Caller email if they mention it and you want to match recent cases by email.'],
                        'limit' => ['type' => 'integer', 'description' => 'How many recent cases to return. Keep this small, usually 1 to 3.'],
                    ],
                ],
            ],
            'server' => [
                'url' => $webhookUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $integrationToken,
                    'X-Workspace-Slug' => $workspaceSlug,
                ],
            ],
            'parameters' => [
                ['key' => 'phone', 'value' => '{{ customer.number }}'],
            ],
        ];
    }

    private function buildAssistantPayload(AssistantConfig $config, ?string $toolId, ?string $bookingToolId, ?string $lookupToolId, ?string $caseLookupToolId, Workspace $workspace): array
    {
        $presetData = [];
        $presetKey = AssistantPreset::normalizeKey($config->preset_key);

        if ($presetKey) {
            $preset = AssistantPreset::ensureDefaults()->firstWhere('key', $presetKey);
            if ($preset && $preset->vapi_payload_json) {
                $presetData = $preset->vapi_payload_json;
            }
        }

        $systemPrompt = $config->system_prompt ?: ($presetData['systemPrompt'] ?? $this->defaultPromptTemplate());
        $systemPrompt = str_replace('{{company_name}}', $workspace->name, $systemPrompt);
        $systemPrompt = $this->scriptLocalizer()->localizePrompt($systemPrompt, $config->language_code, [
            'workspace_name' => $workspace->name,
            'assistant_name' => $config->name,
        ]);
        $systemPrompt .= "\n\n" . $this->toolExecutionGuardrailsPrompt();
        $systemPrompt .= "\n\n" . $this->knownCallerGuardrailsPrompt();
        $systemPrompt .= "\n\n" . $this->humaneConversationGuardrailsPrompt();
        $systemPrompt .= "\n\n" . $this->silentHandoffGuardrailsPrompt();
        $systemPrompt .= "\n\n" . $this->smsConfirmationGuardrailsPrompt();

        if (! empty($config->language_code)) {
            $systemPrompt .= "\n\n[SYSTEM NOTE: Keep caller-facing replies in {$config->language_code} unless the caller clearly switches language and the business supports that change.]";
        }

        $operatorPrompt = $this->operatorRoutingPrompt($config, $workspace);
        if ($operatorPrompt !== '') {
            $systemPrompt .= "\n\n".$operatorPrompt;
        }

        $systemPrompt .= "\n\n[SYSTEM NOTE: Today is " . now()->format('l, F j, Y') . ". The current time is " . now()->format('g:i A T') . ". Always use this exact date as your reference when scheduling meetings or calculating relative times.]";
        $modelName = AssistantConfig::normalizedModelName($config->model_name);
        $isRealtimeModel = AssistantConfig::isRealtimeModelName($modelName);

        $model = [
            'provider' => 'openai',
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
            ],
        ];

        if ($isRealtimeModel) {
            $model['temperature'] = 0.6;
            $model['maxTokens'] = 380;
        }

        if ($toolId) {
            $model['toolIds'][] = $toolId;
        }

        if ($bookingToolId) {
            $model['toolIds'][] = $bookingToolId;
        }

        if ($lookupToolId) {
            $model['toolIds'][] = $lookupToolId;
        }

        if ($caseLookupToolId) {
            $model['toolIds'][] = $caseLookupToolId;
        }

        foreach ($this->operatorHandoffTools($config, $workspace) as $operatorHandoffTool) {
            $model['tools'][] = $operatorHandoffTool;
        }

        if ($smsTool = $this->smsToolForAssistant($config)) {
            $model['tools'][] = $smsTool;
        }

        if (! empty($config->fallback_phone)) {
            $model['tools'][] = [
                'type' => 'transferCall',
                'destinations' => [[
                    'type' => 'number',
                    'number' => preg_replace('/[^0-9+]/', '', $config->fallback_phone),
                ]],
            ];

            $model['messages'][0]['content'] .= "\n\n[SYSTEM NOTE: If the caller is exceptionally frustrated, demands a human, or reports a high-severity emergency, immediately use the transferCall tool. Do not keep troubleshooting in those situations.]";
        }

        $firstMessage = $this->buildFirstMessage($config, $workspace);

        $payload = [
            'name' => $config->name,
            'model' => $model,
            'firstMessage' => $firstMessage,
        ];

        if ($this->isOperatorRouteDestination($config, $workspace)) {
            if ($this->operatorRoutingEnabled($config)) {
                $payload['firstMessage'] = $this->operatorDestinationFirstMessage($config, $workspace);
                $payload['firstMessageMode'] = 'assistant-speaks-first';
            } else {
                $payload['firstMessage'] = '';
                $payload['firstMessageMode'] = 'assistant-speaks-first-with-model-generated-message';
            }
        }

        $voiceConfig = VoiceConfig::query()
            ->where('workspace_id', $workspace->id)
            ->latest('id')
            ->first();

        $payload['artifactPlan'] = [
            'recordingEnabled' => (bool) ($voiceConfig?->recording_enabled ?? true),
            'loggingEnabled' => true,
            'transcriptPlan' => [
                'enabled' => (bool) ($voiceConfig?->transcript_enabled ?? true),
                'assistantName' => $config->name ?: 'Assistant',
                'userName' => 'Caller',
            ],
        ];

        $voice = $this->voiceBlock($config, $workspace);
        if ($voice) {
            $payload['voice'] = $voice;
        }

        if (! $isRealtimeModel) {
            $payload['transcriber'] = $this->transcriberBlock($config, $workspace);
        }
        $payload['backgroundSpeechDenoisingPlan'] = [
            'smartDenoisingPlan' => [
                'enabled' => true,
            ],
        ];

        $startSpeakingPlan = $this->applyStartSpeakingDefaults(
            $presetData['startSpeakingPlan'] ?? [],
            $config,
        );
        $stopSpeakingPlan = $this->applyStopSpeakingDefaults($presetData['stopSpeakingPlan'] ?? [], $config);

        $overrides = $config->override_params ?? [];
        if (isset($overrides['waitSeconds'])) {
            $startSpeakingPlan['waitSeconds'] = (float) $overrides['waitSeconds'];
        }
        if (isset($overrides['numWords'])) {
            $stopSpeakingPlan['numWords'] = (int) $overrides['numWords'];
        }
        if (isset($overrides['backoffSeconds'])) {
            $stopSpeakingPlan['backoffSeconds'] = (float) $overrides['backoffSeconds'];
        }

        $startSpeakingPlan = $this->enforceHumaneStartSpeakingPlan($startSpeakingPlan);
        $stopSpeakingPlan = $this->enforceHumaneStopSpeakingPlan($stopSpeakingPlan);

        if (! empty($startSpeakingPlan)) {
            $payload['startSpeakingPlan'] = $startSpeakingPlan;
        }
        if (! empty($stopSpeakingPlan)) {
            $payload['stopSpeakingPlan'] = $stopSpeakingPlan;
        }

        return $payload;
    }

    private function operatorHandoffTools(AssistantConfig $config, Workspace $workspace): array
    {
        if (! $this->operatorRoutingEnabled($config)) {
            return [];
        }

        $routes = $this->operatorRoutes($config);
        if ($routes === []) {
            return [];
        }

        $assistantIds = collect($routes)
            ->pluck('assistant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($assistantIds === []) {
            return [];
        }

        $destinationsByAssistant = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $assistantIds)
            ->whereNotNull('vapi_assistant_id')
            ->get(['id', 'name', 'language_code', 'vapi_assistant_id'])
            ->keyBy('id');

        $tools = [];

        foreach ($routes as $route) {
            $assistantId = (int) ($route['assistant_id'] ?? 0);
            $assistant = $destinationsByAssistant->get($assistantId);

            if (! $assistant || blank($assistant->vapi_assistant_id)) {
                continue;
            }

            $descriptionParts = array_filter([
                trim((string) ($route['label'] ?? '')),
                filled($route['keywords'] ?? null) ? 'caller may say: '.trim((string) $route['keywords']) : null,
                filled($route['language_code'] ?? null) ? 'language: '.trim((string) $route['language_code']) : null,
                'destination assistant: '.$assistant->name,
            ]);

            $destination = [
                'type' => 'assistant',
                'assistantId' => $assistant->vapi_assistant_id,
                'description' => implode('; ', $descriptionParts),
                'contextEngineeringPlan' => [
                    'type' => 'userAndAssistantMessages',
                ],
            ];

            $label = trim((string) ($route['label'] ?? $assistant->name));
            $label = $label !== '' ? $label : $assistant->name;
            $keywords = trim((string) ($route['keywords'] ?? ''));
            $phraseHint = $keywords !== ''
                ? " Caller phrases for this route: {$keywords}."
                : '';
            $functionName = $this->operatorHandoffFunctionName($label, $assistant->name);

            $tools[] = [
                'type' => 'handoff',
                'function' => [
                    'name' => $functionName,
                    'description' => "Silently hand off the call to {$label} ({$assistant->name}). Use this when the caller says {$label} or any configured phrase for this route.{$phraseHint}",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => [
                                'type' => 'string',
                                'description' => 'Brief internal reason for the handoff.',
                            ],
                        ],
                        'required' => [],
                    ],
                ],
                'messages' => [],
                'destinations' => [$destination],
            ];
        }

        return $tools;
    }

    private function operatorRoutingPrompt(AssistantConfig $config, Workspace $workspace): string
    {
        if (! $this->operatorRoutingEnabled($config)) {
            return '';
        }

        $routes = $this->operatorRoutes($config);
        $liveAssistantIds = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', collect($routes)->pluck('assistant_id')->filter()->all())
            ->whereNotNull('vapi_assistant_id')
            ->pluck('vapi_assistant_id', 'id');

        $destinationNames = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', collect($routes)->pluck('assistant_id')->filter()->all())
            ->pluck('name', 'id');

        $routeLines = collect($routes)
            ->values()
            ->map(function (array $route, int $index) use ($liveAssistantIds, $destinationNames): string {
                $label = trim((string) ($route['label'] ?? 'Route '.($index + 1)));
                $keywords = trim((string) ($route['keywords'] ?? ''));
                $language = trim((string) ($route['language_code'] ?? ''));
                $assistantId = (int) ($route['assistant_id'] ?? 0);
                $status = $liveAssistantIds->has($assistantId) ? 'live handoff configured' : 'saved but not live until the destination assistant is synced';
                $destinationName = (string) ($destinationNames[$assistantId] ?? $label);
                $functionName = $this->operatorHandoffFunctionName($label, $destinationName);

                return ($index + 1).") {$label}"
                    .($keywords !== '' ? " | caller may say: {$keywords}" : '')
                    .($language !== '' ? " | language: {$language}" : '')
                    ." | handoff function: {$functionName}"
                    ." | {$status}";
            })
            ->implode("\n");

        if ($routeLines === '') {
            $routeLines = 'No live routes are configured yet. Continue normal intake and do not claim a transfer is available.';
        }

        $intro = $this->operatorIntro($config, $workspace);
        $fallback = $this->operatorFallbackMessage($config);

        return trim(<<<PROMPT
[SYSTEM NOTE: OPERATOR ROUTING MODE]
This assistant can act as a spoken operator before normal intake.
- Start by using this operator routing line when it fits the call: "{$intro}"
- The configured spoken routes below are the only choices this operator can offer. Do not mention or offer departments, languages, or queues that are not configured on this assistant.
- This is Vapi-only spoken routing. Do not tell callers they must press keypad buttons. If a caller says "one", "two", "English", "Spanish", "German", "sales", "support", or another configured phrase out loud, treat that as their spoken route choice.
- A phrase listed after "caller may say" counts as that configured route, even if the phrase does not exactly match the route label.
- Route only to the exact configured choice the caller actually said. A language choice such as "English" may only route to the English destination; it must never be treated as a hidden choice for sales, tech support, or property maintenance.
- If this operator's opening line asks for a language first and the caller only says a language such as "English", do not infer support, sales, or maintenance. If no configured route label is that exact language, ask this exact next question and wait: "{$fallback}"
- Never say "You're through to support" or "How can I help you?" after a language-only answer. A language choice is permission to continue, not a department choice.
- When the caller says a configured route, your next action must be the matching handoff function listed below. Do not speak another sentence first.
- Never answer "How can I help you?" while live operator routes are available. If a route matches, call the matching handoff function instead of normal intake.
- Ask one short clarification if the route is unclear: "{$fallback}"
- If this assistant was reached after the caller chose only a language or parent menu, do not infer a downstream destination from that prior choice. Ask which configured route they need first.
- When you are confident about the route and the route is live, silently use the matching Vapi handoff destination for that route. Do not acknowledge the route choice, do not say "connecting", and do not create a ticket before handoff unless no matching live destination exists.
- Handoffs are handled in the background. Do not tell the caller the call is ending, and do not say goodbye before or after a handoff.
- If no live destination matches, say this fallback message naturally and wait for a route choice: "{$fallback}". Do not continue normal intake while routing is on.

Configured spoken routes:
{$routeLines}
PROMPT);
    }

    private function operatorRoutingEnabled(AssistantConfig $config): bool
    {
        return filter_var(data_get($config->intake_params ?? [], 'operator.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function isOperatorRouteDestination(AssistantConfig $config, Workspace $workspace): bool
    {
        return AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->where('id', '!=', $config->id)
            ->get(['id', 'intake_params'])
            ->contains(function (AssistantConfig $operator) use ($config): bool {
                if (! $this->operatorRoutingEnabled($operator)) {
                    return false;
                }

                return collect($this->operatorRoutes($operator))
                    ->contains(fn (array $route): bool => (int) ($route['assistant_id'] ?? 0) === (int) $config->id);
            });
    }

    private function operatorDestinationFirstMessage(AssistantConfig $config, Workspace $workspace): string
    {
        if (trim((string) $config->first_message) !== '') {
            return $this->buildFirstMessage($config, $workspace);
        }

        $message = $this->scriptLocalizer()->localizeOpeningLine(
            $this->operatorIntro($config, $workspace),
            $config->language_code,
            [
                'workspace_name' => $workspace->name,
                'assistant_name' => $config->name,
            ],
        );

        return rtrim($message) . '{{ knownCallerSuffix | default: "" }}';
    }

    private function operatorRoutes(AssistantConfig $config): array
    {
        $routes = data_get($config->intake_params ?? [], 'operator.routes', []);

        if (! is_array($routes)) {
            return [];
        }

        return collect($routes)
            ->filter(fn ($route) => is_array($route))
            ->map(fn (array $route) => [
                'label' => trim((string) ($route['label'] ?? '')),
                'keywords' => trim((string) ($route['keywords'] ?? '')),
                'assistant_id' => filled($route['assistant_id'] ?? null) ? (int) $route['assistant_id'] : null,
                'language_code' => trim((string) ($route['language_code'] ?? '')),
            ])
            ->filter(fn (array $route) => $route['label'] !== '' || $route['keywords'] !== '' || $route['assistant_id'])
            ->values()
            ->all();
    }

    private function operatorHandoffFunctionName(string $label, string $assistantName): string
    {
        $source = $assistantName !== '' ? $assistantName : $label;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $source));
        $slug = trim((string) preg_replace('/_+/', '_', $slug), '_');

        return 'handoff_to_'.($slug !== '' ? $slug : 'assistant');
    }

    private function operatorIntro(AssistantConfig $config, Workspace $workspace): string
    {
        $intro = trim((string) data_get($config->intake_params ?? [], 'operator.intro', ''));

        return $intro !== ''
            ? $intro
            : "Thanks for calling {$workspace->name}. Tell me which team or language you need, and I will connect you.";
    }

    private function operatorFallbackMessage(AssistantConfig $config): string
    {
        $fallback = trim((string) data_get($config->intake_params ?? [], 'operator.fallback_message', ''));

        return $fallback !== ''
            ? $fallback
            : 'I can help route the call, but I need one more detail. Which team should I connect you with?';
    }

    private function voiceBlock(AssistantConfig $config, Workspace $workspace): ?array
    {
        $defaultVoice = $this->defaultVoiceProfile($config, $workspace);
        $voiceProvider = $config->voice_provider ?: $defaultVoice['provider'];
        $voiceId = $config->voice_id ?: $defaultVoice['voiceId'];
        $isRealtimeModel = AssistantConfig::isRealtimeModelName($config->model_name);

        if ($voiceId === 'Hana') {
            $voiceProvider = $defaultVoice['provider'];
            $voiceId = $defaultVoice['voiceId'];
        }

        if ($isRealtimeModel && ! $this->supportsRealtimeVoice($voiceProvider, $voiceId)) {
            $voiceProvider = $defaultVoice['provider'];
            $voiceId = $defaultVoice['voiceId'];
        }

        $speed = $this->humaneVoiceSpeed($defaultVoice['speed'] ?? self::HUMANE_DEFAULT_VOICE_SPEED);

        return [
            'provider' => $voiceProvider,
            'voiceId' => $voiceId,
            'speed' => round($speed, 2),
        ] + ($voiceProvider === 'vapi' ? ['version' => 2] : []);
    }

    private function transcriberBlock(AssistantConfig $config, Workspace $workspace): array
    {
        $languageCode = $config->transcriberLanguageCode(
            RegionalPilotStackCatalog::defaultLanguageForMarket($workspace->primary_market ?? null)
        );
        $transcriber = RegionalPilotStackCatalog::transcriberProfile($languageCode);

        $keyterms = collect([
            $workspace->name,
            $workspace->case_label,
            $config->name,
        ])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values()
            ->all();

        $payload = [
            'provider' => $transcriber['provider'],
            'model' => $transcriber['model'],
            'language' => $transcriber['language'],
            'smartFormat' => true,
            'numerals' => true,
            'keyterm' => $keyterms,
        ];

        if (! empty($transcriber['fallback'])) {
            $payload['fallbackPlan'] = [
                'transcribers' => [[
                    'provider' => $transcriber['fallback']['provider'],
                    'language' => $transcriber['fallback']['language'],
                ]],
            ];
        }

        return $payload;
    }

    private function defaultPromptTemplate(): string
    {
        return trim(<<<'PROMPT'
You are the voice assistant for this business.

Core behavior:
1) Sound natural, clear, upbeat, and easy to follow, but never rushed.
2) Never talk over the caller or treat a short pause like the end of their thought.
3) Ask one question at a time.
4) When the caller shares their full name and it matters for follow-up, politely confirm the spelling once.
5) Capture the caller's name when it matters, and include requesterName in createCase if they shared it.
6) The caller's phone number is usually already available from call metadata, so do not ask for it unless it is truly missing.
7) If caller context is not already provided in your system note and you truly need to know whether the business already has caller details on file, silently use lookupContact before asking the caller to repeat identity details.
8) If recent case history would help and it is not already provided in your system note, silently use lookupCase only after you know who the caller is.
9) Ask at most 1-2 clarifying questions before moving toward action.
10) Read back a short summary and ask for confirmation.
11) ONLY after confirmation, call createCase with:
   title, description, category, priority, requesterName, requesterEmail, and any property, unit, access, or visit-window details that matter for the business
12) After createCase returns a case number, tell the caller the case number and the next step in one clean sentence.
13) If the caller wants a follow-up meeting, book it only after the case exists.
14) If the caller asks to book the meeting before a case exists, explain that you will log the request first and then book the follow-up right after.
15) After bookMeeting succeeds, use the sms tool once to send the caller a short confirmation text with the scheduled date and time, the ticket or case number when available, and a brief issue label.

Spoken style:
- Keep responses short and easy to hear.
- Keep energy up and avoid long, dragging sentences.
- Never mention internal tool names.
- Do not make the caller repeat information you already have.
- Never narrate a contact or case lookup with phrases like "Just a sec", "One moment", "Let me check that", or "Hold on while I look that up". Do the lookup silently and continue naturally.
- If you already have caller context in your system note, use it immediately instead of pausing to re-check it.
- Never create a separate turn whose only purpose is announcing that you recognize the caller or are checking their details. If you know the caller, simply greet them naturally and continue.
PROMPT);
    }

    private function toolExecutionGuardrailsPrompt(): string
    {
        return trim(<<<'PROMPT'
[SYSTEM NOTE: TOOL EXECUTION RULES]
- Never call createCase and bookMeeting in parallel.
- If the caller wants both a case and a meeting, first confirm the summary, then call createCase exactly once.
- Wait for createCase to return the case number before calling bookMeeting.
- Use the case number returned by createCase for bookMeeting.
- Use any caller context already provided in your system note first.
- Use lookupContact only if caller context is missing, unclear, or the caller asks what details are on file.
- Use lookupCase only if recent case history would help and it is not already provided in your system note.
- Do not use lookupContact or lookupCase at the very start of the call when the system note already contains caller identity or recent case context.
- Never call lookupContact repeatedly once you already have enough caller context.
- Never call lookupCase repeatedly once you already have enough recent case context.
- If the caller asks for a meeting before a case exists, explain that you will log the request first and then handle the booking immediately after the case is created.
- Never say the booking cannot happen just because the case has not been created yet.
- After bookMeeting succeeds, use sms at most once to send a short confirmation text to the live caller number with the scheduled date/time and case number.
- Do not retry a tool after it succeeds.
- If a tool fails, explain that briefly and decide the next step with the caller instead of repeatedly calling the same tool.
- Never say "Just a sec", "One moment", or similar filler before using lookupContact or lookupCase. Do those lookups silently.
PROMPT);
    }

    private function smsConfirmationGuardrailsPrompt(): string
    {
        return trim(<<<'PROMPT'
[SYSTEM NOTE: SMS CONFIRMATION RULES]
- The sms tool is only for short transactional confirmations, not general chatting or marketing.
- Use sms only after bookMeeting has succeeded or after the assistant has clearly recorded a pending follow-up time.
- Send at most one SMS per call unless the caller explicitly asks you to correct the confirmation.
- Send the SMS to the live caller number. Do not ask for another phone number unless the caller says the current number is not the right one.
- Keep the SMS under 320 characters.
- Include the scheduled date and time, ticket or case number when available, and a short issue label.
- Do not include sensitive medical, financial, legal, or highly private details in SMS. Use a generic issue label instead.
- Never promise an SMS was sent unless the sms tool succeeds.
PROMPT);
    }

    private function knownCallerGuardrailsPrompt(): string
    {
        return trim(<<<'PROMPT'
[SYSTEM NOTE: RETURNING CALLER RULES]
- If your system note already tells you who the caller is, use that context immediately and do not pause to re-check it out loud.
- If your system note already tells you the caller's saved name, answer directly from that saved name and do not use lookupContact again during the same call unless the caller disputes the record.
- Only use lookupContact if the caller context you already have is missing, unclear, or the caller asks what details are on file.
- If lookupContact confirms a returning caller and recent case history would help, use lookupCase silently before asking repeated questions.
- If you know the caller's saved name, use that name naturally in the conversation and do not say you do not know their name.
- If recent case context is available, use it briefly and naturally so the caller feels recognized without sounding scripted.
- If you know the caller is returning, you may follow the opening line with one short familiar sentence such as "Nice to speak with you again, Jon." before moving into the request.
- Do not create a second assistant turn just to recognize the caller after the opening line. If you already know who they are, include that naturally in the opening line or the very next sentence and continue with the reason for the call.
- If the caller asks what name you have on file, answer directly from the saved contact context.
- Only ask the caller to spell or confirm their name if no saved contact is found or if the saved name appears incomplete.
- Never invent a caller name. If no saved contact exists, simply say you do not have a name on file yet and ask for it clearly.
- Never announce caller recognition or record checking with phrases like "Just a sec", "One moment", "Give me a moment", or similar lookup filler. Either use what you already know or do the lookup silently.
PROMPT);
    }

    private function humaneConversationGuardrailsPrompt(): string
    {
        return trim(<<<'PROMPT'
[SYSTEM NOTE: HUMANE CONVERSATION RULES]
- Sound calm, warm, and capable. Never sound rushed, clipped, annoyed, sarcastic, robotic, or overly cheerful.
- Speak at a natural human pace. Use short spoken sentences with small pauses between ideas.
- Do not interrupt the caller. Treat hesitations, breaths, and short silences as the caller still thinking.
- If the caller starts speaking while you are speaking, stop cleanly, let them finish, then respond to what they said.
- Ask one question at a time. Do not stack questions unless the caller explicitly asks for a list.
- Avoid filler before tool use, including "just a sec", "one moment", "hold on", and "let me check".
- If the caller sounds frustrated, slow down, acknowledge the issue briefly, and move one clear step forward.
- Never pressure the caller to move faster. The caller should feel heard, not managed.
PROMPT);
    }

    private function silentHandoffGuardrailsPrompt(): string
    {
        return trim(<<<'PROMPT'
[SYSTEM NOTE: SILENT HANDOFF RULES]
- If this assistant receives a caller from another tickIt operator or assistant, do not greet the caller again, do not mention a transfer, and do not make small talk.
- Continue directly with the next useful question for this assistant's task, using any route choice or caller context already present in the conversation.
PROMPT);
    }

    private function applyStartSpeakingDefaults(array $startSpeakingPlan, AssistantConfig $config): array
    {
        $startSpeakingPlan['waitSeconds'] = (float) ($startSpeakingPlan['waitSeconds'] ?? self::HUMANE_DEFAULT_WAIT_SECONDS);

        if (! isset($startSpeakingPlan['smartEndpointingPlan'])) {
            $languageCode = strtolower((string) ($config->language_code ?: 'en-US'));
            $startSpeakingPlan['smartEndpointingPlan'] = str_starts_with($languageCode, 'en')
                ? [
                    'provider' => 'livekit',
                    'waitFunction' => '340 + 3600 * x',
                ]
                : [
                    'provider' => 'vapi',
                ];
        }

        return $startSpeakingPlan;
    }

    private function applyStopSpeakingDefaults(array $stopSpeakingPlan, AssistantConfig $config): array
    {
        $stopSpeakingPlan['numWords'] = (int) ($stopSpeakingPlan['numWords'] ?? self::HUMANE_DEFAULT_INTERRUPT_WORDS);
        $stopSpeakingPlan['voiceSeconds'] = (float) ($stopSpeakingPlan['voiceSeconds'] ?? self::HUMANE_DEFAULT_VOICE_SECONDS);
        $stopSpeakingPlan['backoffSeconds'] = (float) ($stopSpeakingPlan['backoffSeconds'] ?? self::HUMANE_DEFAULT_BACKOFF_SECONDS);
        $stopSpeakingPlan['acknowledgementPhrases'] = $stopSpeakingPlan['acknowledgementPhrases'] ?? ['okay', 'got it', 'right', 'yeah', 'mm-hmm', 'uh-huh', 'understood'];
        $stopSpeakingPlan['interruptionPhrases'] = $stopSpeakingPlan['interruptionPhrases'] ?? ['stop', 'hold on', 'wait', 'actually', 'sorry', 'excuse me', 'one second'];

        return $stopSpeakingPlan;
    }

    private function normalizeHumaneTimingOverrides(?array $overrides): array
    {
        $overrides = is_array($overrides) ? $overrides : [];

        if (array_key_exists('waitSeconds', $overrides) && filled($overrides['waitSeconds'])) {
            $overrides['waitSeconds'] = $this->clampFloat(
                (float) $overrides['waitSeconds'],
                self::HUMANE_MIN_WAIT_SECONDS,
                self::HUMANE_MAX_WAIT_SECONDS,
            );
        }

        if (array_key_exists('numWords', $overrides) && filled($overrides['numWords'])) {
            $overrides['numWords'] = $this->clampInt(
                (int) $overrides['numWords'],
                self::HUMANE_MIN_INTERRUPT_WORDS,
                self::HUMANE_MAX_INTERRUPT_WORDS,
            );
        }

        if (array_key_exists('backoffSeconds', $overrides) && filled($overrides['backoffSeconds'])) {
            $overrides['backoffSeconds'] = $this->clampFloat(
                (float) $overrides['backoffSeconds'],
                self::HUMANE_MIN_BACKOFF_SECONDS,
                self::HUMANE_MAX_BACKOFF_SECONDS,
            );
        }

        return $overrides;
    }

    private function enforceHumaneStartSpeakingPlan(array $startSpeakingPlan): array
    {
        $startSpeakingPlan['waitSeconds'] = $this->clampFloat(
            (float) ($startSpeakingPlan['waitSeconds'] ?? self::HUMANE_DEFAULT_WAIT_SECONDS),
            self::HUMANE_MIN_WAIT_SECONDS,
            self::HUMANE_MAX_WAIT_SECONDS,
        );

        return $startSpeakingPlan;
    }

    private function enforceHumaneStopSpeakingPlan(array $stopSpeakingPlan): array
    {
        $stopSpeakingPlan['numWords'] = $this->clampInt(
            (int) ($stopSpeakingPlan['numWords'] ?? self::HUMANE_DEFAULT_INTERRUPT_WORDS),
            self::HUMANE_MIN_INTERRUPT_WORDS,
            self::HUMANE_MAX_INTERRUPT_WORDS,
        );
        $stopSpeakingPlan['voiceSeconds'] = $this->clampFloat(
            (float) ($stopSpeakingPlan['voiceSeconds'] ?? self::HUMANE_DEFAULT_VOICE_SECONDS),
            self::HUMANE_MIN_VOICE_SECONDS,
            self::HUMANE_MAX_VOICE_SECONDS,
        );
        $stopSpeakingPlan['backoffSeconds'] = $this->clampFloat(
            (float) ($stopSpeakingPlan['backoffSeconds'] ?? self::HUMANE_DEFAULT_BACKOFF_SECONDS),
            self::HUMANE_MIN_BACKOFF_SECONDS,
            self::HUMANE_MAX_BACKOFF_SECONDS,
        );

        return $stopSpeakingPlan;
    }

    private function humaneVoiceSpeed(float|int|string|null $speed): float
    {
        return $this->clampFloat(
            is_numeric($speed) ? (float) $speed : self::HUMANE_DEFAULT_VOICE_SPEED,
            self::HUMANE_MIN_VOICE_SPEED,
            self::HUMANE_MAX_VOICE_SPEED,
        );
    }

    private function clampFloat(float $value, float $min, float $max): float
    {
        return round(min(max($value, $min), $max), 2);
    }

    private function clampInt(int $value, int $min, int $max): int
    {
        return min(max($value, $min), $max);
    }

    private function defaultVoiceProfile(AssistantConfig $config, Workspace $workspace): array
    {
        $languageCode = RegionalPilotStackCatalog::normalizeLanguageCode(
            $config->language_code,
            $workspace->preferredLanguageCode()
        ) ?: 'en-US';
        $presetKey = AssistantPreset::normalizeKey($config->preset_key);
        $isRealtimeModel = AssistantConfig::isRealtimeModelName($config->model_name);
        $primaryMarket = $workspace->primaryMarket();
        $standardVoice = RegionalPilotStackCatalog::standardVoiceProfile($languageCode, $presetKey, $primaryMarket);

        if ($workspace->isFreePlan() && ! $workspace->bypassesPlanLimits()) {
            if ($standardVoice) {
                return $standardVoice;
            }

            return match ($presetKey) {
                'steady_operator' => ['provider' => 'vapi', 'voiceId' => 'Savannah', 'speed' => 0.98],
                'confident_closer' => ['provider' => 'vapi', 'voiceId' => 'Elliot', 'speed' => 1.0],
                'premium_concierge' => ['provider' => 'vapi', 'voiceId' => 'Clara', 'speed' => 0.98],
                default => ['provider' => 'vapi', 'voiceId' => 'Emma', 'speed' => 1.0],
            };
        }

        if ($isRealtimeModel) {
            return match ($presetKey) {
                'premium_concierge' => ['provider' => 'openai', 'voiceId' => 'shimmer', 'speed' => 1.0],
                'bright_guide' => ['provider' => 'openai', 'voiceId' => 'shimmer', 'speed' => 1.0],
                'steady_operator' => ['provider' => 'openai', 'voiceId' => 'alloy', 'speed' => 0.98],
                'confident_closer' => ['provider' => 'openai', 'voiceId' => 'alloy', 'speed' => 1.0],
                default => ['provider' => 'openai', 'voiceId' => 'shimmer', 'speed' => 1.0],
            };
        }

        if ($standardVoice) {
            return $standardVoice;
        }

        return match ($presetKey) {
            'bright_guide' => ['provider' => 'vapi', 'voiceId' => 'Emma', 'speed' => 1.0],
            'steady_operator' => ['provider' => 'vapi', 'voiceId' => 'Savannah', 'speed' => 0.98],
            'confident_closer' => ['provider' => 'vapi', 'voiceId' => 'Elliot', 'speed' => 1.0],
            'premium_concierge' => ['provider' => 'vapi', 'voiceId' => 'Clara', 'speed' => 0.98],
            default => ['provider' => 'vapi', 'voiceId' => 'Emma', 'speed' => 1.0],
        };
    }

    private function normalizePreferredVoiceSelection(
        Workspace $workspace,
        string $languageCode,
        ?string $voiceProvider,
        ?string $voiceId,
        string $modelName,
        ?string $presetKey,
    ): array {
        $voiceProvider = trim((string) $voiceProvider) !== '' ? trim((string) $voiceProvider) : null;
        $voiceId = trim((string) $voiceId) !== '' ? trim((string) $voiceId) : null;

        if ($voiceProvider === 'openai') {
            $voiceId = $this->supportsOpenAiVoice($voiceId)
                ? $voiceId
                : $this->defaultOpenAiVoiceForPreset($presetKey);

            return ['openai', $voiceId];
        }

        return [$voiceProvider, $voiceId];
    }

    private function supportsRealtimeVoice(string $provider, string $voiceId): bool
    {
        if ($provider !== 'openai') {
            return false;
        }

        return $this->supportsOpenAiVoice($voiceId);
    }

    private function supportsOpenAiVoice(?string $voiceId): bool
    {
        return in_array($voiceId, ['alloy', 'echo', 'shimmer', 'marin', 'cedar'], true);
    }

    private function defaultOpenAiVoiceForPreset(?string $presetKey): string
    {
        return in_array(AssistantPreset::normalizeKey($presetKey), ['steady_operator', 'confident_closer'], true)
            ? 'cedar'
            : 'marin';
    }

    private function buildFirstMessage(AssistantConfig $config, Workspace $workspace): string
    {
        $base = trim((string) $config->first_message);

        if ($base === '') {
            $base = RegionalPilotStackCatalog::defaultFirstMessage($config->language_code, 'support');
        } else {
            $base = $this->scriptLocalizer()->localizeOpeningLine($base, $config->language_code, [
                'workspace_name' => $workspace->name,
                'assistant_name' => $config->name,
            ]);
        }

        return rtrim($base) . '{{ knownCallerSuffix | default: "" }}';
    }

    private function scriptLocalizer(): AssistantScriptLocalizer
    {
        return $this->scriptLocalizer ?? app(AssistantScriptLocalizer::class);
    }

    private function normalizeLanguageCode(?string $languageCode, ?string $voiceId, Workspace $workspace): string
    {
        $languageCode = RegionalPilotStackCatalog::normalizeLanguageCode($languageCode);
        $voiceLanguageCode = RegionalPilotStackCatalog::languageCodeForVoiceId($voiceId);

        if ($voiceLanguageCode && $voiceLanguageCode !== 'multi') {
            return $voiceLanguageCode;
        }

        if ($languageCode) {
            return $languageCode;
        }

        return $workspace->preferredLanguageCode();
    }

    private function applyWorkspaceGuardrails(AssistantConfig $config, Workspace $workspace): void
    {
        if (! $workspace->isFreePlan() || $workspace->bypassesPlanLimits()) {
            return;
        }

        $defaultModel = AssistantConfig::DEFAULT_MODEL;
        $defaultVoice = $this->defaultVoiceProfile(
            new AssistantConfig([
                'preset_key' => $config->preset_key,
                'language_code' => $config->language_code,
                'model_name' => $defaultModel,
            ]),
            $workspace,
        );

        $config->forceFill([
            'model_name' => $defaultModel,
            'voice_provider' => $defaultVoice['provider'],
            'voice_id' => $defaultVoice['voiceId'],
        ])->save();
    }
}
