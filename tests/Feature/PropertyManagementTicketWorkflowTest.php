<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\SupportCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Services\Tickets\TicketCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyManagementTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_persists_property_management_ticket_context_and_defaults_to_an_urgent_queue_stage(): void
    {
        $workspace = Workspace::factory()->create([
            'use_case' => 'property_management',
            'onboarding_step' => 'done',
        ]);

        $case = app(TicketCreationService::class)->createForWorkspace($workspace, [
            'title' => 'Leaking toilet in unit 6',
            'description' => 'Water is actively leaking onto the bathroom floor.',
            'category' => 'water leak',
            'priority' => 'critical',
            'requesterPhone' => '+16402298699',
            'requesterName' => 'Nick Dillon',
            'propertyCode' => '123 King Street West',
            'unit' => '6',
            'accessNotes' => 'Keys are in the lockbox.',
            'preferredVisitWindow' => 'Tomorrow at 3 PM',
        ]);

        $case->refresh();

        $this->assertSame(SupportCase::OPS_STAGE_URGENT_REVIEW, $case->ops_stage);
        $this->assertSame('Keys are in the lockbox.', $case->access_notes);
        $this->assertSame('Tomorrow at 3 PM', $case->preferred_visit_window);
        $this->assertSame('123 King Street West', data_get($case->structured_payload, 'propertyCode'));
        $this->assertSame('6', data_get($case->structured_payload, 'unit'));

        $this->assertDatabaseHas('contacts', [
            'workspace_id' => $workspace->id,
            'phone_e164' => '+16402298699',
            'name' => 'Nick Dillon',
            'property_code' => '123 King Street West',
            'unit' => '6',
        ]);
    }

    /** @test */
    public function it_shows_the_property_management_queue_context_on_the_ticket_pages(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $workspace = Workspace::factory()->create([
            'use_case' => 'property_management',
            'onboarding_step' => 'done',
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
            'case_number' => 'TC-PMQUEUE1',
            'title' => 'Leaking toilet',
            'description' => 'Water is leaking onto the floor.',
            'category' => 'water leak',
            'priority' => SupportCase::PRIORITY_HIGH,
            'status' => SupportCase::STATUS_NEW,
            'ops_stage' => SupportCase::OPS_STAGE_URGENT_REVIEW,
            'requester_phone' => '+16402298699',
            'access_notes' => 'Keys are in the lockbox.',
            'preferred_visit_window' => 'Tomorrow at 3 PM',
        ]);

        $indexResponse = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.tickets.index'));

        $indexResponse
            ->assertOk()
            ->assertSee('Urgent review')
            ->assertSee('123 King Street West')
            ->assertSee('Unit 6');

        $showResponse = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.tickets.show', $case));

        $showResponse
            ->assertOk()
            ->assertSee('Maintenance workflow')
            ->assertSee('Keys are in the lockbox.')
            ->assertSee('Tomorrow at 3 PM');
    }

    /** @test */
    public function it_updates_the_property_management_workflow_and_records_the_change(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $workspace = Workspace::factory()->create([
            'use_case' => 'property_management',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'case_number' => 'TC-PMWORK1',
            'title' => 'Broken AC',
            'description' => 'No cooling in the unit.',
            'priority' => SupportCase::PRIORITY_HIGH,
            'status' => SupportCase::STATUS_NEW,
            'ops_stage' => SupportCase::OPS_STAGE_NEW_INTAKE,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('app.cases.workflow.update', [$workspace, $case]), [
                'ops_stage' => SupportCase::OPS_STAGE_SCHEDULED,
                'vendor_name' => 'Acme HVAC',
                'vendor_phone' => '+15551230000',
                'preferred_visit_window' => 'Today between 4 PM and 6 PM',
                'access_notes' => 'Resident will buzz in.',
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Maintenance workflow updated.');

        $case->refresh();

        $this->assertSame(SupportCase::OPS_STAGE_SCHEDULED, $case->ops_stage);
        $this->assertSame('Acme HVAC', $case->vendor_name);
        $this->assertSame('+15551230000', $case->vendor_phone);
        $this->assertSame('Today between 4 PM and 6 PM', $case->preferred_visit_window);
        $this->assertSame('Resident will buzz in.', $case->access_notes);
        $this->assertSame(SupportCase::STATUS_IN_PROGRESS, $case->status);

        $this->assertDatabaseHas('case_events', [
            'workspace_id' => $workspace->id,
            'support_case_id' => $case->id,
            'type' => 'workflow_updated',
        ]);
    }

    public function test_it_suggests_recent_vendors_on_the_property_management_ticket_detail_page(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $workspace = Workspace::factory()->create([
            'use_case' => 'property_management',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'name' => 'Jon Nila',
            'phone_e164' => '+16402298699',
            'property_code' => '123 King Street West',
            'unit' => '6',
        ]);

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'case_number' => 'TC-PMSUG1',
            'title' => 'Sink leak',
            'description' => 'Kitchen sink is leaking.',
            'category' => 'plumbing',
            'priority' => SupportCase::PRIORITY_HIGH,
            'status' => SupportCase::STATUS_NEW,
        ]);

        SupportCase::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'case_number' => 'TC-PMSUG2',
            'title' => 'Older plumbing issue',
            'description' => 'Toilet leak in the same property.',
            'category' => 'plumbing',
            'priority' => SupportCase::PRIORITY_NORMAL,
            'status' => SupportCase::STATUS_RESOLVED,
            'vendor_name' => 'Acme Plumbing',
            'vendor_phone' => '+15551230000',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.tickets.show', $case));

        $response
            ->assertOk()
            ->assertSee('Suggested vendors')
            ->assertSee('Acme Plumbing')
            ->assertSee('Use vendor');
    }
}
