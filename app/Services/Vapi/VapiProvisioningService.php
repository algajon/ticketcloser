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
            'override_params' => $input['override_params'] ?? $config->override_params,
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
                $record->save();

                $pn = $this->createVapiNumber($record, $workspace, $assistantConfig, $areaCode);
            }
        }

        if (array_key_exists('forwarding_number', $input)) {
            $record->forwarding_number = $this->normalizeStoredPhoneNumber($input['forwarding_number']);
            $record->save();
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
            $record->save();

            return $this->vapi->createPhoneNumber($payload);
        }

        try {
            return $this->vapi->updatePhoneNumber($record->vapi_phone_number_id, $payload);
        } catch (\Illuminate\Http\Client\RequestException) {
            $this->deleteRemotePhoneNumberQuietly($record->vapi_phone_number_id);

            $record->vapi_phone_number_id = null;
            $record->e164 = null;
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

        if (! empty($config->language_code)) {
            $systemPrompt .= "\n\n[SYSTEM NOTE: Keep caller-facing replies in {$config->language_code} unless the caller clearly switches language and the business supports that change.]";
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

        if (! empty($startSpeakingPlan)) {
            $payload['startSpeakingPlan'] = $startSpeakingPlan;
        }
        if (! empty($stopSpeakingPlan)) {
            $payload['stopSpeakingPlan'] = $stopSpeakingPlan;
        }

        return $payload;
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

        $speed = (float) ($defaultVoice['speed'] ?? 1.0);

        if ($isRealtimeModel) {
            $speed = max($speed, 1.16);
        }

        return [
            'provider' => $voiceProvider,
            'voiceId' => $voiceId,
            'speed' => round($speed, 2),
        ];
    }

    private function transcriberBlock(AssistantConfig $config, Workspace $workspace): array
    {
        $languageCode = $config->language_code ?: RegionalPilotStackCatalog::defaultLanguageForMarket($workspace->primary_market ?? null);
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

        return [
            'provider' => $transcriber['provider'],
            'model' => $transcriber['model'],
            'language' => $transcriber['language'],
            'smartFormat' => true,
            'numerals' => true,
            'keyterm' => $keyterms,
            'fallbackPlan' => [
                'transcribers' => [[
                    'provider' => $transcriber['fallback']['provider'],
                    'language' => $transcriber['fallback']['language'],
                ]],
            ],
        ];
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
- Do not retry a tool after it succeeds.
- If a tool fails, explain that briefly and decide the next step with the caller instead of repeatedly calling the same tool.
- Never say "Just a sec", "One moment", or similar filler before using lookupContact or lookupCase. Do those lookups silently.
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

    private function applyStartSpeakingDefaults(array $startSpeakingPlan, AssistantConfig $config): array
    {
        $startSpeakingPlan['waitSeconds'] = (float) ($startSpeakingPlan['waitSeconds'] ?? 0.62);

        if (AssistantConfig::isRealtimeModelName($config->model_name)) {
            $startSpeakingPlan['waitSeconds'] = min($startSpeakingPlan['waitSeconds'], 0.44);
        }

        if (! isset($startSpeakingPlan['smartEndpointingPlan'])) {
            $languageCode = strtolower((string) ($config->language_code ?: 'en-US'));
            $startSpeakingPlan['smartEndpointingPlan'] = str_starts_with($languageCode, 'en')
                ? [
                    'provider' => 'livekit',
                    'waitFunction' => '240 + 2800 * x',
                ]
                : [
                    'provider' => 'vapi',
                ];
        }

        return $startSpeakingPlan;
    }

    private function applyStopSpeakingDefaults(array $stopSpeakingPlan, AssistantConfig $config): array
    {
        $stopSpeakingPlan['numWords'] = (int) ($stopSpeakingPlan['numWords'] ?? 2);
        $stopSpeakingPlan['voiceSeconds'] = (float) ($stopSpeakingPlan['voiceSeconds'] ?? 0.34);
        $stopSpeakingPlan['backoffSeconds'] = (float) ($stopSpeakingPlan['backoffSeconds'] ?? 1.05);
        $stopSpeakingPlan['acknowledgementPhrases'] = $stopSpeakingPlan['acknowledgementPhrases'] ?? ['okay', 'got it', 'right', 'understood'];
        $stopSpeakingPlan['interruptionPhrases'] = $stopSpeakingPlan['interruptionPhrases'] ?? ['stop', 'hold on', 'wait', 'one second'];

        if (AssistantConfig::isRealtimeModelName($config->model_name)) {
            $stopSpeakingPlan['numWords'] = max((int) $stopSpeakingPlan['numWords'], 3);
            $stopSpeakingPlan['voiceSeconds'] = min(max((float) $stopSpeakingPlan['voiceSeconds'], 0.34), 0.38);
            $stopSpeakingPlan['backoffSeconds'] = min(max((float) $stopSpeakingPlan['backoffSeconds'], 0.92), 1.05);
        }

        return $stopSpeakingPlan;
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
                'steady_operator' => ['provider' => 'vapi', 'voiceId' => 'Savannah', 'speed' => 1.08],
                'confident_closer' => ['provider' => 'vapi', 'voiceId' => 'Elliot', 'speed' => 1.1],
                'premium_concierge' => ['provider' => 'vapi', 'voiceId' => 'Clara', 'speed' => 1.08],
                default => ['provider' => 'vapi', 'voiceId' => 'Emma', 'speed' => 1.12],
            };
        }

        if ($isRealtimeModel) {
            return match ($presetKey) {
                'premium_concierge' => ['provider' => 'openai', 'voiceId' => 'shimmer', 'speed' => 1.22],
                'bright_guide' => ['provider' => 'openai', 'voiceId' => 'shimmer', 'speed' => 1.22],
                'steady_operator' => ['provider' => 'openai', 'voiceId' => 'alloy', 'speed' => 1.18],
                'confident_closer' => ['provider' => 'openai', 'voiceId' => 'alloy', 'speed' => 1.2],
                default => ['provider' => 'openai', 'voiceId' => 'shimmer', 'speed' => 1.2],
            };
        }

        if ($standardVoice) {
            return $standardVoice;
        }

        return match ($presetKey) {
            'bright_guide' => ['provider' => 'vapi', 'voiceId' => 'Emma', 'speed' => 1.12],
            'steady_operator' => ['provider' => 'vapi', 'voiceId' => 'Savannah', 'speed' => 1.08],
            'confident_closer' => ['provider' => 'vapi', 'voiceId' => 'Elliot', 'speed' => 1.1],
            'premium_concierge' => ['provider' => 'vapi', 'voiceId' => 'Clara', 'speed' => 1.08],
            default => ['provider' => 'vapi', 'voiceId' => 'Emma', 'speed' => 1.1],
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

        if (
            $voiceProvider !== 'openai'
            || AssistantConfig::isRealtimeModelName($modelName)
            || str_starts_with(strtolower($languageCode), 'en')
        ) {
            return [$voiceProvider, $voiceId];
        }

        $standardVoice = RegionalPilotStackCatalog::standardVoiceProfile(
            $languageCode,
            $presetKey,
            $workspace->primaryMarket(),
        );

        if (! $standardVoice || ($standardVoice['provider'] ?? null) === 'openai') {
            return [$voiceProvider, $voiceId];
        }

        return [
            $standardVoice['provider'] ?? $voiceProvider,
            $standardVoice['voiceId'] ?? $voiceId,
        ];
    }

    private function supportsRealtimeVoice(string $provider, string $voiceId): bool
    {
        if ($provider !== 'openai') {
            return false;
        }

        return in_array($voiceId, ['alloy', 'echo', 'shimmer', 'marin', 'cedar'], true);
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
