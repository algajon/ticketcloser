<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\AssistantPreset;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
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
                $this->assertSame(1.08, $payload['voice']['speed']);
                $this->assertStringContainsString('RETURNING CALLER RULES', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Use any caller context already provided in your system note first', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Nice to speak with you again', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Use any caller context already provided in your system note first', $payload['model']['messages'][0]['content']);
                $this->assertStringContainsString('Never say "Just a sec"', $payload['model']['messages'][0]['content']);
                $this->assertTrue($payload['backgroundSpeechDenoisingPlan']['smartDenoisingPlan']['enabled']);
                $this->assertSame('deepgram', $payload['transcriber']['provider']);
                $this->assertSame('nova-3-general', $payload['transcriber']['model']);
                $this->assertTrue($payload['transcriber']['numerals']);
                $this->assertSame(0.62, $payload['startSpeakingPlan']['waitSeconds']);
                $this->assertSame(2, $payload['stopSpeakingPlan']['numWords']);
                $this->assertSame(1.1, $payload['stopSpeakingPlan']['backoffSeconds']);

                return true;
            }))
            ->willReturn(['id' => 'assistant-1']);

        $service = new VapiProvisioningService($client);
        $service->provisionAssistantAndToolForConfig($assistant, $workspace, ['name' => 'Front Desk']);
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
                $this->assertSame(1.22, $payload['voice']['speed']);
                $this->assertArrayNotHasKey('transcriber', $payload);
                $this->assertSame(0.6, $payload['model']['temperature']);
                $this->assertSame(380, $payload['model']['maxTokens']);
                $this->assertSame('Thanks for calling Northline Support. How can I help today?{{ knownCallerSuffix | default: "" }}', $payload['firstMessage']);
                $this->assertSame(0.44, $payload['startSpeakingPlan']['waitSeconds']);
                $this->assertSame(3, $payload['stopSpeakingPlan']['numWords']);
                $this->assertSame(0.37, $payload['stopSpeakingPlan']['voiceSeconds']);
                $this->assertSame(1.05, $payload['stopSpeakingPlan']['backoffSeconds']);

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
                $this->assertSame(1.12, $payload['voice']['speed']);

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

    public function test_admin_owned_free_plan_is_not_clamped_to_standard_model_and_voice(): void
    {
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'name' => 'Northline Support',
            'plan_key' => 'free',
        ]);
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);
        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $admin->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
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
                $this->assertSame('openai', $payload['voice']['provider']);
                $this->assertSame('marin', $payload['voice']['voiceId']);

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
        $this->assertSame('openai', $assistant->voice_provider);
        $this->assertSame('marin', $assistant->voice_id);
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
}
