<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssistantDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_an_assistant_removes_remote_vapi_resources_and_local_records(): void
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
            'name' => 'Front Desk',
            'vapi_assistant_id' => 'asst_live_123',
            'vapi_tool_id' => 'tool_case_123',
            'vapi_booking_tool_id' => 'tool_booking_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
            'is_active' => true,
        ]);

        $deletedTools = [];

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->once())
            ->method('deletePhoneNumber')
            ->with('pn_live_123');
        $client->expects($this->once())
            ->method('deleteAssistant')
            ->with('asst_live_123');
        $client->expects($this->exactly(2))
            ->method('deleteTool')
            ->willReturnCallback(function (string $id) use (&$deletedTools): void {
                $deletedTools[] = $id;
            });

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->delete(route('app.assistant.destroy', [$workspace, $assistant]));

        $response
            ->assertRedirect(route('app.assistant.edit', $workspace, false))
            ->assertSessionHas('success', 'Assistant and linked phone number deleted.');

        $this->assertEqualsCanonicalizing(
            ['tool_case_123', 'tool_booking_123'],
            $deletedTools,
        );

        $this->assertDatabaseMissing('assistant_configs', [
            'id' => $assistant->id,
        ]);

        $this->assertDatabaseMissing('workspace_phone_numbers', [
            'assistant_id' => $assistant->id,
        ]);
    }
}
