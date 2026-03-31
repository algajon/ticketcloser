<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\Meetings\MeetingBookingService;
use App\Services\Tickets\TicketCreationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VapiWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        // 1) Verify Webhook Signature
        $secret = config('services.vapi.secret');
        if ($secret && $request->header('x-vapi-secret') !== $secret) {
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

                    // Enforce one-call limit for non-paid users
                    if ($workspace->isFreePlan()) {
                        // Prevent concurrent call spam by using an atomic cache lock
                        $lockKey = "vapi_free_call_{$workspace->id}";
                        if (!\Illuminate\Support\Facades\Cache::add($lockKey, true, now()->addMinutes(10)) || $workspace->callEvents()->count() >= 1) {
                            return response()->json([
                                'assistant' => [
                                    'model' => [
                                        'provider' => 'openai',
                                        'model' => 'gpt-4o-mini',
                                        'messages' => [
                                            ['role' => 'system', 'content' => 'Tell the caller that this workspace has reached its call limit and they need to upgrade their plan, then hang up immediately.']
                                        ],
                                    ],
                                    'firstMessage' => 'We are sorry, but this workspace has reached its free call limit. Please contact the business to upgrade their account. Goodbye.',
                                ],
                            ], 200);
                        }
                    }
                    // Return the assigned assistant for this phone number
                    if ($workspacePhone->assistant && $workspacePhone->assistant->vapi_assistant_id) {
                        $assistantId = $workspacePhone->assistant->vapi_assistant_id;
                        $customerNumber = $this->resolveCustomerNumber($payload);
                        $overrides = [];

                        // The Brain: Prospecting Context Injection
                        if ($customerNumber) {
                            $phoneSearch = ltrim(preg_replace('/\D+/', '', $customerNumber), '1+');
                            if (strlen($phoneSearch) > 5) {
                                $contact = \App\Models\Contact::where('workspace_id', $workspace->id)
                                    ->where('phone_e164', 'like', "%{$phoneSearch}%")
                                    ->with(['cases' => function ($q) {
                                        $q->orderBy('created_at', 'desc')->take(3);
                                    }])
                                    ->first();

                                $memory = "[SYSTEM NOTE: IMPORTANT CALLER CONTEXT - The caller's phone number is {$customerNumber}. YOU ALREADY KNOW THEIR PHONE NUMBER AND MUST NEVER ASK THEM TO PROVIDE IT OR SPELL IT OUT. ONLY confirm it is the best number to reach them if necessary. ";
                                
                                if ($contact) {
                                    if ($contact->name) {
                                        $memory .= "They are a known returning caller named {$contact->name}. ";
                                        if ($contact->property_code || $contact->unit) {
                                            $memory .= "Their property on file is {$contact->property_code}" . ($contact->unit ? " Unit {$contact->unit}" : "") . ". ";
                                        }
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
                                $dateContext = "\n\n[SYSTEM NOTE: Today is " . now()->format('l, F j, Y') . ". The current time is " . now()->format('g:i A T') . ". Always use this exact date as your reference.]";
                                $toolRules = "\n\n[SYSTEM NOTE: TOOL EXECUTION RULES]\n- Never call createCase and bookMeeting in parallel.\n- If the caller wants both a case and a meeting, confirm the summary, call createCase once, wait for the case number, then call bookMeeting.\n- Do not retry a tool after it succeeds.\n- If a tool fails, explain that briefly instead of looping on the same tool.\n";
                                $systemPrompt = $memory . $basePrompt . $toolRules . $dateContext;

                                // Build toolIds array so Vapi doesn't strip tools when we override the model
                                $toolIds = array_filter([
                                    $workspacePhone->assistant->vapi_tool_id ?? null,
                                    $workspacePhone->assistant->vapi_booking_tool_id ?? null,
                                ]);

                                $modelOverride = [
                                    'provider' => 'openai',
                                    'model' => 'gpt-4o-mini',
                                    'messages' => [
                                        ['role' => 'system', 'content' => $systemPrompt]
                                    ],
                                ];

                                if (!empty($toolIds)) {
                                    $modelOverride['toolIds'] = array_values($toolIds);
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

                                $overrides = ['model' => $modelOverride];
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
                            'model' => 'gpt-3.5-turbo',
                            'messages' => [
                                [
                                    'role' => 'system',
                                    'content' => 'You are a placeholder assistant. Tell the user their phone number is not configured and hang up.'
                                ]
                            ]
                        ],
                        'voice' => [
                            'provider' => '11labs',
                            'voiceId' => 'bIHbv24MWmeRgasZH58o',
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
                    $errorMessage = $nameLower === 'bookmeeting'
                        ? 'Meeting booking failed'
                        : 'Ticket creation failed';

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
            [$workspace, $authError] = $this->resolveWorkspaceFromHeaders($request);

            if ($workspace) {
                \App\Jobs\ProcessEndCallReport::dispatch($workspace, $payload);
            }
            return response()->json(['ok' => true], 200);
        }

        // Anything else: acknowledge
        return response()->json(['ok' => true], 200);
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
            'createcase', 'createmaintenanceticket', 'createmortgagelead' => 0,
            'bookmeeting' => 1,
            default => 2,
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
}
