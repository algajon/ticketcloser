<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\CalendarConnection;
use App\Models\Workspace;
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
}
