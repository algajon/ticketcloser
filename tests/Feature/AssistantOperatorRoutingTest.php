<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\AssistantPreset;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantOperatorRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_routing_settings_are_saved_and_hydrated_on_edit(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'integration_token' => 'token-123',
            'plan_key' => 'pro',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_operator_routing',
            'plan_key' => 'pro',
            'status' => 'active',
        ]);

        AssistantPreset::ensureDefaults();

        $destination = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sales Desk',
            'language_code' => 'de-DE',
            'vapi_assistant_id' => 'asst_sales_123',
        ]);

        $operator = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Main Operator',
            'preset_key' => 'bright_guide',
            'language_code' => 'en-US',
            'model_name' => 'gpt-4o-mini',
            'voice_provider' => 'vapi',
            'voice_id' => 'Emma',
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionAssistantAndToolForConfig')
            ->with(
                $this->callback(fn (AssistantConfig $config) => $config->is($operator)),
                $this->callback(fn (Workspace $candidate) => $candidate->is($workspace)),
                $this->callback(function (array $data) use ($destination): bool {
                    $this->assertTrue(data_get($data, 'intake_params.operator.enabled'));
                    $this->assertSame('spoken_handoff', data_get($data, 'intake_params.operator.mode'));
                    $this->assertSame('Connect callers to the right desk.', data_get($data, 'intake_params.operator.intro'));
                    $this->assertSame('Sales', data_get($data, 'intake_params.operator.routes.0.label'));
                    $this->assertSame('sales, pricing', data_get($data, 'intake_params.operator.routes.0.keywords'));
                    $this->assertSame($destination->id, data_get($data, 'intake_params.operator.routes.0.assistant_id'));
                    $this->assertSame('de-DE', data_get($data, 'intake_params.operator.routes.0.language_code'));

                    return true;
                })
            )
            ->willReturnCallback(function (AssistantConfig $config, Workspace $workspace, array $data): AssistantConfig {
                $config->fill([
                    'name' => $data['name'],
                    'first_message' => $data['first_message'] ?? null,
                    'system_prompt' => $data['system_prompt'] ?? null,
                    'voice_provider' => $data['voice_provider'] ?? null,
                    'voice_id' => $data['voice_id'] ?? null,
                    'language_code' => $data['language_code'] ?? null,
                    'model_name' => $data['model_name'] ?? null,
                    'preset_key' => $data['preset_key'] ?? null,
                    'override_params' => $data['override_params'] ?? [],
                    'intake_params' => $data['intake_params'] ?? [],
                    'fallback_phone' => $data['fallback_phone'] ?? null,
                ])->save();

                return $config->fresh();
            });

        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.assistant.update', [$workspace, $operator]), [
                'assistant_id' => $operator->id,
                'name' => 'Main Operator',
                'first_message' => 'Thanks for calling. Which team do you need?',
                'system_prompt' => 'Route the caller first.',
                'voice_provider' => 'azure',
                'voice_id' => 'de-DE-KlausNeural',
                'language_code' => 'de-DE',
                'model_name' => 'gpt-4.1',
                'preset_key' => 'steady_operator',
                'fallback_phone' => '+12025550100',
                'intake_params' => [
                    'operator' => [
                        'enabled' => '1',
                        'mode' => 'spoken_handoff',
                        'intro' => 'Connect callers to the right desk.',
                        'fallback_message' => 'Which team should I connect you with?',
                        'routes' => [
                            [
                                'label' => 'Sales',
                                'keywords' => 'sales, pricing',
                                'assistant_id' => $destination->id,
                                'language_code' => 'de-DE',
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('app.assistant.edit', $workspace, false));

        $operator->refresh();
        $this->assertSame('steady_operator', $operator->preset_key);
        $this->assertSame('gpt-4.1', $operator->model_name);
        $this->assertSame('azure', $operator->voice_provider);
        $this->assertSame('de-DE-KlausNeural', $operator->voice_id);
        $this->assertTrue(data_get($operator->intake_params, 'operator.enabled'));
        $this->assertSame($destination->id, data_get($operator->intake_params, 'operator.routes.0.assistant_id'));

        $this
            ->actingAs($user)
            ->get(route('app.assistant.show', [$workspace, $operator]))
            ->assertOk()
            ->assertSee('Operator routing')
            ->assertSee('tc-accent-control', false)
            ->assertSee('Connect callers to the right desk.')
            ->assertSee('sales, pricing')
            ->assertSee('Sales Desk')
            ->assertSee('de-DE-KlausNeural')
            ->assertSee('steady_operator');
    }
}
