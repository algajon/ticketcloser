<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\SupportCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_the_latest_synced_assistant_and_phone_when_building_launch_state(): void
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
            ->assertSee('Assistant live')
            ->assertSee('Number live')
            ->assertSee('Connect your calendar')
            ->assertSee('Make a test call');
    }

    public function test_dashboard_shows_live_banner_when_core_launch_steps_are_done(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
            'use_case' => 'customer_support',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Your assistant is live')
            ->assertSee('See live number');
    }

    public function test_property_management_dashboard_shows_maintenance_queue_and_next_visit(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
            'use_case' => 'property_management',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'name' => 'Nick Dillon',
            'phone_e164' => '+16402298699',
            'property_code' => '123 King Street West',
            'unit' => '6',
        ]);

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'case_number' => 'TC-DASHPM1',
            'title' => 'Leaking toilet',
            'priority' => SupportCase::PRIORITY_CRITICAL,
            'status' => SupportCase::STATUS_NEW,
            'ops_stage' => SupportCase::OPS_STAGE_URGENT_REVIEW,
            'requester_phone' => '+16402298699',
        ]);

        CalendarEvent::create([
            'workspace_id' => $workspace->id,
            'case_id' => $case->id,
            'contact_id' => $contact->id,
            'provider' => 'calendly',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addMinutes(30),
            'timezone' => 'UTC',
            'status' => 'created',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Maintenance priority queue')
            ->assertSee('Urgent review')
            ->assertSee('123 King Street West')
            ->assertSee('Next scheduled visit');
    }
}
