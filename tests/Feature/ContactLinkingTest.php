<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Workspace;
use App\Services\Contacts\ContactLinkingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_phone_reuses_one_contact_and_extends_matching_name(): void
    {
        $workspace = Workspace::factory()->create();
        $service = app(ContactLinkingService::class);

        $first = $service->resolveForWorkspace($workspace, '+1 (640) 222-9869', 'John', null);
        $second = $service->resolveForWorkspace($workspace, '+16402229869', 'John Nila', null);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);

        $this->assertDatabaseHas('contacts', [
            'workspace_id' => $workspace->id,
            'name' => 'John Nila',
            'phone_e164' => '+16402229869',
        ]);
        $this->assertDatabaseCount('contacts', 1);
    }

    public function test_existing_contact_name_is_not_replaced_by_unrelated_name(): void
    {
        $workspace = Workspace::factory()->create();
        $service = app(ContactLinkingService::class);

        $first = $service->resolveForWorkspace($workspace, '+1 (640) 222-9869', 'Pam Beesly', null);
        $second = $service->resolveForWorkspace($workspace, '+16402229869', 'John Nila', null);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);

        $this->assertDatabaseHas('contacts', [
            'id' => $first->id,
            'name' => 'Pam Beesly',
        ]);
    }

    public function test_blank_contact_is_upgraded_when_a_name_arrives_later(): void
    {
        $workspace = Workspace::factory()->create();
        $service = app(ContactLinkingService::class);

        $blank = Contact::create([
            'workspace_id' => $workspace->id,
            'phone_e164' => '+16402229869',
        ]);

        $resolved = $service->resolveForWorkspace($workspace, '+16402229869', 'John Nila', null);

        $this->assertSame($blank->id, $resolved->id);

        $this->assertDatabaseHas('contacts', [
            'id' => $blank->id,
            'name' => 'John Nila',
        ]);
    }

    public function test_it_rejects_obvious_transcript_fragments_as_names(): void
    {
        $workspace = Workspace::factory()->create();
        $service = app(ContactLinkingService::class);

        $contact = $service->resolveForWorkspace($workspace, '+16402229869', 'Gonna Bring It', null);

        $this->assertNotNull($contact);
        $this->assertNull($contact->name);

        $this->assertDatabaseHas('contacts', [
            'workspace_id' => $workspace->id,
            'phone_e164' => '+16402229869',
            'name' => null,
        ]);
    }

    public function test_it_rejects_prompt_fragment_names(): void
    {
        $workspace = Workspace::factory()->create();
        $service = app(ContactLinkingService::class);

        $contact = $service->resolveForWorkspace($workspace, '+16402229869', 'Pam Current Prompt', null);

        $this->assertNotNull($contact);
        $this->assertNull($contact->name);
    }

    public function test_it_rejects_on_file_phrases_as_names_and_preserves_existing_contact_name(): void
    {
        $workspace = Workspace::factory()->create();
        $service = app(ContactLinkingService::class);

        $contact = $service->resolveForWorkspace($workspace, '+16402229869', 'Jon Nila', null);
        $sameContact = $service->resolveForWorkspace($workspace, '+1 (640) 222-9869', 'Already On File', null);

        $this->assertSame($contact?->id, $sameContact?->id);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact?->id,
            'name' => 'Jon Nila',
        ]);
    }
}
