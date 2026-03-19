<?php

namespace App\Http\Controllers;

use App\Models\SupportCase;
use App\Models\Workspace;
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
                $workspacePhone = \App\Models\WorkspacePhoneNumber::with(['workspace', 'assistantConfig'])
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
                    if ($workspacePhone->assistantConfig && $workspacePhone->assistantConfig->vapi_assistant_id) {
                        return response()->json([
                            'assistantId' => $workspacePhone->assistantConfig->vapi_assistant_id,
                        ], 200);
                    }
                }
            }

            // Fallback
            $assistantId = config('services.vapi.default_assistant_id');

            return response()->json([
                'assistantId' => $assistantId,
            ], 200);
        }

        // --- Tool calls ---
        if ($type === 'tool-calls') {
            // ✅ Robust tool call extraction (covers toolCallList, toolCalls, toolWithToolCallList)
            $toolCalls = $this->extractToolCalls($payload);

            // Resolve workspace from headers
            [$workspace, $authError] = $this->resolveWorkspaceFromHeaders($request);

            $results = [];

            foreach ($toolCalls as $call) {
                $toolCallId = $call['id'] ?? null;

                // Vapi tool call shape: { id, type:"function", function:{ name, arguments } }
                $fn = $call['function'] ?? [];
                $name = $fn['name'] ?? $call['name'] ?? null;

                $argsRaw = $fn['arguments'] ?? $call['arguments'] ?? '{}';
                $args = $this->decodeArguments($argsRaw);

                if (!$toolCallId || !$name) {
                    continue;
                }

                // If auth/workspace failed, still return a tool result so Vapi doesn't show "No result returned"
                if (!$workspace) {
                    $results[] = [
                        'toolCallId' => $toolCallId,
                        'name' => $name,
                        'result' => json_encode(['error' => $authError ?? 'Unauthorized']),
                    ];
                    continue;
                }

                try {
                    if ($name === 'createCase' || $name === 'createMaintenanceTicket' || $name === 'createMortgageLead') {
                        // IMPORTANT: Assign properties directly to avoid mass-assignment issues
                        $case = new SupportCase();
                        $case->workspace_id = $workspace->id;
                        $case->case_number = 'TC-' . strtoupper(Str::random(8));
                        $case->title = $args['title'] ?? 'New ticket';
                        $case->description = $args['description'] ?? '';
                        $case->category = $args['category'] ?? 'general';
                        $case->priority = $args['priority'] ?? 'normal';
                        $case->status = 'new';
                        $case->requester_phone = $args['requesterPhone'] ?? null;
                        $case->requester_email = $args['requesterEmail'] ?? null;

                        // Prefer explicitly passed externalCallId, otherwise use Vapi call id
                        $case->external_call_id = $args['externalCallId'] ?? data_get($payload, 'message.call.id');

                        $case->source = $args['source'] ?? 'voice';

                        // Queue resolution: accept explicit queue name in args, or fallback to 'support'
                        $queueName = $args['queue'] ?? null;
                        if ($queueName) {
                            $queue = \App\Models\Queue::where('workspace_id', $workspace->id)->where('name', $queueName)->first();
                            if ($queue)
                                $case->queue_id = $queue->id;
                        }

                        // Store structured payload if provided
                        if (isset($args['structuredPayload']) && is_array($args['structuredPayload'])) {
                            $case->structured_payload = $args['structuredPayload'];
                        }

                        // Lookup assistant config by vapi_assistant_id
                        $vapiAssistantId = data_get($payload, 'message.call.assistantId');
                        if ($vapiAssistantId) {
                            $assistantConfig = \App\Models\AssistantConfig::where('workspace_id', $workspace->id)
                                ->where('vapi_assistant_id', $vapiAssistantId)
                                ->first();
                            if ($assistantConfig) {
                                $case->assistant_config_id = $assistantConfig->id;
                            }
                        }

                        // Save case first so we have an ID
                        $case->save();

                        // Link or create contact by phone
                        if ($case->requester_phone) {
                            $phone = preg_replace('/\D+/', '', $case->requester_phone);
                            $match = \App\Models\Contact::where('workspace_id', $workspace->id)
                                ->where('phone_e164', 'like', "%{$phone}%")
                                ->first();
                            if (!$match) {
                                $match = \App\Models\Contact::create([
                                    'workspace_id' => $workspace->id,
                                    'phone_e164' => $case->requester_phone,
                                    'name' => $args['requesterName'] ?? null,
                                    'email' => $case->requester_email ?? null,
                                ]);
                            }
                            if ($match) {
                                $case->contact_id = $match->id;
                                $case->save();
                            }
                        }

                        // If Vapi provided call-level transcript or recording in payload, attach
                        $callBlock = data_get($payload, 'message.call');
                        if (is_array($callBlock)) {
                            if (isset($callBlock['transcript'])) {
                                $case->transcript = is_string($callBlock['transcript']) ? $callBlock['transcript'] : json_encode($callBlock['transcript']);
                            }
                            if (isset($callBlock['recordingUrl'])) {
                                $case->recording_url = $callBlock['recordingUrl'];
                            }
                            if ($case->transcript || $case->recording_url) {
                                $case->save();
                            }

                            // Create a call_event record for audit
                            try {
                                \App\Models\CallEvent::create([
                                    'workspace_id' => $workspace->id,
                                    'queue_id' => $case->queue_id,
                                    'vapi_call_id' => data_get($payload, 'message.call.id'),
                                    'from_number' => data_get($callBlock, 'from'),
                                    'to_number' => data_get($callBlock, 'to'),
                                    'duration_seconds' => data_get($callBlock, 'durationSeconds'),
                                    'cost' => data_get($callBlock, 'cost'),
                                    'transcript' => $case->transcript,
                                    'recording_url' => $case->recording_url,
                                    'meta' => $callBlock,
                                ]);
                            } catch (\Throwable) {
                                // ignore failure to record call event
                            }
                        }

                        // ✅ Vapi requires result to be a STRING
                        $results[] = [
                            'toolCallId' => $toolCallId,
                            'name' => $name,
                            'result' => json_encode([
                                'caseNumber' => $case->case_number,
                                'id' => $case->id,
                            ]),
                        ];
                    } elseif ($name === 'bookMeeting') {
                        $caseIdParam = $args['caseId'] ?? '';
                        $case = SupportCase::where('workspace_id', $workspace->id)
                            ->where(function ($q) use ($caseIdParam) {
                                $q->where('case_number', $caseIdParam)
                                    ->orWhere('id', $caseIdParam);
                            })
                            ->first();

                        if (!$case) {
                            $results[] = [
                                'toolCallId' => $toolCallId,
                                'name' => $name,
                                'result' => json_encode(['error' => 'Case not found']),
                            ];
                            continue;
                        }

                        try {
                            $startsAt = \Carbon\Carbon::parse($args['dateTime'] ?? now()->addDay());
                        } catch (\Exception) {
                            $startsAt = now()->addDay()->setHour(14)->setMinute(0);
                        }
                        $endsAt = $startsAt->copy()->addMinutes(30);

                        $suggested = \App\Models\SuggestedEvent::create([
                            'workspace_id' => $workspace->id,
                            'case_id' => $case->id,
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                            'timezone' => 'UTC',
                            'status' => 'pending',
                        ]);

                        $conn = \App\Models\CalendarConnection::where('workspace_id', $workspace->id)
                            ->where('provider', 'google')
                            ->first();

                        $url = null;
                        if ($conn && isset($conn->tokens['access_token'])) {
                            $summary = "Support Follow-up: Case #{$case->case_number}";
                            $response = \Illuminate\Support\Facades\Http::withToken($conn->tokens['access_token'])
                                ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                                    'summary' => $summary,
                                    'start' => ['dateTime' => $startsAt->toRfc3339String(), 'timeZone' => 'UTC'],
                                    'end' => ['dateTime' => $endsAt->toRfc3339String(), 'timeZone' => 'UTC'],
                                    'description' => "Discussing case {$case->case_number}",
                                ]);

                            if ($response->ok()) {
                                $url = $response->json('htmlLink');
                            }
                        }

                        if ($url) {
                            \App\Models\CalendarEvent::create([
                                'workspace_id' => $workspace->id,
                                'case_id' => $case->id,
                                'suggested_event_id' => $suggested->id,
                                'provider' => 'google',
                                'starts_at' => $startsAt,
                                'ends_at' => $endsAt,
                                'status' => 'created',
                                'url' => $url,
                            ]);
                            $suggested->update(['status' => 'confirmed']);

                            $msg = "Meeting successfully booked for " . $startsAt->format('l, F jS \a\t g:i A') . ". We have added this to the calendar and the user will see it in their account.";
                        } else {
                            $msg = "We have noted your preferred time of " . $startsAt->format('l, F jS \a\t g:i A') . " and our team will follow up shortly to confirm the calendar invite via email.";
                        }

                        $results[] = [
                            'toolCallId' => $toolCallId,
                            'name' => $name,
                            'result' => json_encode(['success' => true, 'message' => $msg]),
                        ];
                    } else {
                        $results[] = [
                            'toolCallId' => $toolCallId,
                            'name' => $name,
                            'result' => json_encode(['error' => "Unknown tool: {$name}"]),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('VAPI_TOOL_EXCEPTION', [
                        'toolCallId' => $toolCallId,
                        'tool' => $name,
                        'error' => $e->getMessage(),
                    ]);

                    // Still return a result so Vapi doesn’t produce "No result returned"
                    $results[] = [
                        'toolCallId' => $toolCallId,
                        'name' => $name,
                        'result' => json_encode(['error' => 'Ticket creation failed']),
                    ];
                }
            }

            Log::info('VAPI_OUT', [
                'toolCallsCount' => count($toolCalls),
                'resultsCount' => count($results),
            ]);

            // ✅ Always return 200 and include results array
            return response()->json([
                'results' => $results,
            ], 200);
        }

        if ($type === 'end-of-call-report') {
            [$workspace, $authError] = $this->resolveWorkspaceFromHeaders($request);

            if ($workspace) {
                // Determine Vapi call ID
                $vapiCallId = data_get($payload, 'message.call.id');

                if ($vapiCallId) {
                    $callBlock = data_get($payload, 'message.call', []);
                    $duration = data_get($callBlock, 'durationSeconds') ?? data_get($payload, 'message.durationSeconds');
                    $cost = data_get($callBlock, 'cost') ?? data_get($payload, 'message.cost');

                    try {
                        \App\Models\CallEvent::updateOrCreate(
                            ['vapi_call_id' => $vapiCallId],
                            [
                                'workspace_id' => $workspace->id,
                                'duration_seconds' => $duration,
                                'cost' => $cost,
                                'meta' => $callBlock ?: data_get($payload, 'message', []),
                            ]
                        );

                        // Find case associated with this call
                        $case = \App\Models\SupportCase::where('workspace_id', $workspace->id)
                            ->where('external_call_id', $vapiCallId)
                            ->first();

                        // 1) Billing - Usage Event
                        if ($duration > 0) {
                            $existingUsage = \App\Models\UsageEvent::where('workspace_id', $workspace->id)
                                ->where('event_type', 'call')
                                ->where('metadata->vapi_call_id', $vapiCallId)
                                ->exists();

                            if (!$existingUsage) {
                                $minutes = (int) ceil($duration / 60);
                                \App\Models\UsageEvent::create([
                                    'workspace_id' => $workspace->id,
                                    'support_case_id' => $case?->id,
                                    'minutes' => $minutes,
                                    'event_type' => 'call',
                                    'occurred_at' => now(),
                                    'metadata' => ['vapi_call_id' => $vapiCallId],
                                ]);
                            }
                        }

                        // 2) Billing - Deduct Credits
                        if ($cost > 0) {
                            $costInCents = (int) round($cost * 100);
                            if ($costInCents > 0) {
                                \Illuminate\Support\Facades\DB::transaction(function () use ($workspace, $vapiCallId, $costInCents) {
                                    $lockedWorkspace = \App\Models\Workspace::lockForUpdate()->find($workspace->id);
                                    
                                    $existingCreditDeduction = \App\Models\CreditLedger::where('workspace_id', $lockedWorkspace->id)
                                        ->where('type', 'call_deduction')
                                        ->where('meta->vapi_call_id', $vapiCallId)
                                        ->exists();

                                    if (!$existingCreditDeduction) {
                                        \App\Models\CreditLedger::create([
                                            'workspace_id' => $lockedWorkspace->id,
                                            'type' => 'call_deduction',
                                            'amount' => -$costInCents,
                                            'meta' => ['vapi_call_id' => $vapiCallId],
                                        ]);
                                        $lockedWorkspace->decrement('credits_balance', $costInCents);
                                    }
                                });
                            }
                        }

                        // 3) Update case title and description
                        $summary = data_get($payload, 'message.analysis.summary');
                        if ($case && $summary) {
                            $changed = false;
                            
                            // Replace missing or generic description
                            if (empty($case->description) || trim($case->description) === 'New case, no description') {
                                $case->description = $summary;
                                $changed = true;
                            }
                            
                            // Replace missing or generic title
                            if (empty($case->title) || $case->title === 'New ticket' || stripos($case->title, 'New ticket') === 0 || $case->title === 'New case' || stripos($case->title, 'New case') === 0) {
                                $case->title = substr($summary, 0, 80) . (strlen($summary) > 80 ? '...' : '');
                                $changed = true;
                            }
                            
                            if ($changed) {
                                $case->save();
                            }
                        }

                    } catch (\Throwable $e) {
                        Log::error('VAPI_END_CALL_REPORT_ERROR', [
                            'callId' => $vapiCallId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
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
