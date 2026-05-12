<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantFreeLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_workspace_cannot_open_create_assistant_screen_after_hitting_minute_limit(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        UsageEvent::create([
            'workspace_id' => $workspace->id,
            'minutes' => 5,
            'event_type' => 'call',
            'occurred_at' => now(),
            'metadata' => ['vapi_call_id' => 'limit_reached_1'],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('app.assistant.create', $workspace));

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace))
            ->assertSessionHas('error', 'This free workspace has reached its 5 minute limit. Upgrade to add another assistant.');
    }

    public function test_free_workspace_cannot_create_another_assistant_after_hitting_minute_limit(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        UsageEvent::create([
            'workspace_id' => $workspace->id,
            'minutes' => 5,
            'event_type' => 'call',
            'occurred_at' => now(),
            'metadata' => ['vapi_call_id' => 'limit_reached_2'],
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->never())->method('provisionAssistantAndToolForConfig');
        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.assistant.store', $workspace), [
                'name' => 'Blocked Assistant',
            ]);

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace))
            ->assertSessionHas('error', 'This free workspace has reached its 5 minute limit. Upgrade to add another assistant.');

        $this->assertDatabaseMissing('assistant_configs', [
            'workspace_id' => $workspace->id,
            'name' => 'Blocked Assistant',
        ]);
    }

    public function test_admin_users_can_open_create_assistant_screen_after_free_workspace_hits_minute_limit(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        UsageEvent::create([
            'workspace_id' => $workspace->id,
            'minutes' => 20,
            'event_type' => 'call',
            'occurred_at' => now(),
            'metadata' => ['vapi_call_id' => 'admin_limit_bypass_1'],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('app.assistant.create', $workspace));

        $response
            ->assertOk()
            ->assertSee('New assistant');
    }

    public function test_admin_users_can_create_assistant_after_free_workspace_hits_minute_limit(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        UsageEvent::create([
            'workspace_id' => $workspace->id,
            'minutes' => 20,
            'event_type' => 'call',
            'occurred_at' => now(),
            'metadata' => ['vapi_call_id' => 'admin_limit_bypass_2'],
        ]);

        $assistant = new AssistantConfig([
            'workspace_id' => $workspace->id,
            'name' => 'Allowed Assistant',
        ]);
        $assistant->id = 999;

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionAssistantAndToolForConfig')
            ->willReturn($assistant);
        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.assistant.store', $workspace), [
                'name' => 'Allowed Assistant',
            ]);

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace))
            ->assertSessionHas('success', 'Assistant + tool synced to Vapi.');
    }
}
