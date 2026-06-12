<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\Subscription;
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

    private const PAID_PLAN_REQUIRED_MESSAGE = 'Assistant creation is available on paid plans only. Upgrade to add an assistant.';

    public function test_free_workspace_cannot_open_create_assistant_screen(): void
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

        $response = $this
            ->actingAs($user)
            ->get(route('app.assistant.create', $workspace));

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace))
            ->assertSessionHas('error', self::PAID_PLAN_REQUIRED_MESSAGE);
    }

    public function test_free_workspace_cannot_create_assistant(): void
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
            ->assertSessionHas('error', self::PAID_PLAN_REQUIRED_MESSAGE);

        $this->assertDatabaseMissing('assistant_configs', [
            'workspace_id' => $workspace->id,
            'name' => 'Blocked Assistant',
        ]);
    }

    public function test_paid_plan_without_active_subscription_is_sent_to_billing_before_creating_assistant(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->never())->method('provisionAssistantAndToolForConfig');
        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.assistant.store', $workspace), [
                'name' => 'Blocked Lapsed Assistant',
            ]);

        $response
            ->assertRedirect(route('app.billing.plans'))
            ->assertSessionHas('error', 'Your subscription has expired. Please choose a plan to continue.');

        $this->assertDatabaseMissing('assistant_configs', [
            'workspace_id' => $workspace->id,
            'name' => 'Blocked Lapsed Assistant',
        ]);
    }

    public function test_free_workspace_cannot_duplicate_assistant(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);
        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Existing Assistant',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->never())->method('provisionAssistantAndToolForConfig');
        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.assistant.duplicate', [$workspace, $assistant]));

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace))
            ->assertSessionHas('error', self::PAID_PLAN_REQUIRED_MESSAGE);

        $this->assertSame(1, AssistantConfig::query()->where('workspace_id', $workspace->id)->count());
    }

    public function test_active_paid_workspace_can_create_assistant(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'onboarding_step' => 'done',
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_active_assistant_create',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->once())->method('provisionAssistantAndToolForConfig');
        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.assistant.store', $workspace), [
                'name' => 'Paid Assistant',
            ]);

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace))
            ->assertSessionHas('success', 'Assistant + tool synced to Vapi.');
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
