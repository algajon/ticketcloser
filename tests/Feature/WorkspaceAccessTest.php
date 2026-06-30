<?php

namespace Tests\Feature;

use App\Mail\WelcomeToTickItMail;
use App\Models\IntakeConfig;
use App\Models\User;
use App\Models\VoiceConfig;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_one_unconfigured_workspace_are_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
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
        $user->markEmailAsVerified();
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

    public function test_creating_a_workspace_applies_use_case_defaults_and_redirects_to_plans_for_free_workspace(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $response = $this
            ->actingAs($user)
            ->post(route('app.workspaces.store'), [
                'name' => 'Northwind Support',
                'slug' => 'northwind-support',
                'default_timezone' => 'America/New_York',
                'use_case' => 'it_support',
            ]);

        $workspace = Workspace::query()->where('name', 'Northwind Support')->firstOrFail();
        $intakeConfig = IntakeConfig::query()->where('workspace_id', $workspace->id)->first();

        $response
            ->assertRedirect(route('app.billing.plans'))
            ->assertSessionHas('current_workspace_id', $workspace->id)
            ->assertSessionHas('success', 'Workspace created. Choose a paid plan to create and sync your first assistant.');

        $this->assertDatabaseHas('workspace_memberships', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);
        $this->assertSame('it_support', $workspace->use_case);
        $this->assertSame('Ticket', $workspace->case_label);
        $this->assertSame('done', $workspace->onboarding_step);
        $this->assertNotNull($intakeConfig);
        $this->assertStringContainsString('inbound it support calls', strtolower($intakeConfig->system_prompt));
        $this->assertContains('System affected', $intakeConfig->required_fields);
        $this->assertTrue(VoiceConfig::query()->where('workspace_id', $workspace->id)->exists());
        Mail::assertSent(WelcomeToTickItMail::class, function (WelcomeToTickItMail $mail) use ($user, $workspace) {
            return $mail->hasTo($user->email)
                && $mail->user->is($user)
                && $mail->workspace->is($workspace);
        });
        $this->assertNotNull($user->fresh()->welcome_email_sent_at);
    }

    public function test_free_users_cannot_create_a_second_workspace(): void
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

        $response = $this
            ->actingAs($user)
            ->post(route('app.workspaces.store'), [
                'name' => 'Second Workspace',
                'slug' => 'second-workspace',
                'default_timezone' => 'America/New_York',
                'use_case' => 'customer_support',
            ]);

        $response
            ->assertRedirect(route('app.workspaces.index'))
            ->assertSessionHas('error', 'Free workspaces are limited to 1 workspace. Upgrade to add more.');

        $this->assertDatabaseMissing('workspaces', [
            'name' => 'Second Workspace',
        ]);
    }

    public function test_admin_users_can_create_a_second_workspace_even_if_their_existing_workspace_is_free(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);
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

        $response = $this
            ->actingAs($user)
            ->post(route('app.workspaces.store'), [
                'name' => 'Second Workspace',
                'slug' => 'second-workspace',
                'default_timezone' => 'America/New_York',
                'use_case' => 'customer_support',
            ]);

        $newWorkspace = Workspace::query()->where('name', 'Second Workspace')->firstOrFail();

        $response
            ->assertRedirect(route('app.assistant.create', $newWorkspace))
            ->assertSessionHas('current_workspace_id', $newWorkspace->id);

        $this->assertDatabaseHas('workspace_memberships', [
            'workspace_id' => $newWorkspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);
    }

    public function test_creating_other_workspace_requires_custom_details(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);
        $user->markEmailAsVerified();

        $response = $this
            ->actingAs($user)
            ->from(route('app.workspaces.create'))
            ->post(route('app.workspaces.store'), [
                'name' => 'Custom Ops',
                'slug' => 'custom-ops',
                'default_timezone' => 'America/New_York',
                'use_case' => 'other',
                'use_case_details' => '',
            ]);

        $response
            ->assertRedirect(route('app.workspaces.create'))
            ->assertSessionHasErrors(['use_case_details']);
    }
}
