<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_marks_setup_complete_when_a_later_assistant_and_phone_are_the_completed_ones(): void
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

        $staleAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Old Draft Assistant',
        ]);

        $liveAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $staleAssistant->id,
            'e164' => null,
            'vapi_phone_number_id' => null,
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $liveAssistant->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.dashboard'));

        $response
            ->assertOk()
            ->assertDontSee('Get started')
            ->assertSee('1 synced to Vapi');
    }
}
