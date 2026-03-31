<?php

namespace Tests\Feature\Auth;

use App\Mail\OtpVerificationMail;
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
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        $this->assertAuthenticated();
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->otp_code);
        $this->assertNotNull($user->otp_expires_at);
        $this->assertDatabaseHas('workspace_memberships', [
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        Mail::assertSent(OtpVerificationMail::class, function (OtpVerificationMail $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->otpCode === $user->otp_code;
        });
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
