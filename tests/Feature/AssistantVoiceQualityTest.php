<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\AssistantPreset;
use App\Models\Workspace;
use App\Services\Vapi\VapiClient;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantVoiceQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_assistants_get_premium_feeling_defaults(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Front Desk',
            'preset_key' => 'premium_concierge',
            'language_code' => 'en-US',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('gpt-4o-mini', $payload['model']['model']);
                $this->assertSame('vapi', $payload['voice']['provider']);
                $this->assertSame('Clara', $payload['voice']['voiceId']);
                $this->assertSame(0.98, $payload['voice']['speed']);
                $this->assertStringContainsString('RETURNING CALLER RULES', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Use any caller context already provided in your system note first', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Nice to speak with you again', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Use any caller context already provided in your system note first', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Never say "Just a sec"', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('HUMANE CONVERSATION RULES', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Do not interrupt the caller', $payload['model']['messages'][0]['content']);
                $this->assertTrue($payload['backgroundSpeechDenoisingPlan']['smartDenoisingPlan']['enabled']);
                $this->assertSame('deepgram', $payload['transcriber']['provider']);
                $this->assertSame('nova-3-general', $payload['transcriber']['model']);
                $this->assertTrue($payload['transcriber']['numerals']);
                $this->assertSame(0.95, $payload['startSpeakingPlan']['waitSeconds']);
                $this->assertSame(4, $payload['stopSpeakingPlan']['numWords']);
                $this->assertSame(1.45, $payload['stopSpeakingPlan']['backoffSeconds']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, ['name' => 'Front Desk']);
    }

    public function test_aggressive_timing_overrides_are_clamped_to_humane_defaults(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Careful Desk',
            'preset_key' => 'confident_closer',
            'language_code' => 'en-US',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame(0.8, $payload['startSpeakingPlan']['waitSeconds']);
                $this->assertSame(4, $payload['stopSpeakingPlan']['numWords']);
                $this->assertSame(0.46, $payload['stopSpeakingPlan']['voiceSeconds']);
                $this->assertSame(1.25, $payload['stopSpeakingPlan']['backoffSeconds']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Careful Desk',
            'preset_key' => 'confident_closer',
            'override_params' => [
                'waitSeconds' => 0.1,
                'numWords' => 1,
                'backoffSeconds' => 0.5,
            ],
        ]);

        $assistant->refresh();
        $this->assertSame(0.8, $assistant->override_params['waitSeconds']);
        $this->assertSame(4, $assistant->override_params['numWords']);
        $this->assertSame(1.25, $assistant->override_params['backoffSeconds']);
    }

    public function test_realtime_model_uses_compatible_openai_voice_and_skips_transcriber(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Premium Line',
            'preset_key' => 'premium_concierge',
            'language_code' => 'en-US',
            'model_name' => 'gpt-realtime-2025-08-28',
            'voice_provider' => 'vapi',
            'voice_id' => 'Clara',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('gpt-realtime-2025-08-28', $payload['model']['model']);
                $this->assertSame('openai', $payload['voice']['provider']);
                $this->assertSame('shimmer', $payload['voice']['voiceId']);
                $this->assertSame(1.0, $payload['voice']['speed']);
                $this->assertArrayNotHasKey('transcriber', $payload);
                $this->assertSame(0.6, $payload['model']['temperature']);
                $this->assertSame(380, $payload['model']['maxTokens']);
                $this->assertSame('Thanks for calling Northline Support. How can I help today?{{ knownCallerSuffix | default: "" }}', $payload['firstMessage']);
                $this->assertSame(0.95, $payload['startSpeakingPlan']['waitSeconds']);
                $this->assertSame(4, $payload['stopSpeakingPlan']['numWords']);
                $this->assertSame(0.5, $payload['stopSpeakingPlan']['voiceSeconds']);
                $this->assertSame(1.45, $payload['stopSpeakingPlan']['backoffSeconds']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Premium Line',
            'model_name' => 'gpt-realtime-2025-08-28',
            'voice_provider' => 'vapi',
            'voice_id' => 'Clara',
            'first_message' => 'Thanks for calling Northline Support. How can I help today?',
        ]);
    }

    public function test_free_plan_is_clamped_to_standard_model_and_curated_voice(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'free',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Trial Line',
            'preset_key' => 'bright_guide',
            'language_code' => 'en-US',
            'model_name' => 'gpt-realtime-2025-08-28',
            'voice_provider' => 'openai',
            'voice_id' => 'marin',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('gpt-4o-mini', $payload['model']['model']);
                $this->assertSame('vapi', $payload['voice']['provider']);
                $this->assertSame('Emma', $payload['voice']['voiceId']);
                $this->assertSame(1.02, $payload['voice']['speed']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Trial Line',
            'model_name' => 'gpt-realtime-2025-08-28',
            'voice_provider' => 'openai',
            'voice_id' => 'marin',
        ]);

        $assistant->refresh();
        $this->assertSame('gpt-4o-mini', $assistant->model_name);
        $this->assertSame('vapi', $assistant->voice_provider);
        $this->assertSame('Emma', $assistant->voice_id);
    }

    public function test_standard_models_normalize_openai_voice_choices_back_to_curated_english_voice(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Admin Trial Line',
            'preset_key' => 'premium_concierge',
            'language_code' => 'en-US',
            'model_name' => 'gpt-4.1',
            'voice_provider' => 'openai',
            'voice_id' => 'marin',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('gpt-4.1', $payload['model']['model']);
                $this->assertSame('vapi', $payload['voice']['provider']);
                $this->assertSame('Emma', $payload['voice']['voiceId']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Admin Trial Line',
            'model_name' => 'gpt-4.1',
            'voice_provider' => 'openai',
            'voice_id' => 'marin',
        ]);

        $assistant->refresh();
        $this->assertSame('gpt-4.1', $assistant->model_name);
        $this->assertSame('vapi', $assistant->voice_provider);
        $this->assertSame('Emma', $assistant->voice_id);
    }

    public function test_custom_first_message_is_used_for_standard_models_too(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Front Desk',
            'preset_key' => 'bright_guide',
            'language_code' => 'en-US',
            'first_message' => 'Northline Support, this is Maya speaking. How can I help today?',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('Northline Support, this is Maya speaking. How can I help today?{{ knownCallerSuffix | default: "" }}', $payload['firstMessage']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Front Desk',
            'first_message' => 'Northline Support, this is Maya speaking. How can I help today?',
        ]);
    }

    public function test_custom_prompt_and_opening_line_are_localized_to_the_assistant_language_when_ai_is_available(): void
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
                            'content' => 'Sie sind der Sprachassistent fuer Northline Support. Begruessen Sie Anrufer ruhig und klar.',
                        ],
                    ]],
                ], 200)
                ->push([
                    'choices' => [[
                        'message' => [
                            'content' => 'Danke fuer Ihren Anruf bei Northline Support. Wie kann ich Ihnen heute helfen?',
                        ],
                    ]],
                ], 200),
        ]);

        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'German Desk',
            'preset_key' => 'steady_operator',
            'language_code' => 'de-DE',
            'system_prompt' => 'You are the voice assistant for Northline Support. Greet callers calmly and clearly.',
            'first_message' => 'Thanks for calling Northline Support. How can I help today?',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertStringContainsString(
                    'Sie sind der Sprachassistent fuer Northline Support. Begruessen Sie Anrufer ruhig und klar.',
                    $payload['model']['messages'][0]['content']
                );
                $this->assertStringNotContainsString(
                    'You are the voice assistant for Northline Support. Greet callers calmly and clearly.',
                    $payload['model']['messages'][0]['content']
                );
                $this->assertSame(
                    'Danke fuer Ihren Anruf bei Northline Support. Wie kann ich Ihnen heute helfen?{{ knownCallerSuffix | default: "" }}',
                    $payload['firstMessage']
                );

                return true;
            }))
            ->willReturn(['id' => 'assistant-de-localized-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'German Desk',
            'language_code' => 'de-DE',
            'system_prompt' => 'You are the voice assistant for Northline Support. Greet callers calmly and clearly.',
            'first_message' => 'Thanks for calling Northline Support. How can I help today?',
        ]);
    }

    public function test_standard_models_fall_back_to_a_localized_default_first_message(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Hindi Desk',
            'preset_key' => 'bright_guide',
            'language_code' => 'hi-IN',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame(
                    'नमस्ते, सपोर्ट पर कॉल करने के लिए धन्यवाद। आज मैं आपकी कैसे मदद करूँ?{{ knownCallerSuffix | default: "" }}',
                    $payload['firstMessage']
                );

                return true;
            }))
            ->willReturn(['id' => 'assistant-hi-msg-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Hindi Desk',
            'language_code' => 'hi-IN',
        ]);
    }

    public function test_hindi_assistants_use_curated_azure_voice_and_hindi_transcription(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Hindi Desk',
            'preset_key' => 'bright_guide',
            'language_code' => 'hi-IN',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('azure', $payload['voice']['provider']);
                $this->assertSame('hi-IN-SwaraNeural', $payload['voice']['voiceId']);
                $this->assertSame('deepgram', $payload['transcriber']['provider']);
                $this->assertSame('nova-3', $payload['transcriber']['model']);
                $this->assertSame('hi', $payload['transcriber']['language']);
                $this->assertSame('hi-IN', $payload['transcriber']['fallbackPlan']['transcribers'][0]['language']);
                $this->assertStringContainsString('Keep caller-facing replies in hi-IN', $payload['model']['messages'][0]['content']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-hi-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Hindi Desk',
            'language_code' => 'hi-IN',
        ]);
    }

    public function test_german_assistants_use_curated_azure_voice_and_german_transcription(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'German Desk',
            'preset_key' => 'steady_operator',
            'language_code' => 'de-DE',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('azure', $payload['voice']['provider']);
                $this->assertSame('de-DE-KlausNeural', $payload['voice']['voiceId']);
                $this->assertSame('deepgram', $payload['transcriber']['provider']);
                $this->assertSame('nova-3', $payload['transcriber']['model']);
                $this->assertSame('de', $payload['transcriber']['language']);
                $this->assertSame('de-DE', $payload['transcriber']['fallbackPlan']['transcribers'][0]['language']);
                $this->assertStringContainsString('Keep caller-facing replies in de-DE', $payload['model']['messages'][0]['content']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-de-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'German Desk',
            'language_code' => 'de-DE',
            'preset_key' => 'steady_operator',
        ]);
    }

    public function test_non_english_standard_models_normalize_openai_voice_choices_back_to_the_curated_regional_voice(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'German Desk',
            'preset_key' => 'steady_operator',
            'language_code' => 'de-DE',
            'model_name' => 'gpt-4.1',
            'voice_provider' => 'openai',
            'voice_id' => 'cedar',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('gpt-4.1', $payload['model']['model']);
                $this->assertSame('azure', $payload['voice']['provider']);
                $this->assertSame('de-DE-KlausNeural', $payload['voice']['voiceId']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-de-openai-fallback-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'German Desk',
            'language_code' => 'de-DE',
            'model_name' => 'gpt-4.1',
            'voice_provider' => 'openai',
            'voice_id' => 'cedar',
            'preset_key' => 'steady_operator',
        ]);

        $assistant->refresh();
        $this->assertSame('azure', $assistant->voice_provider);
        $this->assertSame('de-DE-KlausNeural', $assistant->voice_id);
    }

    public function test_free_plan_keeps_curated_non_english_voice_path(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'free',
        ]);
        AssistantPreset::ensureDefaults();

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Hindi Trial Line',
            'preset_key' => 'bright_guide',
            'language_code' => 'hi-IN',
            'model_name' => 'gpt-realtime-2025-08-28',
            'voice_provider' => 'openai',
            'voice_id' => 'marin',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('gpt-4o-mini', $payload['model']['model']);
                $this->assertSame('azure', $payload['voice']['provider']);
                $this->assertSame('hi-IN-SwaraNeural', $payload['voice']['voiceId']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-hi-free-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, [
            'name' => 'Hindi Trial Line',
            'language_code' => 'hi-IN',
            'model_name' => 'gpt-realtime-2025-08-28',
            'voice_provider' => 'openai',
            'voice_id' => 'marin',
        ]);

        $assistant->refresh();
        $this->assertSame('gpt-4o-mini', $assistant->model_name);
        $this->assertSame('azure', $assistant->voice_provider);
        $this->assertSame('hi-IN-SwaraNeural', $assistant->voice_id);
    }

    public function test_operator_routing_adds_vapi_handoff_tool_between_synced_assistants(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $salesAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sales Desk',
            'language_code' => 'en-US',
            'vapi_assistant_id' => 'asst_sales_123',
        ]);
        $supportAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Tech Support',
            'language_code' => 'en-US',
            'vapi_assistant_id' => 'asst_support_123',
        ]);

        $operator = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Main Operator',
            'preset_key' => 'steady_operator',
            'language_code' => 'en-US',
            'intake_params' => [
                'operator' => [
                    'enabled' => true,
                    'mode' => 'spoken_handoff',
                    'intro' => 'Thanks for calling Northline Support. Say sales, support, or German and I will connect you.',
                    'fallback_message' => 'Which team should I connect you with?',
                    'routes' => [
                        [
                            'label' => 'Sales',
                            'keywords' => 'sales, pricing, quote',
                            'assistant_id' => $salesAssistant->id,
                            'language_code' => 'en-US',
                        ],
                        [
                            'label' => 'Tech Support',
                            'keywords' => 'support, technical support, not working',
                            'assistant_id' => $supportAssistant->id,
                            'language_code' => 'en-US',
                        ],
                    ],
                ],
            ],
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $handoffTools = collect($payload['model']['tools'] ?? [])->where('type', 'handoff')->values();
                $handoffTool = $handoffTools->first();

                $this->assertCount(2, $handoffTools);
                $this->assertNotNull($handoffTool);
                $this->assertCount(1, $handoffTool['destinations']);
                $this->assertSame('handoff_to_sales_desk', $handoffTool['function']['name']);
                $this->assertStringContainsString('Caller phrases for this route: sales, pricing, quote', $handoffTool['function']['description']);
                $this->assertArrayHasKey('reason', $handoffTool['function']['parameters']['properties']);
                $this->assertArrayNotHasKey('destination', $handoffTool['function']['parameters']['properties']);
                $this->assertSame('assistant', $handoffTool['destinations'][0]['type']);
                $this->assertSame('asst_sales_123', $handoffTool['destinations'][0]['assistantId']);
                $this->assertSame('userAndAssistantMessages', $handoffTool['destinations'][0]['contextEngineeringPlan']['type']);
                $this->assertStringContainsString('Sales', $handoffTool['destinations'][0]['description']);
                $this->assertStringContainsString('sales, pricing, quote', $handoffTool['destinations'][0]['description']);
                $this->assertSame([], $handoffTool['messages']);
                $this->assertStringContainsString('OPERATOR ROUTING MODE', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('SILENT HANDOFF RULES', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Vapi-only spoken routing', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('The configured spoken routes below are the only choices this operator can offer', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('A phrase listed after "caller may say" counts as that configured route', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Route only to the exact configured choice', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('If no configured route label is that exact language, ask this exact next question', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('A language choice is permission to continue, not a department choice', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('handoff function: handoff_to_sales_desk', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Never answer "How can I help you?"', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Which team should I connect you with?', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Do not continue normal intake while routing is on', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('matching Vapi handoff destination', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('silently use the matching Vapi handoff destination', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Sales', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Do not tell callers they must press keypad buttons', $payload['model']['messages'][0]['content']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-operator-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($operator, $workspace, ['name' => 'Main Operator']);
    }

    public function test_operator_route_destination_uses_model_generated_first_message_for_silent_handoff(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $salesAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sales Desk',
            'language_code' => 'en-US',
            'first_message' => 'Thanks for calling sales. How can I help?',
            'vapi_assistant_id' => 'asst_sales_123',
        ]);

        AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Main Operator',
            'preset_key' => 'steady_operator',
            'language_code' => 'en-US',
            'intake_params' => [
                'operator' => [
                    'enabled' => true,
                    'routes' => [
                        [
                            'label' => 'Sales',
                            'keywords' => 'sales, pricing, quote',
                            'assistant_id' => $salesAssistant->id,
                            'language_code' => 'en-US',
                        ],
                    ],
                ],
            ],
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('updateAssistant')
            ->with('asst_sales_123', $this->callback(function (array $payload): bool {
                $this->assertSame('', $payload['firstMessage']);
                $this->assertSame('assistant-speaks-first-with-model-generated-message', $payload['firstMessageMode']);
                $this->assertStringContainsString('SILENT HANDOFF RULES', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Continue directly with the next useful question', $payload['model']['messages'][0]['content']);

                return true;
            }));

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($salesAssistant, $workspace, ['name' => 'Sales Desk']);
    }

    public function test_operator_route_destination_that_is_also_a_router_keeps_its_menu_question(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $salesAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sales Desk',
            'language_code' => 'en-US',
            'vapi_assistant_id' => 'asst_sales_123',
        ]);
        $englishRouter = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'English Router',
            'language_code' => 'en-US',
            'first_message' => 'Which line are you looking for: sales, tech support, or property maintenance?',
            'vapi_assistant_id' => 'asst_english_router_123',
            'intake_params' => [
                'operator' => [
                    'enabled' => true,
                    'routes' => [
                        [
                            'label' => 'Sales',
                            'keywords' => 'sales, pricing, quote',
                            'assistant_id' => $salesAssistant->id,
                            'language_code' => 'en-US',
                        ],
                    ],
                ],
            ],
        ]);

        AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Main Operator',
            'preset_key' => 'steady_operator',
            'language_code' => 'en-US',
            'intake_params' => [
                'operator' => [
                    'enabled' => true,
                    'routes' => [
                        [
                            'label' => 'English support',
                            'keywords' => 'english, support',
                            'assistant_id' => $englishRouter->id,
                            'language_code' => 'en-US',
                        ],
                    ],
                ],
            ],
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('updateAssistant')
            ->with('asst_english_router_123', $this->callback(function (array $payload): bool {
                $this->assertSame(
                    'Which line are you looking for: sales, tech support, or property maintenance?{{ knownCallerSuffix | default: "" }}',
                    $payload['firstMessage']
                );
                $this->assertSame('assistant-speaks-first', $payload['firstMessageMode']);
                $this->assertStringContainsString('The configured spoken routes below are the only choices this operator can offer', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Route only to the exact configured choice', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('If no configured route label is that exact language, ask this exact next question', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('A language choice is permission to continue, not a department choice', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('do not infer a downstream destination', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('handoff function: handoff_to_sales_desk', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Never answer "How can I help you?"', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('silently use the matching Vapi handoff destination', $payload['model']['messages'][0]['content']);

                return true;
            }));

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($englishRouter, $workspace, ['name' => 'English Router']);
    }

    public function test_operator_routing_with_multiple_route_languages_uses_multilingual_transcription(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'pro',
        ]);
        AssistantPreset::ensureDefaults();

        $englishAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'English Desk',
            'language_code' => 'en-US',
            'vapi_assistant_id' => 'asst_en_123',
        ]);
        $germanAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'German Desk',
            'language_code' => 'de-DE',
            'vapi_assistant_id' => 'asst_de_123',
        ]);

        $operator = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Main Operator',
            'preset_key' => 'steady_operator',
            'language_code' => 'en-US',
            'intake_params' => [
                'operator' => [
                    'enabled' => true,
                    'mode' => 'spoken_handoff',
                    'routes' => [
                        [
                            'label' => 'English support',
                            'keywords' => 'english, support',
                            'assistant_id' => $englishAssistant->id,
                            'language_code' => 'en-US',
                        ],
                        [
                            'label' => 'German support',
                            'keywords' => 'german, deutsch',
                            'assistant_id' => $germanAssistant->id,
                            'language_code' => 'de-DE',
                        ],
                    ],
                ],
            ],
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->exactly(4))
            ->method('createTool')
            ->willReturnOnConsecutiveCalls(['id' => 'tool-1'], ['id' => 'tool-2'], ['id' => 'tool-3'], ['id' => 'tool-4']);
        $client->expects($this->once())
            ->method('createAssistant')
            ->with($this->callback(function (array $payload): bool {
                $this->assertSame('deepgram', $payload['transcriber']['provider']);
                $this->assertSame('nova-3', $payload['transcriber']['model']);
                $this->assertSame('multi', $payload['transcriber']['language']);
                $this->assertArrayNotHasKey('fallbackPlan', $payload['transcriber']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-operator-multi-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($operator, $workspace, ['name' => 'Main Operator']);
    }
}
