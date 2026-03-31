<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_one_unconfigured_workspace_are_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'company',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('app.dashboard'));

        $response
            ->assertRedirect(route('app.onboarding.company'))
            ->assertSessionHas('current_workspace_id', $workspace->id);
    }

    public function test_users_with_multiple_workspaces_must_select_one_before_entering_the_app(): void
    {
        $user = User::factory()->create();
        $firstWorkspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);
        $secondWorkspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        foreach ([$firstWorkspace, $secondWorkspace] as $workspace) {
            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceMembership::ROLE_OWNER,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('app.dashboard'));

        $response
            ->assertRedirect(route('app.workspaces.index'))
            ->assertSessionHas('error', 'Choose a workspace to continue.');
    }

    public function test_creating_a_workspace_selects_it_and_redirects_to_onboarding(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('app.workspaces.store'), [
                'name' => 'Northwind Support',
            ]);

        $workspace = Workspace::query()->where('name', 'Northwind Support')->firstOrFail();

        $response
            ->assertRedirect(route('app.onboarding.company'))
            ->assertSessionHas('current_workspace_id', $workspace->id);

        $this->assertDatabaseHas('workspace_memberships', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);
    }
}
