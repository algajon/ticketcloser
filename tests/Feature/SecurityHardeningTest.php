<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_calendar_callback_rejects_tampered_workspace_state(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);
        $otherWorkspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $expectedState = [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'nonce' => 'secure-nonce',
        ];
        $tamperedState = [
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $user->id,
            'nonce' => 'secure-nonce',
        ];

        $response = $this
            ->actingAs($user)
            ->withSession([
                'current_workspace_id' => $workspace->id,
                'google_oauth_state' => $expectedState,
            ])
            ->get(route('app.calendar.google.callback', [
                'code' => 'oauth-code-123',
                'state' => urlencode(Crypt::encryptString(json_encode($tamperedState))),
            ]));

        $response->assertForbidden();
        Http::assertNothingSent();
        $this->assertDatabaseMissing('calendar_connections', [
            'workspace_id' => $otherWorkspace->id,
            'provider' => 'google',
        ]);
    }

    public function test_agent_cannot_regenerate_integration_tokens(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_AGENT,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('app.integrations.token.regenerate', $workspace));

        $response->assertForbidden();
    }

    public function test_agent_cannot_access_billing_pages(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'free',
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_AGENT,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.billing.index'));

        $response->assertForbidden();
    }

    public function test_workspace_helper_is_rate_limited(): void
    {
        config(['services.openai.api_key' => null]);

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

        $this->actingAs($user)->withSession(['current_workspace_id' => $workspace->id]);

        for ($i = 0; $i < 20; $i++) {
            $this->postJson(route('app.helper.chat', $workspace), [
                'message' => 'How do I get to assistants?',
            ])->assertOk();
        }

        $this->postJson(route('app.helper.chat', $workspace), [
            'message' => 'One more question',
        ])->assertStatus(429);
    }

    public function test_password_reset_uses_a_generic_status_message(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        $knownResponse = $this->post(route('password.email'), [
            'email' => 'known@example.com',
        ]);

        $knownResponse
            ->assertRedirect()
            ->assertSessionHas('status', 'If an account exists for that email, we have sent a password reset link.');

        $unknownResponse = $this->post(route('password.email'), [
            'email' => 'unknown@example.com',
        ]);

        $unknownResponse
            ->assertRedirect()
            ->assertSessionHas('status', 'If an account exists for that email, we have sent a password reset link.');
    }

    public function test_password_reset_requests_are_rate_limited(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.email'), [
                'email' => 'known@example.com',
            ])->assertSessionHas('status', 'If an account exists for that email, we have sent a password reset link.');
        }

        $this->post(route('password.email'), [
            'email' => 'known@example.com',
        ])->assertSessionHasErrors('email');
    }
}
