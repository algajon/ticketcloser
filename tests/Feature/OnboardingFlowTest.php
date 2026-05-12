<?php

namespace Tests\Feature;

use App\Models\IntakeConfig;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_workspace_setup_applies_use_case_defaults_and_redirects_to_assistant_builder(): void
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'company',
            'use_case' => null,
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('app.onboarding.company.save'), [
                'name' => 'Harbor Property Group',
                'slug' => 'harbor-property-group',
                'default_timezone' => 'America/New_York',
                'case_label' => 'Request',
                'use_case' => 'property_management',
            ]);

        $workspace->refresh();
        $intakeConfig = IntakeConfig::query()->where('workspace_id', $workspace->id)->first();

        $response
            ->assertRedirect(route('app.assistant.create', $workspace))
            ->assertSessionHas('success', 'Workspace saved. We prefilled your first assistant based on your workflow.');

        $this->assertSame('Harbor Property Group', $workspace->name);
        $this->assertSame('harbor-property-group', $workspace->slug);
        $this->assertSame('property_management', $workspace->use_case);
        $this->assertSame('done', $workspace->onboarding_step);
        $this->assertSame('Request', $workspace->case_label);
        $this->assertNotNull($intakeConfig);
        $this->assertStringContainsString('tenant maintenance requests', strtolower($intakeConfig->system_prompt));
        $this->assertContains('Property or building', $intakeConfig->required_fields);
        $this->assertContains('maintenance', $intakeConfig->category_options);
        $this->assertSame('critical', $intakeConfig->priority_rules['no heat']);
    }

    public function test_other_workflow_requires_custom_details(): void
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
            ->withSession(['current_workspace_id' => $workspace->id])
            ->from(route('app.onboarding.company'))
            ->post(route('app.onboarding.company.save'), [
                'name' => 'Custom Ops',
                'slug' => 'custom-ops',
                'default_timezone' => 'America/New_York',
                'case_label' => 'Case',
                'use_case' => 'other',
                'use_case_details' => '',
            ]);

        $response
            ->assertRedirect(route('app.onboarding.company'))
            ->assertSessionHasErrors(['use_case_details']);
    }
}
