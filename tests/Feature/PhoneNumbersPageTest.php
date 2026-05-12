<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiClient;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PhoneNumbersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_numbers_page_defaults_to_the_assistant_that_has_a_phone_number(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['plan_key' => 'free']);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Draft Assistant',
        ]);

        $liveAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $liveAssistant->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('app.phone_numbers.index', $workspace));

        $response
            ->assertOk()
            ->assertSee('+14155550123')
            ->assertSee('Live Assistant');
    }

    public function test_store_phone_number_redirects_back_to_the_selected_assistant(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['plan_key' => 'free']);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        $record = WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
            'is_active' => true,
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionPhoneNumber')
            ->willReturn($record);

        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'area_code' => '415',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionMissing('phone_activation_countdown');
    }

    public function test_store_phone_number_flashes_activation_countdown_for_new_number(): void
    {
        Carbon::setTestNow('2026-03-31 10:15:00');

        try {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['plan_key' => 'free']);

            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceMembership::ROLE_OWNER,
            ]);

            $assistant = AssistantConfig::create([
                'workspace_id' => $workspace->id,
                'name' => 'Fresh Assistant',
                'vapi_assistant_id' => 'asst_fresh_123',
            ]);

            $record = new WorkspacePhoneNumber([
                'workspace_id' => $workspace->id,
                'assistant_id' => $assistant->id,
                'e164' => '+14155550124',
                'vapi_phone_number_id' => 'pn_fresh_123',
                'is_active' => true,
            ]);

            $service = $this->createMock(VapiProvisioningService::class);
            $service->expects($this->once())
                ->method('provisionPhoneNumber')
                ->willReturn($record);

            $this->app->instance(VapiProvisioningService::class, $service);

            $response = $this
                ->actingAs($user)
                ->post(route('app.phone_numbers.store', $workspace), [
                    'assistant_id' => $assistant->id,
                    'area_code' => '415',
                ]);

            $response->assertSessionHas('phone_activation_countdown', function (array $countdown) use ($assistant) {
                return $countdown['assistant_id'] === $assistant->id
                    && $countdown['ends_at'] === Carbon::now()->addSeconds(180)->toIso8601String();
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_phone_numbers_page_shows_activation_countdown_for_newly_provisioned_number(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['plan_key' => 'free']);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Fresh Assistant',
            'vapi_assistant_id' => 'asst_fresh_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'e164' => '+14155550124',
            'vapi_phone_number_id' => 'pn_fresh_123',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession([
                'phone_activation_countdown' => [
                    'assistant_id' => $assistant->id,
                    'ends_at' => now()->addSeconds(180)->toIso8601String(),
                ],
            ])
            ->get(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ]));

        $response
            ->assertOk()
            ->assertSee('Activation countdown')
            ->assertSee('This number was just added.')
            ->assertSee('Activating');
    }

    public function test_existing_business_number_can_create_a_forwarding_target_immediately(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Forwarding Assistant',
            'vapi_assistant_id' => 'asst_forward_123',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->once())
            ->method('createPhoneNumber')
            ->with($this->callback(function (array $payload) use ($assistant) {
                return $payload['provider'] === 'vapi'
                    && $payload['assistantId'] === $assistant->vapi_assistant_id;
            }))
            ->willReturn([
                'id' => 'pn_forward_123',
                'number' => '+14155550999',
            ]);

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'existing_business_number',
                'forwarding_number' => '+14155550111',
                'auto_forwarding_target' => '1',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionHas('success', 'Forward your existing number to +14155550999 whenever you are ready to switch calls over.');

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'provisioning_mode' => 'existing_business_number',
            'forwarding_number' => '+14155550111',
            'vapi_phone_number_id' => 'pn_forward_123',
            'e164' => '+14155550999',
            'is_active' => true,
        ]);
    }
}
