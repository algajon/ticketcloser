<?php

namespace App\Services\Vapi;

use App\Models\AssistantConfig;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;

class VapiProvisioningService
{
    public function __construct(
        private readonly VapiClient $vapi,
    ) {
    }

    public function provisionAssistantAndTool(Workspace $workspace, array $input): AssistantConfig
    {
        // Backwards compatible: find-or-create the default assistant and delegate
        $config = AssistantConfig::firstOrCreate(
            ['workspace_id' => $workspace->id, 'name' => ($input['name'] ?? 'Ticketcloser Assistant')],
            ['name' => $input['name'] ?? 'Ticketcloser Assistant']
        );

        return $this->provisionAssistantAndToolForConfig($config, $workspace, $input);
    }

    /**
     * Provision (or update) a specific AssistantConfig and its tool in Vapi.
     */
    public function provisionAssistantAndToolForConfig(AssistantConfig $config, Workspace $workspace, array $input): AssistantConfig
    {
        $config->fill([
            'name' => $input['name'] ?? $config->name,
            'system_prompt' => $input['system_prompt'] ?? $config->system_prompt,
            'voice_provider' => $input['voice_provider'] ?? $config->voice_provider,
            'voice_id' => $input['voice_id'] ?? $config->voice_id,
            'preset_key' => $input['preset_key'] ?? $config->preset_key,
            'override_params' => $input['override_params'] ?? $config->override_params,
            'intake_params' => $input['intake_params'] ?? $config->intake_params,
            'is_active' => $input['is_active'] ?? true,
        ])->save();

        $integrationToken = $workspace->integration_token
            ?? throw new \RuntimeException('Workspace integration token not found.');

        $toolPayload = $this->buildCreateCaseToolPayload(
            workspaceSlug: $workspace->slug,
            integrationToken: $integrationToken
        );

        // 1) Tool (idempotent)
        if ($config->vapi_tool_id) {
            $updatePayload = $toolPayload;
            unset($updatePayload['type']);
            $this->vapi->updateTool($config->vapi_tool_id, $updatePayload);
        } else {
            $tool = $this->vapi->createTool($toolPayload);
            $config->vapi_tool_id = $tool['id'] ?? null;
            $config->save();
        }

        // 1.5) Booking Tool (idempotent)
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

        // 2) Assistant (idempotent)
        $assistantPayload = $this->buildAssistantPayload($config, $config->vapi_tool_id, $config->vapi_booking_tool_id, $workspace);

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
        $assistantConfig = null;
        if (!empty($input['assistant_id'])) {
            $assistantConfig = AssistantConfig::where('workspace_id', $workspace->id)->where('id', $input['assistant_id'])->firstOrFail();
        } else {
            $assistantConfig = AssistantConfig::where('workspace_id', $workspace->id)->firstOrFail();
        }

        if (!$assistantConfig->vapi_assistant_id) {
            throw new \RuntimeException('You must sync the assistant before provisioning a phone number.');
        }

        $record = WorkspacePhoneNumber::firstOrCreate([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistantConfig->id
        ]);
        $record->is_active = true;

        $areaCode = isset($input['area_code'])
            ? preg_replace('/\D+/', '', (string) $input['area_code'])
            : null;

        // Step 1: Create (or skip if already provisioned in Vapi)
        if (!$record->vapi_phone_number_id) {
            $pn = $this->createVapiNumber($record, $workspace, $assistantConfig, $areaCode);
        } else {
            // Try to update the existing Vapi number. If Vapi rejects it
            // (e.g. the number was never fully provisioned and has broken state),
            // delete the orphan in Vapi + reset the DB record, then create fresh.
            try {
                $pn = $this->vapi->updatePhoneNumber($record->vapi_phone_number_id, [
                    'name' => $workspace->name . ' Support',
                    'serverUrl' => config('services.vapi.webhook_url'),
                ]);
                $record->e164 = $pn['number'] ?? $pn['sipUri'] ?? $record->e164;
                $record->assistant_id = $assistantConfig->id;
                $record->save();
            } catch (\Illuminate\Http\Client\RequestException $e) {
                // Broken/orphaned Vapi record — nuke it and start fresh
                try {
                    $this->vapi->deletePhoneNumber($record->vapi_phone_number_id);
                } catch (\Throwable) {
                    // Ignore 404 — already gone from Vapi side
                }
                $record->vapi_phone_number_id = null;
                $record->e164 = null;
                $record->save();

                $pn = $this->createVapiNumber($record, $workspace, $assistantConfig, $areaCode);
            }
        }

        if (array_key_exists('forwarding_number', $input)) {
            $record->forwarding_number = $input['forwarding_number'];
            $record->save();
        }

        return $record->fresh();
    }

    /** @throws \Illuminate\Http\Client\RequestException */
    private function createVapiNumber(
        WorkspacePhoneNumber $record,
        Workspace $workspace,
        AssistantConfig $assistantConfig,
        ?string $areaCode
    ): array {
        $payload = [
            'provider' => 'vapi',
            'name' => $workspace->name . ' Support',
            'serverUrl' => config('services.vapi.webhook_url'),
        ];

        if ($areaCode && strlen($areaCode) === 3) {
            $payload['numberDesiredAreaCode'] = $areaCode;
        }

        $pn = $this->vapi->createPhoneNumber($payload);
        $record->vapi_phone_number_id = $pn['id'] ?? null;
        $record->e164 = $pn['number'] ?? $pn['sipUri'] ?? null;
        $record->assistant_id = $assistantConfig->id;
        $record->save();
        return $pn;
    }

    private function buildCreateCaseToolPayload(string $workspaceSlug, string $integrationToken): array
    {
        $webhookUrl = config('services.vapi.webhook_url');

        return [
            'type' => 'function',
            'function' => [
                'name' => 'createCase',
                'description' => 'Create a support case in Ticketcloser and return the case number to read back to the caller.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'Short issue title (one sentence)'],
                        'description' => ['type' => 'string', 'description' => 'Full description of the issue as reported by the caller'],
                        'category' => ['type' => 'string', 'description' => 'Category: billing, shipping, warranty, account, or other'],
                        'priority' => ['type' => 'string', 'description' => 'Priority: low, normal, high, or critical'],
                        'requesterPhone' => ['type' => 'string', 'description' => 'Caller phone number in E.164 format, e.g. +14155550100'],
                        'externalCallId' => ['type' => 'string', 'description' => 'The Vapi call ID for traceability'],
                    ],
                    'required' => ['title', 'description', 'requesterPhone', 'externalCallId'],
                ],
            ],
            'server' => [
                'url' => $webhookUrl,
                'headers' => [
                    'Authorization' => 'Bearer ' . $integrationToken,
                    'X-Workspace-Slug' => $workspaceSlug,
                ],
            ],
        ];
    }

    private function buildBookMeetingToolPayload(string $workspaceSlug, string $integrationToken): array
    {
        $webhookUrl = config('services.vapi.webhook_url');

        return [
            'type' => 'function',
            'function' => [
                'name' => 'bookMeeting',
                'description' => 'Book a follow-up meeting with the support team on the calendar directly. Use this when the caller wants to schedule a follow-up call or meeting.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'caseId' => ['type' => 'string', 'description' => 'The Case Number returned by the createCase function.'],
                        'dateTime' => ['type' => 'string', 'description' => 'The requested datetime in ISO 8601 format (e.g., 2026-03-05T14:00:00Z)'],
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
        ];
    }

    private function buildAssistantPayload(AssistantConfig $config, ?string $toolId, ?string $bookingToolId, Workspace $workspace): array
    {
        $presetData = [];
        if ($config->preset_key) {
            $preset = \App\Models\AssistantPreset::where('key', $config->preset_key)->first();
            if ($preset && $preset->vapi_payload_json) {
                $presetData = $preset->vapi_payload_json;
            }
        }

        $systemPrompt = $config->system_prompt ?: ($presetData['systemPrompt'] ?? $this->defaultPromptTemplate());
        $systemPrompt = str_replace('{{company_name}}', $workspace->name, $systemPrompt);

        // Inject the current date so the LLM understands relative dates like "tomorrow"
        $dateContext = "\n\n[SYSTEM NOTE: Today is " . now()->format('l, F j, Y') . ". The current time is " . now()->format('g:i A T') . ". Always use this exact date as your reference when scheduling meetings or calculating relative times.]";
        $systemPrompt .= $dateContext;

        // IMPORTANT: toolIds must live inside the 'model' object per Vapi API spec.
        // Placing it at the top level causes Vapi to reject the assistant creation.
        $model = [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
            ],
        ];

        if ($toolId) {
            $model['toolIds'][] = $toolId;
        }
        if ($bookingToolId) {
            $model['toolIds'][] = $bookingToolId;
        }

        if (!empty($config->fallback_phone)) {
             $model['tools'][] = [
                 'type' => 'transferCall',
                 'destinations' => [
                     [
                         'type' => 'number',
                         'number' => preg_replace('/[^0-9+]/', '', $config->fallback_phone),
                     ]
                 ]
             ];
             // Append to system prompt to instruct the AI to use it
             $model['messages'][0]['content'] .= "\n\n[SYSTEM NOTE: YOUR MOST IMPORTANT EMERGENCY RULE! If the user becomes exceptionally frustrated, aggressively demands a human/manager, or reports a high-severity emergency, you MUST immediately execute the transferCall tool to route them to the live escalation team. Do not attempt to resolve the issue yourself in these scenarios.]";
        }

        $payload = [
            'name' => $config->name,
            'model' => $model,
            'firstMessage' => $presetData['firstMessage'] ?? 'Hi! Thanks for calling support — how can I help today?',
        ];

        $voice = $this->voiceBlock($config);
        if ($voice) {
            $payload['voice'] = $voice;
        }

        // Apply talking plans
        $startSpeakingPlan = $presetData['startSpeakingPlan'] ?? [];
        $stopSpeakingPlan = $presetData['stopSpeakingPlan'] ?? [];

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

        if (!empty($startSpeakingPlan)) {
            $payload['startSpeakingPlan'] = $startSpeakingPlan;
        }
        if (!empty($stopSpeakingPlan)) {
            $payload['stopSpeakingPlan'] = $stopSpeakingPlan;
        }

        return $payload;
    }

    private function voiceBlock(AssistantConfig $config): ?array
    {
        if (!$config->voice_provider || !$config->voice_id) {
            return null;
        }

        return [
            'provider' => $config->voice_provider,
            'voiceId' => $config->voice_id,
        ];
    }

    private function defaultPromptTemplate(): string
    {
        return trim(<<<'PROMPT'
You are a support phone agent for this business.

Goals:
1) Understand the customer issue.
2) If the caller's phone number linked to the account is not clear, ask for it (E.164 format, +1...).
3) Ask at most 1–2 clarifying questions if necessary.
4) Determine category and priority.
5) Read back a short summary and ask for confirmation.
6) ONLY after confirmation, call createCase with:
   title, description, category, priority, requesterPhone, externalCallId
7) After createCase returns a case number, tell the caller the case number and that support will follow up.
8) Specifically ask if the caller would like to book a follow-up meeting. If they say yes, identify a time they want (e.g. "tomorrow at 2pm") and call the bookMeeting tool with the case number and the date/time. Tell them we look forward to the meeting.

Be concise and helpful.
PROMPT);
    }
}