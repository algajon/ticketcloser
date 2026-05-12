<?php

namespace Tests\Feature;

use App\Jobs\ProcessEndCallReport;
use App\Models\AssistantConfig;
use App\Models\CallEvent;
use App\Models\SupportCase;
use App\Models\UsageEvent;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessEndCallReportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_call_artifacts_and_upserts_a_named_contact_from_the_end_of_call_report(): void
    {
        $workspace = Workspace::factory()->create();

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'case_number' => 'TC-ENDCALL1',
            'title' => 'New ticket',
            'description' => 'New case, no description',
            'status' => SupportCase::STATUS_NEW,
            'priority' => SupportCase::PRIORITY_NORMAL,
            'source' => SupportCase::SOURCE_VOICE,
            'external_call_id' => 'call_end_1',
        ]);

        $payload = [
            'message' => [
                'type' => 'end-of-call-report',
                'endedReason' => 'hangup',
                'call' => [
                    'id' => 'call_end_1',
                    'customer' => [
                        'number' => '+15551234567',
                    ],
                    'phoneNumber' => [
                        'number' => '+18005550199',
                    ],
                    'durationSeconds' => 94,
                    'cost' => 0.12,
                ],
                'artifact' => [
                    'recording' => 'https://example.com/recordings/call_end_1.mp3',
                    'transcript' => [
                        [
                            'role' => 'assistant',
                            'message' => 'Thanks for calling support. What is your name?',
                        ],
                        [
                            'role' => 'user',
                            'message' => 'Hi, my name is Jane Doe and my sink is leaking.',
                        ],
                    ],
                ],
                'analysis' => [
                    'summary' => 'Jane Doe called about a leaking sink that needs service.',
                ],
            ],
        ];

        (new ProcessEndCallReport($workspace, $payload))->handle();

        $this->assertDatabaseHas('call_events', [
            'workspace_id' => $workspace->id,
            'vapi_call_id' => 'call_end_1',
            'from_number' => '+15551234567',
            'to_number' => '+18005550199',
            'recording_url' => 'https://example.com/recordings/call_end_1.mp3',
        ]);

        $this->assertDatabaseHas('contacts', [
            'workspace_id' => $workspace->id,
            'phone_e164' => '+15551234567',
            'name' => 'Jane Doe',
        ]);

        $case->refresh();

        $this->assertNotNull($case->contact_id);
        $this->assertSame('+15551234567', $case->requester_phone);
        $this->assertSame('https://example.com/recordings/call_end_1.mp3', $case->recording_url);
        $this->assertStringContainsString('Caller: Hi, my name is Jane Doe and my sink is leaking.', (string) $case->transcript);
        $this->assertSame('Jane Doe called about a leaking sink that needs service.', $case->description);
        $this->assertSame('Jane Doe called about a leaking sink that needs service.', $case->title);
    }

    /** @test */
    public function it_fetches_full_vapi_call_details_when_the_end_report_payload_is_thin(): void
    {
        config([
            'services.vapi.key' => 'test-vapi-key',
            'services.vapi.base_url' => 'https://api.vapi.ai',
        ]);

        Http::fake([
            'https://api.vapi.ai/call/call_end_2' => Http::response([
                'id' => 'call_end_2',
                'customer' => [
                    'number' => '+15557654321',
                ],
                'artifact' => [
                    'recording' => [
                        'stereoUrl' => 'https://example.com/recordings/call_end_2.wav',
                    ],
                    'transcript' => [
                        [
                            'role' => 'assistant',
                            'message' => 'Thank you for calling. May I have your name?',
                        ],
                        [
                            'role' => 'user',
                            'message' => 'This is Maria Hill and I need to move my appointment to Friday.',
                        ],
                    ],
                ],
                'analysis' => [
                    'summary' => 'Maria Hill wants to move her appointment to Friday.',
                ],
                'startedAt' => '2026-04-01T10:00:00Z',
                'endedAt' => '2026-04-01T10:02:35Z',
                'cost' => 0.18,
            ]),
        ]);

        $workspace = Workspace::factory()->create();

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'case_number' => 'TC-ENDCALL2',
            'title' => 'New ticket',
            'description' => 'New case, no description',
            'status' => SupportCase::STATUS_NEW,
            'priority' => SupportCase::PRIORITY_NORMAL,
            'source' => SupportCase::SOURCE_VOICE,
            'external_call_id' => 'call_end_2',
        ]);

        $payload = [
            'message' => [
                'type' => 'end-of-call-report',
                'call' => [
                    'id' => 'call_end_2',
                ],
            ],
        ];

        (new ProcessEndCallReport($workspace, $payload))->handle(app(\App\Services\Vapi\VapiCallSyncService::class));

        $this->assertDatabaseHas('call_events', [
            'workspace_id' => $workspace->id,
            'vapi_call_id' => 'call_end_2',
            'from_number' => '+15557654321',
            'recording_url' => 'https://example.com/recordings/call_end_2.wav',
            'duration_seconds' => 155,
        ]);

        $this->assertDatabaseHas('usage_events', [
            'workspace_id' => $workspace->id,
            'event_type' => 'call',
            'minutes' => 3,
        ]);

        $usageEvent = UsageEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where('event_type', 'call')
            ->firstOrFail();

        $this->assertSame('2026-04-01T10:00:00+00:00', $usageEvent->occurred_at?->utc()->toIso8601String());

        $this->assertDatabaseHas('contacts', [
            'workspace_id' => $workspace->id,
            'phone_e164' => '+15557654321',
            'name' => 'Maria Hill',
        ]);

        $case->refresh();

        $this->assertSame('+15557654321', $case->requester_phone);
        $this->assertStringContainsString('Maria Hill', (string) $case->transcript);
        $this->assertSame('https://example.com/recordings/call_end_2.wav', $case->recording_url);
    }

    /** @test */
    public function it_does_not_extract_a_contact_name_from_assistant_lines_in_a_flattened_transcript(): void
    {
        $workspace = Workspace::factory()->create();

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'case_number' => 'TC-ENDCALL3',
            'title' => 'New ticket',
            'description' => 'New case, no description',
            'status' => SupportCase::STATUS_NEW,
            'priority' => SupportCase::PRIORITY_NORMAL,
            'source' => SupportCase::SOURCE_VOICE,
            'external_call_id' => 'call_end_3',
        ]);

        $payload = [
            'message' => [
                'type' => 'end-of-call-report',
                'call' => [
                    'id' => 'call_end_3',
                    'customer' => [
                        'number' => '+15551112223',
                    ],
                ],
                'artifact' => [
                    'transcript' => "Assistant: Dunder Mifflin this is Pam.\nAssistant: Current prompt direction: keep the caller calm.\nCaller: Hello, I have a toilet leak.",
                ],
                'analysis' => [
                    'summary' => 'Caller reported a toilet leak and needs maintenance help.',
                ],
            ],
        ];

        (new ProcessEndCallReport($workspace, $payload))->handle(app(\App\Services\Vapi\VapiCallSyncService::class));

        $this->assertDatabaseHas('contacts', [
            'workspace_id' => $workspace->id,
            'phone_e164' => '+15551112223',
            'name' => null,
        ]);

        $case->refresh();

        $this->assertNotNull($case->contact_id);
    }

    /** @test */
    public function it_persists_language_labels_for_multilingual_calls(): void
    {
        $workspace = Workspace::factory()->create([
            'default_language_code' => 'en-US',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'French Desk',
            'vapi_assistant_id' => 'ast_fr_1',
            'language_code' => 'fr-FR',
        ]);

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'assistant_config_id' => $assistant->id,
            'case_number' => 'TC-ENDCALL4',
            'title' => 'New ticket',
            'description' => 'New case, no description',
            'status' => SupportCase::STATUS_NEW,
            'priority' => SupportCase::PRIORITY_NORMAL,
            'source' => SupportCase::SOURCE_VOICE,
            'external_call_id' => 'call_end_4',
        ]);

        $payload = [
            'message' => [
                'type' => 'end-of-call-report',
                'call' => [
                    'id' => 'call_end_4',
                    'assistantId' => 'ast_fr_1',
                    'customer' => [
                        'number' => '+33123456789',
                    ],
                    'transcriber' => [
                        'provider' => 'deepgram',
                        'model' => 'nova-3',
                        'language' => 'fr',
                    ],
                ],
                'artifact' => [
                    'transcript' => [
                        [
                            'role' => 'user',
                            'message' => "Bonjour, j'ai besoin d'aide avec ma reservation.",
                        ],
                    ],
                ],
                'analysis' => [
                    'language' => 'fr',
                    'summary' => "Le client a besoin d'aide avec sa reservation.",
                ],
            ],
        ];

        (new ProcessEndCallReport($workspace, $payload))->handle(app(\App\Services\Vapi\VapiCallSyncService::class));

        $callEvent = CallEvent::query()->where('vapi_call_id', 'call_end_4')->firstOrFail();
        $case->refresh();

        $this->assertSame('fr-FR', data_get($callEvent->meta, 'language.configured.code'));
        $this->assertSame('fr-FR', data_get($callEvent->meta, 'language.transcript.code'));
        $this->assertSame('French', $callEvent->transcriptLanguageLabel());
        $this->assertSame('Deepgram nova-3', $callEvent->transcriberLabel());
        $this->assertSame('fr-FR', data_get($case->structured_payload, 'voice_metadata.transcript.code'));
        $this->assertSame('French', $case->transcriptLanguageLabel());
    }

    /** @test */
    public function it_deactivates_free_workspace_phone_numbers_once_the_usage_cap_is_reached(): void
    {
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Trial Assistant',
            'vapi_assistant_id' => 'assistant_trial_1',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_trial_1',
            'e164' => '+18005550111',
            'is_active' => true,
        ]);

        $payload = [
            'message' => [
                'type' => 'end-of-call-report',
                'call' => [
                    'id' => 'call_cap_1',
                    'customer' => [
                        'number' => '+15550000001',
                    ],
                    'durationSeconds' => 360,
                ],
            ],
        ];

        (new ProcessEndCallReport($workspace, $payload))->handle();

        $this->assertDatabaseHas('usage_events', [
            'workspace_id' => $workspace->id,
            'event_type' => 'call',
            'minutes' => 6,
        ]);

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'vapi_phone_number_id' => 'pn_trial_1',
            'is_active' => false,
        ]);
    }
}
