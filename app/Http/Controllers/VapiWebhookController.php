<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\AssistantConfig;
use App\Models\MessageEvent;
use App\Models\MessagingSetting;
use App\Models\SupportCase;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use App\Services\Assistants\AssistantScriptLocalizer;
use App\Services\Contacts\ContactLinkingService;
use App\Services\Meetings\MeetingBookingService;
use App\Services\Tickets\TicketCreationService;
use App\Support\RegionalPilotStackCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VapiWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // 1) Verify Webhook Signature
        $secret = (string) config('services.vapi.secret', '');
        $providedSecret = (string) $request->header('x-vapi-secret', '');

        if ($secret === '') {
            if (! app()->environment(['local', 'testing'])) {
                Log::warning('VAPI_WEBHOOK_SECRET_MISSING_ALLOWING_REQUEST', [
                    'ip' => $request->ip(),
                    'type' => data_get($payload, 'message.type'),
                ]);
            }
        } elseif ($providedSecret === '' || ! hash_equals($secret, $providedSecret)) {
            Log::warning('VAPI_WEBHOOK_UNAUTHORIZED', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $type = data_get($payload, 'message.type');

        Log::info('VAPI_IN', [
            'type' => $type,
            'path' => $request->path(),
        ]);

        // --- Assistant routing (optional; safe to keep) ---
        if ($type === 'assistant-request') {
            $phoneNumberId = data_get($payload, 'message.call.phoneNumberId');
            if ($phoneNumberId) {
                $workspacePhone = \App\Models\WorkspacePhoneNumber::with(['workspace', 'assistant'])
                    ->where('vapi_phone_number_id', $phoneNumberId)
                    ->first();

                if ($workspacePhone && $workspacePhone->workspace) {
                    $workspace = $workspacePhone->workspace;

                    if (! $workspacePhone->is_active && $workspace->bypassesPlanLimits()) {
                        $workspacePhone->forceFill(['is_active' => true])->save();
                    }

                    if (!$workspacePhone->is_active) {
                        return response()->json($this->assistantLimitPayload('This line is currently inactive. Please contact the business again later.'), 200);
                    }

                    if ($workspace->isFreePlan() && $workspace->hasReachedVoiceMinuteLimit()) {
                        if ($workspacePhone->is_active) {
                            $workspacePhone->forceFill(['is_active' => false])->save();
                        }

                        return response()->json($this->assistantLimitPayload('This workspace has reached its free voice limit. Please contact the business to upgrade their account.'), 200);
                    }
                    // Return the assigned assistant for this phone number
                    if ($workspacePhone->assistant && $workspacePhone->assistant->vapi_assistant_id) {
                        $assistantId = $workspacePhone->assistant->vapi_assistant_id;
                        $customerNumber = $this->resolveCustomerNumber($payload);
                        $overrides = [];

                        // The Brain: Prospecting Context Injection
                        if ($customerNumber) {
                            $contact = app(ContactLinkingService::class)
                                ->lookupForWorkspace($workspace, $customerNumber, null);

                            if ($contact) {
                                $contact->load(['cases' => function ($q) {
                                    $q->orderBy('created_at', 'desc')->take(3);
                                }]);
                            }

                            $memory = "[SYSTEM NOTE: IMPORTANT CALLER CONTEXT - The caller's phone number is {$customerNumber}. YOU ALREADY KNOW THEIR PHONE NUMBER AND MUST NEVER ASK THEM TO PROVIDE IT OR SPELL IT OUT. ONLY confirm it is the best number to reach them if necessary. If the caller asks what name, number, property, unit, or prior case you already have on file, answer directly using the saved details below instead of saying you do not know. Do not create a separate assistant turn just to recognize the caller or announce that you are checking records. If you already know who they are, use that naturally in the greeting or next sentence and move straight into helping them. ";
                            
                            if ($contact) {
                                if ($contact->name) {
                                    $memory .= "They are a known returning caller named {$contact->name}. ";
                                    if ($contact->property_code || $contact->unit) {
                                        $memory .= "Their property on file is {$contact->property_code}" . ($contact->unit ? " Unit {$contact->unit}" : "") . ". ";
                                    }
                                    $memory .= "If the caller asks whether you know their name or what name is on file, answer immediately that you have them as {$contact->name}. ";
                                } else {
                                    $memory .= "They are a returning caller. ";
                                }

                                if ($contact->cases && $contact->cases->count() > 0) {
                                    $memory .= "Their recent support cases are: ";
                                    foreach ($contact->cases as $c) {
                                        $memory .= "Case #{$c->case_number} ('{$c->title}', status: {$c->status}). ";
                                    }
                                    $memory .= "Please greet them warmly" . ($contact->name ? " by their name" : "") . " and briefly ask if they are calling about their recent case or if it's something new.";
                                } else {
                                    $memory .= "Please greet them warmly" . ($contact->name ? " by their name" : "") . " as a returning client.";
                                }
                            } else {
                                $memory .= "They are a first-time caller. Greet them warmly and ask how you can help them today.";
                            }
                            $memory .= " You already have the caller context that tickIt could confidently resolve before the call started. Use that context immediately instead of pausing to check records again out loud. Never say 'Just a sec', 'One moment', 'Give me a moment', 'Hold on a sec', or similar filler while checking caller identity or past case context. If you truly still need more context, call the lookup tools silently and continue naturally.";
                            $memory .= "]\n\n";

                            // Build the system prompt to override the default model messages
                            $basePrompt = $workspacePhone->assistant->system_prompt;
                            if (empty($basePrompt) && $workspacePhone->assistant->preset) {
                                $basePrompt = $workspacePhone->assistant->preset->vapi_payload_json['systemPrompt'] ?? '';
                                if ($basePrompt) {
                                    $basePrompt = str_replace('{{company_name}}', $workspace->name, $basePrompt);
                                }
                            }
                            if (empty($basePrompt)) {
                                $basePrompt = 'You are a helpful customer support assistant for ' . $workspace->name . '.';
                            }
                            $basePrompt = $this->assistantScriptLocalizer()->localizePrompt($basePrompt, $workspacePhone->assistant->language_code, [
                                'workspace_name' => $workspace->name,
                                'assistant_name' => $workspacePhone->assistant->name,
                            ]);
                            $languageGuardrail = $this->languageGuardrail($workspacePhone->assistant->language_code);
                            $operatorRoutingPrompt = $this->runtimeOperatorRoutingPrompt($workspacePhone->assistant, $workspace);
                            $operatorRoutingPrompt = $operatorRoutingPrompt !== '' ? "\n\n".$operatorRoutingPrompt : '';
                            $dateContext = "\n\n[SYSTEM NOTE: Today is " . now()->format('l, F j, Y') . ". The current time is " . now()->format('g:i A T') . ". Always use this exact date as your reference.]";
                            $smsInstruction = MessagingSetting::forWorkspace($workspace)->booking_confirmation_enabled
                                ? 'After bookMeeting succeeds, use sms at most once to send a short confirmation text to the live caller number using the workspace SMS template.'
                                : 'Do not send SMS confirmations unless the caller explicitly asks for one; workspace automatic booking confirmations are turned off.';
                            $toolRules = "\n\n[SYSTEM NOTE: TOOL EXECUTION RULES]\n- Never call createCase and bookMeeting in parallel.\n- If the caller wants both a case and a meeting, confirm the summary, call createCase once, wait for the case number, then call bookMeeting.\n- Use any caller context already provided in the system note before deciding whether to call lookupContact or lookupCase.\n- If the system note already gives you the caller's identity or recent case context, do not call lookupContact or lookupCase at the start of the call.\n- Use lookupContact only if the existing caller context is missing, unclear, or the caller asks what details are on file.\n- Use lookupCase only if recent case history would genuinely help and it was not already provided in the system note.\n- {$smsInstruction}\n- Never narrate lookupContact or lookupCase with phrases like 'Just a sec', 'One moment', 'Give me a moment', or 'Hold on a sec'.\n- Do not retry a tool after it succeeds.\n- If a tool fails, explain that briefly instead of looping on the same tool.\n";
                            $systemPrompt = $memory . $basePrompt . $toolRules . "\n\n".$this->silentHandoffGuardrailsPrompt() . "\n\n".$this->smsConfirmationGuardrailsPrompt($workspace) . $languageGuardrail . $operatorRoutingPrompt . $dateContext;

                            // Build toolIds array so Vapi doesn't strip tools when we override the model
                            $toolIds = array_filter([
                                $workspacePhone->assistant->vapi_tool_id ?? null,
                                $workspacePhone->assistant->vapi_booking_tool_id ?? null,
                                $workspacePhone->assistant->vapi_case_lookup_tool_id ?? null,
                            ]);

                            if (! $contact) {
                                $toolIds[] = $workspacePhone->assistant->vapi_lookup_tool_id ?? null;
                            }

                            $toolIds = array_values(array_filter($toolIds));

                            $selectedModel = \App\Models\AssistantConfig::normalizedModelName($workspacePhone->assistant->model_name);
                            $modelOverride = [
                                'provider' => 'openai',
                                'model' => $selectedModel,
                                'messages' => [
                                    ['role' => 'system', 'content' => $systemPrompt]
                                ],
                            ];

                            if (\App\Models\AssistantConfig::isRealtimeModelName($selectedModel)) {
                                $modelOverride['temperature'] = 0.6;
                                $modelOverride['maxTokens'] = 250;
                            }

                            if (!empty($toolIds)) {
                                $modelOverride['toolIds'] = $toolIds;
                            }

                            foreach ($this->runtimeOperatorHandoffTools($workspacePhone->assistant, $workspace) as $handoffTool) {
                                $modelOverride['tools'][] = $handoffTool;
                            }

                            if ($smsTool = $this->runtimeSmsToolForPhoneNumber($workspacePhone)) {
                                $modelOverride['tools'][] = $smsTool;
                            }

                            // Preserve the inline transferCall tool if a fallback phone is configured
                            $fallbackPhone = $workspacePhone->assistant->fallback_phone ?? null;
                            if ($fallbackPhone) {
                                $modelOverride['tools'][] = [
                                    'type' => 'transferCall',
                                    'destinations' => [[
                                        'type' => 'number',
                                        'number' => preg_replace('/[^0-9+]/', '', $fallbackPhone),
                                    ]],
                                ];
                            }

                            $overrides = [
                                'model' => $modelOverride,
                                'firstMessage' => $this->runtimeFirstMessage(
                                    $workspacePhone->assistant->first_message,
                                    $contact?->name,
                                    $workspacePhone->assistant->language_code,
                                    $workspace->name,
                                    $workspacePhone->assistant->name,
                                ),
                                'firstMessageMode' => 'assistant-speaks-first',
                            ];

                            if ($contact?->name) {
                                $knownCallerSuffix = $this->knownCallerSuffix(
                                    $contact->name,
                                    $workspacePhone->assistant->language_code,
                                );
                                $overrides['variableValues'] = [
                                    'knownCallerSuffix' => $knownCallerSuffix,
                                ];
                            }
                        }

                                $responsePayload = ['assistantId' => $assistantId];
                                if (!empty($overrides)) {
                                    $responsePayload['assistantOverrides'] = $overrides;
                                }

                                Log::info('VAPI_ASSISTANT_OVERRIDES', ['payload' => $responsePayload]);

                                return response()->json($responsePayload, 200);
                    }
                }
            }

            // Fallback
            $assistantId = config('services.vapi.default_assistant_id');

            if (!$assistantId) {
                return response()->json([
                    'assistant' => [
                        'firstMessage' => "I'm sorry, this phone number is not currently configured. Please contact the administrator.",
                        'model' => [
                            'provider' => 'openai',
                            'model' => 'gpt-4o-mini',
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => 'You are a placeholder assistant. Tell the user their phone number is not configured and hang up.'
                                ]
                            ]
                        ],
                        'voice' => [
                            'provider' => 'vapi',
                            'voiceId' => 'Emma',
                        ]
                    ]
                ], 200);
            }

            return response()->json([
                'assistantId' => $assistantId,
            ], 200);
        }

        // --- Tool calls ---
        if ($type === 'tool-calls') {
            Log::info('VAPI_TOOL_CALLS_PAYLOAD', ['payload' => $payload]);
            try {
            // ✅ Robust tool call extraction (covers toolCallList, toolCalls, toolWithToolCallList)
            $toolCalls = $this->prioritizeToolCalls($this->extractToolCalls($payload));
            $callBlock = data_get($payload, 'message.call', []);

            // Resolve workspace from headers
            [$workspace, $authError] = $this->resolveWorkspaceFromHeaders($request);

            $results = [];

            foreach ($toolCalls as $call) {
                $toolCallId = $call['id'] ?? $call['toolCallId'] ?? null;

                // Vapi tool call shape: { id, type:"function", function:{ name, arguments } }
                $fn = $call['function'] ?? [];
                $name = $fn['name'] ?? $call['name'] ?? null;
                $nameLower = strtolower($name);

                $argsRaw = $fn['arguments'] ?? $call['arguments'] ?? '{}';
                $args = $this->decodeArguments($argsRaw);

                if (!$toolCallId || !$name) {
                    continue;
                }

                // If auth/workspace failed, still return a tool result so Vapi doesn't show "No result returned"
                if (!$workspace) {
                    $results[] = $this->errorToolResult($toolCallId, $name, $authError ?? 'Unauthorized');
                    continue;
                }

                try {
                    if ($nameLower === 'createcase' || $nameLower === 'createmaintenanceticket' || $nameLower === 'createmortgagelead') {
                        $case = app(TicketCreationService::class)->createForWorkspace($workspace, [
                            ...$args,
                            'requesterPhone' => $args['requesterPhone']
                                ?? $args['requester_phone']
                                ?? $this->resolveCustomerNumber($payload),
                            'externalCallId' => $args['externalCallId']
                                ?? $args['external_call_id']
                                ?? data_get($callBlock, 'id'),
                            'source' => $args['source'] ?? 'voice',
                        ], [
                            'call' => is_array($callBlock) ? $callBlock : [],
                            'vapi_assistant_id' => data_get($callBlock, 'assistantId'),
                        ]);

                        // Send Notification to Workspace Users
                        try {
                            if ($workspace->users && $workspace->users->count() > 0) {
                                // \Illuminate\Support\Facades\Notification::send(
                                //     $workspace->users,
                                //     new \App\Notifications\NewSupportCaseNotification($case)
                                // );
                            }
                        } catch (\Throwable $e) {
                            Log::error('VAPI_NOTIFICATION_FAILED', ['error' => $e->getMessage()]);
                        }

                        $results[] = $this->successToolResult($toolCallId, $name, [
                            'caseNumber' => $case->case_number,
                            'id' => $case->id,
                        ]);
                    } elseif ($nameLower === 'lookupcontact') {
                        $contact = $this->lookupExistingContact(
                            $workspace,
                            $args['phone'] ?? $args['requesterPhone'] ?? $args['requester_phone'] ?? $this->resolveCustomerNumber($payload),
                            $args['email'] ?? $args['requesterEmail'] ?? $args['requester_email'] ?? null,
                        );

                        $results[] = $this->successToolResult($toolCallId, $name, [
                            'found' => (bool) $contact,
                            'contactId' => $contact?->id,
                            'name' => $contact?->name,
                            'phone' => $contact?->phone_e164,
                            'email' => $contact?->email,
                            'propertyCode' => $contact?->property_code,
                            'unit' => $contact?->unit,
                            'recentCaseNumbers' => $contact
                                ? $contact->cases()->latest()->limit(3)->pluck('case_number')->values()->all()
                                : [],
                        ]);
                    } elseif ($nameLower === 'lookupcase') {
                        $lookup = $this->lookupRecentCases(
                            $workspace,
                            $args['phone'] ?? $args['requesterPhone'] ?? $args['requester_phone'] ?? $this->resolveCustomerNumber($payload),
                            $args['email'] ?? $args['requesterEmail'] ?? $args['requester_email'] ?? null,
                            $args['contactId'] ?? $args['contact_id'] ?? null,
                            $args['limit'] ?? null,
                        );

                        $results[] = $this->successToolResult($toolCallId, $name, $lookup);
                    } elseif ($nameLower === 'bookmeeting') {
                        $meetingBooking = app(MeetingBookingService::class);
                        $case = $meetingBooking->resolveCase(
                            $workspace,
                            $args['caseId'] ?? $args['case_id'] ?? null
                        );

                        if (!$case) {
                            $case = $meetingBooking->resolveCaseForCall(
                                $workspace,
                                $args['externalCallId']
                                    ?? $args['external_call_id']
                                    ?? data_get($callBlock, 'id')
                            );
                        }

                        if (!$case) {
                            $results[] = $this->errorToolResult($toolCallId, $name, 'Case not found');
                            continue;
                        }

                        $booking = $meetingBooking->scheduleFromVoice($case, $args);
                        $this->queueBookingConfirmationMessage($workspace, $case, $booking, $payload, is_array($callBlock) ? $callBlock : []);

                        $results[] = $this->successToolResult($toolCallId, $name, [
                            'success' => true,
                            'booked' => $booking['booked'],
                            'message' => $booking['message'],
                            'suggestedEventId' => $booking['suggestedEvent']->id,
                            'calendarEventId' => $booking['calendarEvent']?->id,
                        ]);
                    } else {
                        $results[] = $this->errorToolResult($toolCallId, $name, "Unknown tool: {$name}");
                    }
                } catch (\Throwable $e) {
                    Log::error('VAPI_TOOL_EXCEPTION', [
                        'toolCallId' => $toolCallId,
                        'tool' => $name,
                        'error' => $e->getMessage(),
                    ]);

                    // Still return a result so Vapi doesn’t produce "No result returned"
                    $errorMessage = match ($nameLower) {
                        'bookmeeting' => 'Meeting booking failed',
                        'lookupcontact' => 'Contact lookup failed',
                        'lookupcase' => 'Case lookup failed',
                        default => 'Ticket creation failed',
                    };

                    $results[] = $this->errorToolResult($toolCallId, $name, $errorMessage);
                }
            }

            Log::info('VAPI_OUT', [
                'toolCallsCount' => count($toolCalls),
                'resultsCount' => count($results),
            ]);

            // ✅ Always return 200 and include results array
            return response()->json(['results' => $results], 200);
            } catch (\Throwable $topE) {
                Log::error('VAPI_FATAL', ['error' => $topE->getMessage(), 'file' => $topE->getFile(), 'line' => $topE->getLine(), 'trace' => $topE->getTraceAsString()]);
                $results = [];

                foreach ($this->extractToolCalls($payload) as $call) {
                    $toolCallId = $call['id'] ?? $call['toolCallId'] ?? null;
                    if (!$toolCallId) {
                        continue;
                    }

                    $name = data_get($call, 'function.name') ?? $call['name'] ?? null;
                    $results[] = $this->errorToolResult($toolCallId, $name, 'Internal tool error');
                }

                return response()->json(['results' => $results], 200);
            }
        }

        if ($type === 'end-of-call-report') {
            [$workspace, $authError] = $this->resolveWorkspaceForPayload($request, $payload);

            if ($workspace) {
                \App\Jobs\ProcessEndCallReport::dispatchSync($workspace, $payload);
            } else {
                Log::warning('VAPI_END_REPORT_WORKSPACE_RESOLVE_FAILED', [
                    'error' => $authError,
                    'callId' => data_get($payload, 'message.call.id'),
                    'phoneNumberId' => data_get($payload, 'message.call.phoneNumberId'),
                    'assistantId' => data_get($payload, 'message.call.assistantId'),
                ]);
            }
            return response()->json(['ok' => true], 200);
        }

        // Anything else: acknowledge
        return response()->json(['ok' => true], 200);
    }

    private function lookupExistingContact(Workspace $workspace, ?string $phone, ?string $email): ?Contact
    {
        $contact = app(ContactLinkingService::class)->lookupForWorkspace($workspace, $phone, $email);

        if (! $contact) {
            return null;
        }

        return $contact->fresh();
    }

    private function lookupRecentCases(
        Workspace $workspace,
        ?string $phone,
        ?string $email,
        mixed $contactId,
        mixed $limit,
    ): array {
        $contact = null;

        if (filled($contactId)) {
            $contact = Contact::query()
                ->where('workspace_id', $workspace->id)
                ->find($contactId);
        }

        if (! $contact) {
            $contact = $this->lookupExistingContact($workspace, $phone, $email);
        }

        $query = SupportCase::query()
            ->where('workspace_id', $workspace->id)
            ->latest();

        if ($contact) {
            $query->where('contact_id', $contact->id);
        } elseif (filled($phone) || filled($email)) {
            $query->where(function ($caseQuery) use ($phone, $email) {
                if (filled($phone)) {
                    $caseQuery->orWhere('requester_phone', $phone);
                }

                if (filled($email)) {
                    $caseQuery->orWhere('requester_email', $email);
                }
            });
        } else {
            return [
                'found' => false,
                'contactFound' => false,
                'contactId' => null,
                'contactName' => null,
                'cases' => [],
            ];
        }

        $normalizedLimit = max(1, min((int) ($limit ?: 3), 5));
        $cases = $query->limit($normalizedLimit)->get();

        return [
            'found' => $cases->isNotEmpty(),
            'contactFound' => (bool) $contact,
            'contactId' => $contact?->id,
            'contactName' => $contact?->name,
            'cases' => $cases->map(function (SupportCase $case) {
                return [
                    'caseNumber' => $case->case_number,
                    'title' => $case->title,
                    'status' => $case->status,
                    'priority' => $case->priority,
                    'category' => $case->category,
                    'source' => $case->source,
                    'createdAt' => optional($case->created_at)->toIso8601String(),
                    'summary' => Str::limit($this->singleLine((string) $case->description), 180),
                ];
            })->values()->all(),
        ];
    }

    private function shortContactName(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        return $parts[0] ?? trim((string) $name);
    }

    private function runtimeFirstMessage(
        ?string $baseMessage,
        ?string $contactName,
        ?string $languageCode = null,
        ?string $workspaceName = null,
        ?string $assistantName = null,
    ): string
    {
        $baseMessage = trim((string) $baseMessage);

        if ($baseMessage === '') {
            $baseMessage = $this->defaultFirstMessage($languageCode);
        } else {
            $baseMessage = $this->assistantScriptLocalizer()->localizeOpeningLine($baseMessage, $languageCode, [
                'workspace_name' => $workspaceName,
                'assistant_name' => $assistantName,
            ]);
        }

        if (! $contactName) {
            return $baseMessage;
        }

        return rtrim($baseMessage) . $this->knownCallerSuffix($contactName, $languageCode);
    }

    private function defaultFirstMessage(?string $languageCode = null): string
    {
        return RegionalPilotStackCatalog::defaultFirstMessage($languageCode, 'support');

        return match ($this->languageFamily($languageCode)) {
            'ar' => 'مرحبا، شكرا لاتصالك بالدعم. كيف يمكنني مساعدتك اليوم؟',
            'fr' => "Bonjour, merci d'avoir appele le support. Comment puis-je vous aider aujourd'hui ?",
            'es' => 'Hola, gracias por llamar al soporte. Como puedo ayudarle hoy?',
            default => 'Hi! Thanks for calling support - how can I help today?',
        };
    }

    private function knownCallerSuffix(?string $contactName, ?string $languageCode = null): string
    {
        return RegionalPilotStackCatalog::knownCallerSuffix($contactName, $languageCode);

        if (! $contactName) {
            return '';
        }

        $shortName = $this->shortContactName($contactName);

        return match ($this->languageFamily($languageCode)) {
            'ar' => ' من الجيد التحدث معك مرة أخرى يا ' . $shortName . '.',
            'fr' => ' Ravi de vous reparler, ' . $shortName . '.',
            'es' => ' Me alegra hablar contigo de nuevo, ' . $shortName . '.',
            default => ' Nice to speak with you again, ' . $shortName . '.',
        };
    }

    private function languageGuardrail(?string $languageCode = null): string
    {
        $languageCode = RegionalPilotStackCatalog::normalizeLanguageCode($languageCode);

        return $languageCode
            ? "\n\n[SYSTEM NOTE: Keep caller-facing replies in {$languageCode} unless the caller clearly switches language and the business supports that change.]"
            : '';
    }

    private function silentHandoffGuardrailsPrompt(): string
    {
        return trim(<<<'PROMPT'
[SYSTEM NOTE: SILENT HANDOFF RULES]
- If this assistant receives a caller from another tickIt operator or assistant, do not greet the caller again, do not mention a transfer, and do not make small talk.
- Continue directly with the next useful question for this assistant's task, using any route choice or caller context already present in the conversation.
PROMPT);
    }

    private function smsConfirmationGuardrailsPrompt(Workspace $workspace): string
    {
        $settings = MessagingSetting::forWorkspace($workspace);
        $enabledLine = $settings->booking_confirmation_enabled
            ? '- Automatic booking confirmation SMS is enabled for this workspace.'
            : '- Automatic booking confirmation SMS is disabled. Only send SMS if the caller clearly asks for a text confirmation.';
        $template = trim($settings->booking_confirmation_template ?: MessagingSetting::defaultTemplate());
        $signature = trim((string) $settings->signature);
        $brandVoice = MessagingSetting::BRAND_VOICES[$settings->brand_voice] ?? 'Warm and clear';
        $ticketRule = $settings->include_ticket_number
            ? '- Include the ticket or case number when available.'
            : '- Do not include a ticket or case number unless the caller specifically asks for it.';
        $issueRule = $settings->include_issue_label
            ? '- Include a short, non-sensitive issue label when available.'
            : '- Do not include the issue label; keep the message focused on the booking time.';
        $replyRule = $settings->reply_capture_enabled
            ? '- Invite simple replies only when useful, such as if the time needs to change.'
            : '- Do not invite replies; keep the SMS as a confirmation only.';

        return trim(<<<PROMPT
[SYSTEM NOTE: SMS CONFIRMATION RULES]
{$enabledLine}
- The sms tool is only for short transactional confirmations, not general chatting or marketing.
- Use sms only after bookMeeting has succeeded or after the assistant has clearly recorded a pending follow-up time.
- Send at most one SMS per call unless the caller explicitly asks you to correct the confirmation.
- Send the SMS to the live caller number. Do not ask for another phone number unless the caller says the current number is not the right one.
- Keep the SMS under 320 characters.
- Use this default workspace template as the structure: "{$template}"
- Replace placeholders naturally: {{customer_name}}, {{workspace_name}}, {{appointment_time}}, {{ticket_number}}, {{issue_label}}, and {{signature}}.
- Omit any placeholder cleanly if the value is unknown.
- Signature to use when appropriate: "{$signature}"
- Brand voice: {$brandVoice}.
- Always include the scheduled date and time.
{$ticketRule}
{$issueRule}
{$replyRule}
- Do not include sensitive medical, financial, legal, or highly private details in SMS. Use a generic issue label instead.
- Never promise an SMS was sent unless the sms tool succeeds.
PROMPT);
    }

    private function queueBookingConfirmationMessage(
        Workspace $workspace,
        SupportCase $case,
        array $booking,
        array $payload,
        array $callBlock
    ): void {
        try {
            $settings = MessagingSetting::forWorkspace($workspace);

            if (! $settings->booking_confirmation_enabled) {
                return;
            }

            $suggestedEvent = $booking['suggestedEvent'] ?? null;

            if (! $suggestedEvent || ! isset($suggestedEvent->id)) {
                return;
            }

            $case->loadMissing(['contact']);

            $assistantId = data_get($callBlock, 'assistantId');
            $assistantConfig = $case->assistantConfig;

            if (! $assistantConfig && filled($assistantId)) {
                $assistantConfig = AssistantConfig::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('vapi_assistant_id', $assistantId)
                    ->first();
            }

            $toPhone = $this->resolveCustomerNumber($payload) ?: $case->requester_phone ?: $case->contact?->phone_e164;
            $fromPhone = $assistantConfig
                ? WorkspacePhoneNumber::query()
                    ->where('workspace_id', $workspace->id)
                    ->where('assistant_id', $assistantConfig->id)
                    ->where('is_active', true)
                    ->latest('id')
                    ->value('e164')
                : null;

            $startsAt = $booking['startsAt'] ?? $suggestedEvent->starts_at ?? null;
            $appointmentTime = $startsAt
                ? $startsAt->copy()->timezone($suggestedEvent->timezone ?: $workspace->default_timezone ?: config('app.timezone'))->format('D, M j \a\t g:i A')
                : 'the scheduled time';

            $body = $settings->renderPreview([
                'customer_name' => $this->shortContactName($case->contact?->name) ?: 'there',
                'workspace_name' => $workspace->name,
                'appointment_time' => $appointmentTime,
                'ticket_number' => $settings->include_ticket_number ? 'Ticket '.$case->case_number.'.' : '',
                'issue_label' => $settings->include_issue_label ? $this->singleLine((string) ($case->title ?: $case->category ?: 'Follow-up')).'.' : '',
                'signature' => $settings->signature ?: '',
            ]);

            $callId = data_get($callBlock, 'id') ?: ($case->external_call_id ?: 'suggested');
            $externalMessageId = 'booking-confirmation:'.$callId.':'.$suggestedEvent->id;

            MessageEvent::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'provider' => 'vapi',
                    'external_message_id' => $externalMessageId,
                ],
                [
                    'assistant_config_id' => $assistantConfig?->id,
                    'contact_id' => $case->contact_id,
                    'support_case_id' => $case->id,
                    'calendar_event_id' => data_get($booking, 'calendarEvent.id'),
                    'channel' => 'sms',
                    'direction' => MessageEvent::DIRECTION_OUTBOUND,
                    'status' => MessageEvent::STATUS_QUEUED,
                    'from_phone' => is_scalar($fromPhone) ? (string) $fromPhone : null,
                    'to_phone' => $toPhone,
                    'body' => $body,
                    'metadata' => [
                        'source' => 'bookMeeting',
                        'vapi_call_id' => data_get($callBlock, 'id') ?: $case->external_call_id,
                        'suggested_event_id' => $suggestedEvent->id,
                        'booking_status' => $booking['booked'] ? 'booked' : 'pending',
                    ],
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('VAPI_MESSAGE_EVENT_QUEUE_FAILED', [
                'workspace_id' => $workspace->id,
                'case_id' => $case->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function runtimeSmsToolForPhoneNumber(\App\Models\WorkspacePhoneNumber $phoneNumber): ?array
    {
        if (! $phoneNumber->is_active || blank($phoneNumber->vapi_phone_number_id)) {
            return null;
        }

        $from = $this->normalizeSmsPhoneNumber($phoneNumber->e164);

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

    private function runtimeOperatorHandoffTools(\App\Models\AssistantConfig $assistant, Workspace $workspace): array
    {
        if (! $this->runtimeOperatorEnabled($assistant)) {
            return [];
        }

        $routes = $this->runtimeOperatorRoutes($assistant);
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

        $destinationsByAssistant = \App\Models\AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', $assistantIds)
            ->whereNotNull('vapi_assistant_id')
            ->get(['id', 'name', 'language_code', 'vapi_assistant_id'])
            ->keyBy('id');

        $tools = [];

        foreach ($routes as $route) {
            $destinationAssistant = $destinationsByAssistant->get((int) ($route['assistant_id'] ?? 0));

            if (! $destinationAssistant || blank($destinationAssistant->vapi_assistant_id)) {
                continue;
            }

            $descriptionParts = array_filter([
                trim((string) ($route['label'] ?? '')),
                filled($route['keywords'] ?? null) ? 'caller may say: '.trim((string) $route['keywords']) : null,
                filled($route['language_code'] ?? null) ? 'language: '.trim((string) $route['language_code']) : null,
                'destination assistant: '.$destinationAssistant->name,
            ]);

            $destination = [
                'type' => 'assistant',
                'assistantId' => $destinationAssistant->vapi_assistant_id,
                'description' => implode('; ', $descriptionParts),
                'contextEngineeringPlan' => [
                    'type' => 'userAndAssistantMessages',
                ],
            ];

            $label = trim((string) ($route['label'] ?? $destinationAssistant->name));
            $label = $label !== '' ? $label : $destinationAssistant->name;
            $keywords = trim((string) ($route['keywords'] ?? ''));
            $phraseHint = $keywords !== ''
                ? " Caller phrases for this route: {$keywords}."
                : '';
            $functionName = $this->runtimeOperatorHandoffFunctionName($label, $destinationAssistant->name);

            $tools[] = [
                'type' => 'handoff',
                'function' => [
                    'name' => $functionName,
                    'description' => "Silently hand off the call to {$label} ({$destinationAssistant->name}). Use this when the caller says {$label} or any configured phrase for this route.{$phraseHint}",
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

    private function runtimeOperatorRoutingPrompt(\App\Models\AssistantConfig $assistant, Workspace $workspace): string
    {
        if (! $this->runtimeOperatorEnabled($assistant)) {
            return '';
        }

        $routes = $this->runtimeOperatorRoutes($assistant);
        $liveAssistantIds = \App\Models\AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->whereIn('id', collect($routes)->pluck('assistant_id')->filter()->all())
            ->whereNotNull('vapi_assistant_id')
            ->pluck('vapi_assistant_id', 'id');

        $destinationNames = \App\Models\AssistantConfig::query()
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
                $functionName = $this->runtimeOperatorHandoffFunctionName($label, $destinationName);

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

        $intro = trim((string) data_get($assistant->intake_params ?? [], 'operator.intro', ''));
        if ($intro === '') {
            $intro = "Thanks for calling {$workspace->name}. Tell me which team or language you need, and I will connect you.";
        }
        $fallback = $this->runtimeOperatorFallbackMessage($assistant);

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

    private function runtimeOperatorEnabled(\App\Models\AssistantConfig $assistant): bool
    {
        return filter_var(data_get($assistant->intake_params ?? [], 'operator.enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function runtimeOperatorRoutes(\App\Models\AssistantConfig $assistant): array
    {
        $routes = data_get($assistant->intake_params ?? [], 'operator.routes', []);

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

    private function runtimeOperatorHandoffFunctionName(string $label, string $assistantName): string
    {
        $source = $assistantName !== '' ? $assistantName : $label;
        $slug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '_', $source));
        $slug = trim((string) preg_replace('/_+/', '_', $slug), '_');

        return 'handoff_to_'.($slug !== '' ? $slug : 'assistant');
    }

    private function runtimeOperatorFallbackMessage(\App\Models\AssistantConfig $assistant): string
    {
        $fallback = trim((string) data_get($assistant->intake_params ?? [], 'operator.fallback_message', ''));

        return $fallback !== ''
            ? $fallback
            : 'I can help route the call, but I need one more detail. Which team should I connect you with?';
    }

    private function languageFamily(?string $languageCode = null): string
    {
        $languageCode = strtolower((string) RegionalPilotStackCatalog::normalizeLanguageCode($languageCode));

        return explode('-', $languageCode)[0] ?: 'en';
    }

    private function assistantScriptLocalizer(): AssistantScriptLocalizer
    {
        return app(AssistantScriptLocalizer::class);
    }

    /**
     * ✅ NEW: Extract tool calls from all known Vapi shapes.
     */
    private function extractToolCalls(array $payload): array
    {
        $message = data_get($payload, 'message', []);

        // Most common
        $toolCallList = $message['toolCallList'] ?? null;
        if (is_array($toolCallList) && count($toolCallList)) {
            return $toolCallList;
        }

        $toolCalls = $message['toolCalls'] ?? null;
        if (is_array($toolCalls) && count($toolCalls)) {
            return $toolCalls;
        }

        // Also common in Vapi logs you posted earlier
        $toolWithToolCallList = $message['toolWithToolCallList'] ?? null;
        if (is_array($toolWithToolCallList) && count($toolWithToolCallList)) {
            $out = [];
            foreach ($toolWithToolCallList as $item) {
                if (isset($item['toolCall']) && is_array($item['toolCall'])) {
                    $out[] = $item['toolCall'];
                }
            }
            if (count($out))
                return $out;
        }

        return [];
    }

    private function prioritizeToolCalls(array $toolCalls): array
    {
        usort($toolCalls, function (array $left, array $right): int {
            return $this->toolPriority($left) <=> $this->toolPriority($right);
        });

        return $toolCalls;
    }

    private function toolPriority(array $toolCall): int
    {
        $name = strtolower((string) (data_get($toolCall, 'function.name') ?? $toolCall['name'] ?? ''));

        return match ($name) {
            'lookupcontact', 'lookupcase' => 0,
            'createcase', 'createmaintenanceticket', 'createmortgagelead' => 1,
            'bookmeeting' => 2,
            default => 3,
        };
    }

    /**
     * Decode Vapi tool arguments. Can be:
     * - array/object
     * - JSON string
     * - empty string
     */
    private function decodeArguments($argsRaw): array
    {
        if (is_array($argsRaw)) {
            return $argsRaw;
        }

        if (is_string($argsRaw)) {
            $argsRaw = trim($argsRaw);
            if ($argsRaw === '' || $argsRaw === '{}')
                return [];

            $decoded = json_decode($argsRaw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function resolveCustomerNumber(array $payload): ?string
    {
        $value = data_get($payload, 'message.call.customer.number')
            ?? data_get($payload, 'message.customer.number')
            ?? data_get($payload, 'message.call.customer.phoneNumber')
            ?? data_get($payload, 'message.call.from');

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function assistantLimitPayload(string $message): array
    {
        return [
            'assistant' => [
                'model' => [
                    'provider' => 'openai',
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tell the caller the line is unavailable, advise them to contact the business directly, and keep it to one short sentence.'],
                    ],
                ],
                'firstMessage' => $message,
            ],
        ];
    }

    private function successToolResult(string $toolCallId, ?string $name, array $payload): array
    {
        $result = [
            'toolCallId' => $toolCallId,
            'result' => $this->encodeToolPayload($payload),
        ];

        if ($name) {
            $result['name'] = $name;
        }

        return $result;
    }

    private function errorToolResult(string $toolCallId, ?string $name, string $message): array
    {
        $result = [
            'toolCallId' => $toolCallId,
            'error' => $this->singleLine($message),
        ];

        if ($name) {
            $result['name'] = $name;
        }

        return $result;
    }

    private function encodeToolPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

        return $json === false ? '{}' : $this->singleLine($json);
    }

    private function singleLine(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    /**
     * Resolve workspace using:
     * - X-Workspace-Slug header
     * - Authorization: Bearer <integration_token>
     */
    private function resolveWorkspaceFromHeaders(Request $request): array
    {
        $slug = $request->header('X-Workspace-Slug');
        $auth = $request->header('Authorization');

        if (!$slug) {
            return [null, 'Missing X-Workspace-Slug header'];
        }

        if (!$auth) {
            return [null, 'Missing Authorization header'];
        }

        // Accept "Bearer xxx" or raw token
        $token = trim($auth);
        if (str_starts_with(strtolower($token), 'bearer ')) {
            $token = trim(substr($token, 7));
        }

        if ($token === '') {
            return [null, 'Empty token'];
        }

        $workspace = Workspace::where('slug', $slug)->first();
        if (!$workspace) {
            return [null, 'Workspace not found'];
        }

        if (!$workspace->integration_token) {
            return [null, 'Workspace integration token not set'];
        }

        if (!hash_equals($workspace->integration_token, $token)) {
            return [null, 'Invalid token'];
        }

        // Handy if you want to reuse it elsewhere
        $request->attributes->set('workspace', $workspace);

        return [$workspace, null];
    }

    private function resolveWorkspaceForPayload(Request $request, array $payload): array
    {
        [$workspace, $authError] = $this->resolveWorkspaceFromHeaders($request);

        if ($workspace) {
            return [$workspace, null];
        }

        $phoneNumberId = data_get($payload, 'message.call.phoneNumberId')
            ?? data_get($payload, 'message.call.phoneNumber.id')
            ?? data_get($payload, 'message.artifact.variableValues.phoneNumber.id');

        if (is_string($phoneNumberId) && $phoneNumberId !== '') {
            $workspacePhone = \App\Models\WorkspacePhoneNumber::query()
                ->with('workspace')
                ->where('vapi_phone_number_id', $phoneNumberId)
                ->first();

            if ($workspacePhone?->workspace) {
                $request->attributes->set('workspace', $workspacePhone->workspace);

                return [$workspacePhone->workspace, null];
            }
        }

        $assistantId = data_get($payload, 'message.call.assistantId')
            ?? data_get($payload, 'message.artifact.variableValues.phoneNumber.assistantId');

        if (is_string($assistantId) && $assistantId !== '') {
            $assistant = \App\Models\AssistantConfig::query()
                ->with('workspace')
                ->where('vapi_assistant_id', $assistantId)
                ->first();

            if ($assistant?->workspace) {
                $request->attributes->set('workspace', $assistant->workspace);

                return [$assistant->workspace, null];
            }
        }

        return [null, $authError ?? 'Workspace could not be resolved from payload'];
    }
}
