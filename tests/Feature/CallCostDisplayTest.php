<?php

namespace Tests\Feature;

use App\Models\CallEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallCostDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_analytics_shows_total_and_average_costs(): void
    {
        [$user, $workspace] = $this->authenticatedWorkspaceOwner();

        CallEvent::create([
            'workspace_id' => $workspace->id,
            'from_number' => '+16402298699',
            'to_number' => '+12164053754',
            'duration_seconds' => 91,
            'cost' => 0.0123,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        CallEvent::create([
            'workspace_id' => $workspace->id,
            'from_number' => '+16402298698',
            'to_number' => '+12164053754',
            'duration_seconds' => 127,
            'cost' => 0.0210,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.calls.analytics', $workspace));

        $response
            ->assertOk()
            ->assertSee('Call cost (30 days)')
            ->assertSee('$0.03 USD')
            ->assertSee('Avg $0.02 USD');
    }

    public function test_call_detail_shows_exact_call_cost(): void
    {
        [$user, $workspace] = $this->authenticatedWorkspaceOwner();

        $call = CallEvent::create([
            'workspace_id' => $workspace->id,
            'from_number' => '+16402298699',
            'to_number' => '+12164053754',
            'duration_seconds' => 91,
            'cost' => 0.0123,
            'meta' => [
                'language' => [
                    'configured' => [
                        'code' => 'fr-FR',
                    ],
                    'transcript' => [
                        'code' => 'fr-FR',
                        'source_label' => 'Detected from call',
                    ],
                    'transcriber' => [
                        'provider' => 'deepgram',
                        'model' => 'nova-3',
                        'label' => 'Deepgram nova-3',
                    ],
                ],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.calls.show', [$workspace, $call]));

        $response
            ->assertOk()
            ->assertSee('Call cost')
            ->assertSee('$0.01 USD')
            ->assertSee('Transcript language')
            ->assertSee('French')
            ->assertSee('Speech stack')
            ->assertSee('Deepgram nova-3');
    }

    private function authenticatedWorkspaceOwner(): array
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

        return [$user, $workspace];
    }
}
