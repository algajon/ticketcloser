<?php

namespace Tests\Feature;

use App\Jobs\ProcessEndCallReport;
use App\Models\AssistantConfig;
use App\Models\CalendarConnection;
use App\Models\Contact;
use App\Models\UsageEvent;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VapiWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->workspace = Workspace::factory()->create([
            'integration_token' => 'test-token-123',
            'slug' => 'test-workspace',
        ]);

        AssistantConfig::create([
            'workspace_id' => $this->workspace->id,
            'vapi_assistant_id' => 'ast-1234',
            'name' => 'Support Agent',
        ]);
    }

    /** @test */
    public function it_handles_tool_calls_and_returns_results_array(): void
    {
        $payload = [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_abc123',
                    'type' => 'function',
                    'function' => [
                        'name' => 'createCase',
                        'arguments' => json_encode([
                            'title' => 'Test issue',
                            'description' => 'Detailed issue description',
                            'category' => 'general',
                            'priority' => 'high',
                            'requesterPhone' => '+15551234567',
                            'externalCallId' => 'vapi_call_999',
                        ]),
                    ],
                ]],
                'call' => [
                    'id' => 'vapi_call_999',
                    'assistantId' => 'ast-1234',
                    'from' => '+15551234567',
                    'to' => '+18005551234',
                    'durationSeconds' => 120,
                    'cost' => 0.05,
                    'recordingUrl' => 'https://example.com/recording.wav',
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload, [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => ['toolCallId', 'name', 'result'],
            ],
        ]);

        $data = $response->json('results');
        $this->assertCount(1, $data);
        $this->assertEquals('call_abc123', $data[0]['toolCallId']);
        $this->assertEquals('createCase', $data[0]['name']);

        $parsed = json_decode($data[0]['result'], true);
        $this->assertArrayHasKey('caseNumber', $parsed);

        $this->assertDatabaseHas('support_cases', [
            'workspace_id' => $this->workspace->id,
            'title' => 'Test issue',
            'priority' => 'high',
            'external_call_id' => 'vapi_call_999',
        ]);

        $this->assertDatabaseHas('call_events', [
            'workspace_id' => $this->workspace->id,
            'vapi_call_id' => 'vapi_call_999',
            'from_number' => '+15551234567',
        ]);
    }

    /** @test */
    public function it_can_lookup_an_existing_contact_as_a_tool_call(): void
    {
        $contact = Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Nick Dillon',
            'phone_e164' => '+16402298699',
            'property_code' => '123 King Street West',
            'unit' => '6',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_lookup_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'lookupContact',
                        'arguments' => json_encode([
                            'phone' => '+16402298699',
                        ]),
                    ],
                ]],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();

        $parsed = json_decode($response->json('results.0.result'), true);

        $this->assertTrue($parsed['found']);
        $this->assertSame($contact->id, $parsed['contactId']);
        $this->assertSame('Nick Dillon', $parsed['name']);
        $this->assertSame('123 King Street West', $parsed['propertyCode']);
        $this->assertSame('6', $parsed['unit']);
    }

    /** @test */
    public function it_reuses_the_same_ticket_when_the_same_call_is_processed_twice(): void
    {
        $payload = [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_dup_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'createCase',
                        'arguments' => json_encode([
                            'title' => 'Broken sink',
                            'description' => 'Sink is leaking under the cabinet.',
                            'requesterPhone' => '+15550000001',
                            'externalCallId' => 'vapi_call_dup_1',
                        ]),
                    ],
                ]],
                'call' => [
                    'id' => 'vapi_call_dup_1',
                    'assistantId' => 'ast-1234',
                ],
            ],
        ];

        $headers = [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ];

        $this->postJson('/api/webhooks/vapi', $payload, $headers)->assertOk();
        $this->postJson('/api/webhooks/vapi', $payload, $headers)->assertOk();

        $this->assertDatabaseCount('support_cases', 1);
        $this->assertDatabaseCount('call_events', 1);
    }

    /** @test */
    public function it_falls_back_to_the_call_metadata_for_phone_and_call_id(): void
    {
        $payload = [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_fallback_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'createCase',
                        'arguments' => json_encode([
                            'title' => 'Laptop is broken',
                            'description' => 'The caller says the laptop no longer turns on.',
                        ]),
                    ],
                ]],
                'call' => [
                    'id' => 'vapi_call_fallback_1',
                    'assistantId' => 'ast-1234',
                    'from' => '+15557654321',
                ],
            ],
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload, [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();
        $parsed = json_decode($response->json('results.0.result'), true);

        $this->assertArrayHasKey('caseNumber', $parsed);
        $this->assertDatabaseHas('support_cases', [
            'workspace_id' => $this->workspace->id,
            'title' => 'Laptop is broken',
            'requester_phone' => '+15557654321',
            'external_call_id' => 'vapi_call_fallback_1',
        ]);
        $this->assertDatabaseHas('call_events', [
            'workspace_id' => $this->workspace->id,
            'vapi_call_id' => 'vapi_call_fallback_1',
            'from_number' => '+15557654321',
        ]);
    }

    /** @test */
    public function it_blocks_assistant_routing_when_a_free_workspace_has_used_its_minutes(): void
    {
        $this->workspace->update(['plan_key' => 'free']);

        $assistant = AssistantConfig::query()->where('workspace_id', $this->workspace->id)->firstOrFail();

        WorkspacePhoneNumber::query()->create([
            'workspace_id' => $this->workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_free_limit_1',
            'e164' => '+18005550199',
            'is_active' => true,
        ]);

        UsageEvent::query()->create([
            'workspace_id' => $this->workspace->id,
            'minutes' => 5,
            'event_type' => 'call',
            'occurred_at' => now(),
            'metadata' => ['vapi_call_id' => 'historical_call_1'],
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'assistant-request',
                'call' => [
                    'id' => 'call_limit_1',
                    'phoneNumberId' => 'pn_free_limit_1',
                    'customer' => ['number' => '+15550001111'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('assistant.firstMessage', 'This workspace has reached its free voice limit. Please contact the business to upgrade their account.');
    }

    /** @test */
    public function it_personalizes_the_opening_line_for_a_known_contact(): void
    {
        $assistant = AssistantConfig::query()->where('workspace_id', $this->workspace->id)->firstOrFail();
        $assistant->update([
            'first_message' => 'Hi, thanks for calling Northline Support.',
            'language_code' => 'fr-FR',
            'vapi_tool_id' => 'tool_case_123',
            'vapi_booking_tool_id' => 'tool_booking_123',
            'vapi_lookup_tool_id' => 'tool_lookup_123',
            'vapi_case_lookup_tool_id' => 'tool_case_lookup_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $this->workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_known_contact_1',
            'e164' => '+18005550199',
            'is_active' => true,
        ]);

        Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Nick Dillon',
            'phone_e164' => '+16402298699',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'assistant-request',
                'call' => [
                    'id' => 'call_known_contact_1',
                    'phoneNumberId' => 'pn_known_contact_1',
                    'customer' => ['number' => '+16402298699'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('assistantId', 'ast-1234');
        $response->assertJsonPath('assistantOverrides.firstMessage', 'Hi, thanks for calling Northline Support. Ravi de vous reparler, Nick.');
        $response->assertJsonPath('assistantOverrides.variableValues.knownCallerSuffix', ' Ravi de vous reparler, Nick.');
        $this->assertSame(
            ['tool_case_123', 'tool_booking_123', 'tool_case_lookup_123'],
            array_values(array_filter($response->json('assistantOverrides.model.toolIds')))
        );
        $this->assertStringContainsString(
            'Keep caller-facing replies in fr-FR',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'If the caller asks what name, number, property, unit, or prior case you already have on file, answer directly',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'known returning caller named Nick Dillon',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'Never say \'Just a sec\'',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'Use that context immediately instead of pausing to check records again out loud',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'Use any caller context already provided in the system note before deciding whether to call lookupContact or lookupCase',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'answer immediately that you have them as Nick Dillon',
            $response->json('assistantOverrides.model.messages.0.content')
        );
    }

    /** @test */
    public function it_preserves_operator_handoff_tools_in_runtime_assistant_overrides(): void
    {
        $assistant = AssistantConfig::query()->where('workspace_id', $this->workspace->id)->firstOrFail();
        $destination = AssistantConfig::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Sales Desk',
            'language_code' => 'en-US',
            'vapi_assistant_id' => 'ast-sales-123',
        ]);

        $assistant->update([
            'first_message' => 'Thanks for calling. Which team do you need?',
            'language_code' => 'en-US',
            'intake_params' => [
                'operator' => [
                    'enabled' => true,
                    'mode' => 'spoken_handoff',
                    'intro' => 'Say sales, support, or German and I will connect you.',
                    'fallback_message' => 'Which team should I connect you with?',
                    'routes' => [
                        [
                            'label' => 'Sales',
                            'keywords' => 'sales, pricing, quote',
                            'assistant_id' => $destination->id,
                            'language_code' => 'en-US',
                        ],
                    ],
                ],
            ],
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $this->workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_operator_known_contact_1',
            'e164' => '+18005550197',
            'is_active' => true,
        ]);

        Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Pat Router',
            'phone_e164' => '+16402298701',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'assistant-request',
                'call' => [
                    'id' => 'call_operator_known_contact_1',
                    'phoneNumberId' => 'pn_operator_known_contact_1',
                    'customer' => ['number' => '+16402298701'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('assistantId', 'ast-1234');

        $handoffTool = collect($response->json('assistantOverrides.model.tools') ?? [])->firstWhere('type', 'handoff');
        $this->assertNotNull($handoffTool);
        $this->assertSame('ast-sales-123', $handoffTool['destinations'][0]['assistantId']);
        $this->assertStringContainsString('Sales', $handoffTool['destinations'][0]['description']);
        $this->assertStringContainsString(
            'OPERATOR ROUTING MODE',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringContainsString(
            'Do not tell callers they must press keypad buttons',
            $response->json('assistantOverrides.model.messages.0.content')
        );
    }

    /** @test */
    public function it_localizes_the_runtime_opening_line_for_supported_languages(): void
    {
        $assistant = AssistantConfig::query()->where('workspace_id', $this->workspace->id)->firstOrFail();
        $assistant->update([
            'first_message' => null,
            'language_code' => 'hi-IN',
            'vapi_tool_id' => 'tool_case_123',
            'vapi_booking_tool_id' => 'tool_booking_123',
            'vapi_lookup_tool_id' => 'tool_lookup_123',
            'vapi_case_lookup_tool_id' => 'tool_case_lookup_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $this->workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_known_contact_hi_1',
            'e164' => '+18005550198',
            'is_active' => true,
        ]);

        Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Aarav Singh',
            'phone_e164' => '+16402298700',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'assistant-request',
                'call' => [
                    'id' => 'call_known_contact_hi_1',
                    'phoneNumberId' => 'pn_known_contact_hi_1',
                    'customer' => ['number' => '+16402298700'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('assistantId', 'ast-1234');
        $response->assertJsonPath(
            'assistantOverrides.firstMessage',
            'नमस्ते, सपोर्ट पर कॉल करने के लिए धन्यवाद। आज मैं आपकी कैसे मदद करूँ? आपसे फिर बात करके खुशी हुई, Aarav.'
        );
        $response->assertJsonPath(
            'assistantOverrides.variableValues.knownCallerSuffix',
            ' आपसे फिर बात करके खुशी हुई, Aarav.'
        );
        $this->assertStringContainsString(
            'Keep caller-facing replies in hi-IN',
            $response->json('assistantOverrides.model.messages.0.content')
        );
    }

    /** @test */
    public function it_localizes_runtime_prompt_and_opening_line_to_the_selected_assistant_language(): void
    {
        config([
            'services.openai.api_key' => 'openai-test-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
            'services.openai.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => 'Sie sind der Sprachassistent fuer Test Workspace. Stellen Sie jeweils nur eine Frage.',
                        ],
                    ]],
                ], 200)
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => 'Danke fuer Ihren Anruf bei Test Workspace. Wie kann ich Ihnen heute helfen?',
                        ],
                    ]],
                ], 200),
        ]);

        $assistant = AssistantConfig::query()->where('workspace_id', $this->workspace->id)->firstOrFail();
        $assistant->update([
            'system_prompt' => 'You are the voice assistant for Test Workspace. Ask one question at a time.',
            'first_message' => 'Thanks for calling Test Workspace. How can I help today?',
            'language_code' => 'de-DE',
            'vapi_tool_id' => 'tool_case_123',
            'vapi_booking_tool_id' => 'tool_booking_123',
            'vapi_lookup_tool_id' => 'tool_lookup_123',
            'vapi_case_lookup_tool_id' => 'tool_case_lookup_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $this->workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_known_contact_de_1',
            'e164' => '+18005550197',
            'is_active' => true,
        ]);

        Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Lena Vogel',
            'phone_e164' => '+16402298701',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'assistant-request',
                'call' => [
                    'id' => 'call_known_contact_de_1',
                    'phoneNumberId' => 'pn_known_contact_de_1',
                    'customer' => ['number' => '+16402298701'],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('assistantId', 'ast-1234');
        $this->assertStringStartsWith(
            'Danke fuer Ihren Anruf bei Test Workspace. Wie kann ich Ihnen heute helfen?',
            $response->json('assistantOverrides.firstMessage')
        );
        $this->assertStringContainsString(
            'Lena',
            $response->json('assistantOverrides.variableValues.knownCallerSuffix')
        );
        $this->assertStringContainsString(
            'Sie sind der Sprachassistent fuer Test Workspace. Stellen Sie jeweils nur eine Frage.',
            $response->json('assistantOverrides.model.messages.0.content')
        );
        $this->assertStringNotContainsString(
            'You are the voice assistant for Test Workspace. Ask one question at a time.',
            $response->json('assistantOverrides.model.messages.0.content')
        );
    }

    /** @test */
    public function it_can_lookup_recent_cases_for_a_contact_as_a_tool_call(): void
    {
        $contact = Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Nick Dillon',
            'phone_e164' => '+16402298699',
        ]);

        \App\Models\SupportCase::create([
            'workspace_id' => $this->workspace->id,
            'contact_id' => $contact->id,
            'case_number' => 'TC-PAST001',
            'title' => 'Leaking sink',
            'description' => 'Kitchen sink has been leaking for two days.',
            'status' => 'waiting',
            'priority' => 'high',
            'source' => 'voice',
        ]);

        \App\Models\SupportCase::create([
            'workspace_id' => $this->workspace->id,
            'contact_id' => $contact->id,
            'case_number' => 'TC-PAST002',
            'title' => 'Paint peeling',
            'description' => 'Paint is peeling near the bathroom ceiling.',
            'status' => 'new',
            'priority' => 'normal',
            'source' => 'voice',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_lookup_case_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'lookupCase',
                        'arguments' => json_encode([
                            'phone' => '+16402298699',
                            'limit' => 2,
                        ]),
                    ],
                ]],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();

        $parsed = json_decode($response->json('results.0.result'), true);

        $this->assertTrue($parsed['found']);
        $this->assertTrue($parsed['contactFound']);
        $this->assertSame($contact->id, $parsed['contactId']);
        $this->assertSame('Nick Dillon', $parsed['contactName']);
        $this->assertCount(2, $parsed['cases']);
        $this->assertSame('TC-PAST002', $parsed['cases'][0]['caseNumber']);
        $this->assertSame('Paint peeling', $parsed['cases'][0]['title']);
        $this->assertSame('new', $parsed['cases'][0]['status']);
    }

    /** @test */
    public function it_creates_a_suggested_meeting_when_google_calendar_is_not_connected(): void
    {
        $caseResponse = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_case_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'createCase',
                        'arguments' => json_encode([
                            'title' => 'Need a callback',
                            'description' => 'Please call me back tomorrow afternoon.',
                            'requesterPhone' => '+15551230000',
                            'externalCallId' => 'vapi_call_case_1',
                        ]),
                    ],
                ]],
                'call' => [
                    'id' => 'vapi_call_case_1',
                    'assistantId' => 'ast-1234',
                ],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $caseNumber = json_decode($caseResponse->json('results.0.result'), true)['caseNumber'];

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_meeting_1',
                    'type' => 'function',
                    'function' => [
                        'name' => 'bookMeeting',
                        'arguments' => json_encode([
                            'caseId' => $caseNumber,
                            'dateTime' => '2026-04-02T14:00:00Z',
                        ]),
                    ],
                ]],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();
        $parsed = json_decode($response->json('results.0.result'), true);

        $this->assertTrue($parsed['success']);
        $this->assertArrayHasKey('suggestedEventId', $parsed);
        $this->assertNull($parsed['calendarEventId']);
        $this->assertDatabaseHas('suggested_events', [
            'id' => $parsed['suggestedEventId'],
            'workspace_id' => $this->workspace->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_handles_parallel_case_and_meeting_requests_in_a_single_payload(): void
    {
        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [
                    [
                        'id' => 'call_meeting_parallel_1',
                        'type' => 'function',
                        'function' => [
                            'name' => 'bookMeeting',
                            'arguments' => json_encode([
                                'dateTime' => '2026-04-02T15:00:00Z',
                            ]),
                        ],
                    ],
                    [
                        'id' => 'call_case_parallel_1',
                        'type' => 'function',
                        'function' => [
                            'name' => 'createCase',
                            'arguments' => json_encode([
                                'title' => 'Laptop overheating',
                                'description' => 'The laptop overheats and the caller wants to bring it in tomorrow.',
                                'requesterPhone' => '+15550001111',
                                'externalCallId' => 'vapi_parallel_call_1',
                            ]),
                        ],
                    ],
                ],
                'call' => [
                    'id' => 'vapi_parallel_call_1',
                    'assistantId' => 'ast-1234',
                    'from' => '+15550001111',
                ],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();
        $results = collect($response->json('results'));

        $createResult = $results->firstWhere('name', 'createCase');
        $meetingResult = $results->firstWhere('name', 'bookMeeting');

        $this->assertNotNull($createResult);
        $this->assertNotNull($meetingResult);

        $createPayload = json_decode($createResult['result'], true);
        $meetingPayload = json_decode($meetingResult['result'], true);

        $this->assertArrayHasKey('caseNumber', $createPayload);
        $this->assertTrue($meetingPayload['success']);
        $this->assertArrayHasKey('suggestedEventId', $meetingPayload);

        $this->assertDatabaseHas('support_cases', [
            'workspace_id' => $this->workspace->id,
            'external_call_id' => 'vapi_parallel_call_1',
            'title' => 'Laptop overheating',
        ]);
        $this->assertDatabaseHas('suggested_events', [
            'id' => $meetingPayload['suggestedEventId'],
            'workspace_id' => $this->workspace->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_books_the_meeting_immediately_when_google_calendar_is_connected(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response([
                'id' => 'google-event-1',
                'htmlLink' => 'https://calendar.google.com/event?eid=1',
            ], 200),
        ]);

        $connection = CalendarConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'google',
            'tokens_encrypted' => '-',
        ]);
        $connection->tokens = ['access_token' => 'token-123'];
        $connection->save();

        $caseId = \App\Models\SupportCase::create([
            'workspace_id' => $this->workspace->id,
            'case_number' => 'TC-BOOKED1',
            'title' => 'Follow-up needed',
            'description' => 'Customer asked to meet.',
            'status' => 'new',
            'priority' => 'normal',
            'source' => 'voice',
        ])->case_number;

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_meeting_2',
                    'type' => 'function',
                    'function' => [
                        'name' => 'bookMeeting',
                        'arguments' => json_encode([
                            'caseId' => $caseId,
                            'dateTime' => '2026-04-03T09:30:00Z',
                        ]),
                    ],
                ]],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();
        $parsed = json_decode($response->json('results.0.result'), true);

        $this->assertTrue($parsed['success']);
        $this->assertTrue($parsed['booked']);
        $this->assertNotNull($parsed['calendarEventId']);
        $this->assertDatabaseHas('calendar_events', [
            'id' => $parsed['calendarEventId'],
            'provider' => 'google',
            'provider_event_id' => 'google-event-1',
        ]);
        $this->assertDatabaseHas('suggested_events', [
            'id' => $parsed['suggestedEventId'],
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function it_falls_back_to_a_pending_meeting_when_google_booking_fails(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response([
                'error' => ['message' => 'Calendar unavailable'],
            ], 500),
        ]);

        $connection = CalendarConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'google',
            'tokens_encrypted' => '-',
        ]);
        $connection->tokens = ['access_token' => 'token-123'];
        $connection->save();

        $caseId = \App\Models\SupportCase::create([
            'workspace_id' => $this->workspace->id,
            'case_number' => 'TC-FALLBK1',
            'title' => 'Follow-up needed',
            'description' => 'Customer asked to meet.',
            'status' => 'new',
            'priority' => 'normal',
            'source' => 'voice',
        ])->case_number;

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_meeting_3',
                    'type' => 'function',
                    'function' => [
                        'name' => 'bookMeeting',
                        'arguments' => json_encode([
                            'caseId' => $caseId,
                            'dateTime' => '2026-04-03T09:30:00Z',
                        ]),
                    ],
                ]],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();
        $parsed = json_decode($response->json('results.0.result'), true);

        $this->assertTrue($parsed['success']);
        $this->assertFalse($parsed['booked']);
        $this->assertNull($parsed['calendarEventId']);
        $this->assertDatabaseCount('calendar_events', 0);
        $this->assertDatabaseHas('suggested_events', [
            'id' => $parsed['suggestedEventId'],
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_returns_tool_result_with_error_if_unauthorized(): void
    {
        $payload = [
            'message' => [
                'type' => 'tool-calls',
                'toolCallList' => [[
                    'id' => 'call_wrong123',
                    'type' => 'function',
                    'function' => [
                        'name' => 'createCase',
                        'arguments' => '{}',
                    ],
                ]],
            ],
        ];

        $response = $this->postJson('/api/webhooks/vapi', $payload, [
            'Authorization' => 'Bearer wrong-token',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => ['toolCallId', 'name', 'error'],
            ],
        ]);

        $data = $response->json('results');
        $this->assertCount(1, $data);
        $this->assertEquals('call_wrong123', $data[0]['toolCallId']);
        $this->assertEquals('Invalid token', $data[0]['error']);
    }

    /** @test */
    public function it_rejects_requests_with_an_invalid_vapi_secret_when_configured(): void
    {
        config(['services.vapi.secret' => 'expected-secret']);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'assistant-request',
                'call' => [
                    'id' => 'call_secret_1',
                    'phoneNumberId' => 'pn_secret_1',
                ],
            ],
        ], [
            'x-vapi-secret' => 'wrong-secret',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function it_accepts_end_of_call_reports(): void
    {
        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'end-of-call-report',
                'call' => [
                    'id' => 'vapi_call_report_1',
                    'customer' => [
                        'number' => '+15551112222',
                    ],
                ],
                'artifact' => [
                    'transcript' => [
                        [
                            'role' => 'user',
                            'message' => 'Hi, my name is Janet.',
                        ],
                    ],
                ],
            ],
        ], [
            'Authorization' => 'Bearer test-token-123',
            'X-Workspace-Slug' => 'test-workspace',
        ]);

        $response->assertOk();

        Queue::assertPushed(ProcessEndCallReport::class, function (ProcessEndCallReport $job) {
            return $job->workspace->is($this->workspace)
                && data_get($job->payload, 'message.call.id') === 'vapi_call_report_1';
        });
    }

    /** @test */
    public function it_resolves_end_of_call_reports_by_phone_number_or_assistant_without_tool_headers(): void
    {
        WorkspacePhoneNumber::create([
            'workspace_id' => $this->workspace->id,
            'assistant_id' => AssistantConfig::query()->where('workspace_id', $this->workspace->id)->value('id'),
            'vapi_phone_number_id' => 'pn-prod-123',
            'e164' => '+12165550123',
        ]);

        $response = $this->postJson('/api/webhooks/vapi', [
            'message' => [
                'type' => 'end-of-call-report',
                'call' => [
                    'id' => 'vapi_call_report_2',
                    'assistantId' => 'ast-1234',
                    'phoneNumberId' => 'pn-prod-123',
                    'customer' => [
                        'number' => '+15554443333',
                    ],
                ],
            ],
        ]);

        $response->assertOk();

        Queue::assertPushed(ProcessEndCallReport::class, function (ProcessEndCallReport $job) {
            return $job->workspace->is($this->workspace)
                && data_get($job->payload, 'message.call.id') === 'vapi_call_report_2';
        });
    }
}
