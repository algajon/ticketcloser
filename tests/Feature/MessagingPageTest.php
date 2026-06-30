<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_messaging_page_shows_sidebar_entry_and_sms_ready_assistant(): void
    {
        config(['services.vapi.key' => 'vapi_test_key']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'onboarding_step' => 'done',
            'name' => 'Northline Support',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_messaging_ready',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Maintenance Desk',
            'vapi_assistant_id' => 'asst_sms_ready',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'e164' => '+18005550123',
            'vapi_phone_number_id' => 'pn_sms_ready',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.messaging.index', $workspace));

        $response
            ->assertOk()
            ->assertSee('Messaging')
            ->assertSee('SMS-ready assistants')
            ->assertSee('SMS ready')
            ->assertSee('Maintenance Desk')
            ->assertSee('+18005550123')
            ->assertSee('Edit assistant')
            ->assertSee('Manage number');
    }
}
