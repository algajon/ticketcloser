<?php

namespace Tests\Feature\Auth;

use App\Mail\WelcomeToTickItMail;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_welcome_email_is_sent_after_verification_when_workspace_is_configured(): void
    {
        Mail::fake();
        Event::fake();

        $user = User::factory()->unverified()->create();
        $workspace = Workspace::factory()->create([
            'onboarding_step' => 'done',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl);

        Mail::assertSent(WelcomeToTickItMail::class, function (WelcomeToTickItMail $mail) use ($user, $workspace) {
            return $mail->hasTo($user->email)
                && $mail->user->is($user)
                && $mail->workspace->is($workspace);
        });
        $this->assertNotNull($user->fresh()->welcome_email_sent_at);
    }

    public function test_welcome_email_contains_docs_sales_and_calendly_links(): void
    {
        config([
            'services.tickit_sales.email' => 'sales@example.com',
            'services.tickit_sales.calendly_url' => 'https://calendly.com/jon/tickit-launch',
        ]);

        $user = User::factory()->create(['name' => 'Launch User']);
        $workspace = Workspace::factory()->create(['name' => 'Launch Workspace']);

        $html = html_entity_decode((new WelcomeToTickItMail($user, $workspace))->render());

        $this->assertStringContainsString(route('docs'), $html);
        $this->assertStringContainsString('mailto:sales@example.com?subject=Help%20me%20launch%20tickIt', $html);
        $this->assertStringContainsString('https://calendly.com/jon/tickit-launch', $html);
        $this->assertStringContainsString('Book with Jon', $html);
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_can_be_verified_with_a_valid_otp(): void
    {
        $user = User::factory()->unverified()->create([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->actingAs($user)->post(route('verification.verify.otp'), [
            'otp' => '123456',
        ]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->otp_code);
        $this->assertNull($user->fresh()->otp_expires_at);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_welcome_email_waits_for_workspace_setup_after_otp_verification(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(15),
        ]);
        $workspace = Workspace::factory()->create([
            'onboarding_step' => 'company',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $this->actingAs($user)->post(route('verification.verify.otp'), [
            'otp' => '123456',
        ]);

        Mail::assertNotSent(WelcomeToTickItMail::class);
        $this->assertNull($user->fresh()->welcome_email_sent_at);
    }
}
