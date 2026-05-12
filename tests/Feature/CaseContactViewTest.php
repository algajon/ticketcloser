<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\SupportCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseContactViewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shows_the_linked_contact_on_the_case_detail_page(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
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
        ]);

        $case = SupportCase::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'case_number' => 'TC-CONTACT1',
            'title' => 'Leaking toilet',
            'description' => 'Water is leaking onto the floor.',
            'status' => SupportCase::STATUS_NEW,
            'priority' => SupportCase::PRIORITY_HIGH,
            'source' => SupportCase::SOURCE_VOICE,
            'requester_phone' => '+16402298699',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.tickets.show', $case));

        $response
            ->assertOk()
            ->assertSee('Linked contact')
            ->assertSee('Nick Dillon')
            ->assertSee(route('app.contacts.show', [$workspace, $contact]), false);
    }
}
