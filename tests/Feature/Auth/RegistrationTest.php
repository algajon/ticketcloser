<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpVerificationMail;
use App\Mail\WelcomeToTickItMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertAuthenticated();
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_expires_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame('2026-03-31', $user->terms_version);
        $this->assertNull($user->marketing_opted_in_at);
        $this->assertDatabaseHas('workspace_memberships', [
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        Mail::assertSent(OtpVerificationMail::class, function (OtpVerificationMail $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->otpCode === $user->otp_code;
        });
        Mail::assertSent(WelcomeToTickItMail::class, function (WelcomeToTickItMail $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->user->is($user);
        });
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_marketing_opt_in_is_saved_when_requested(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Marketing User',
            'email' => 'marketing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
            'marketing_opt_in' => '1',
        ]);

        $user = User::where('email', 'marketing@example.com')->firstOrFail();

        $this->assertNotNull($user->marketing_opted_in_at);
    }
}
