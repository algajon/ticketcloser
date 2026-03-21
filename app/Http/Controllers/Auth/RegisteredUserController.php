<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\VoiceConfig;
use App\Models\IntakeConfig;
use Illuminate\Support\Str;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    // ...

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // ✅ Create workspace for this company (MVP: company name = user name + "Co")
        $baseName = Str::of($user->name)->trim()->append(' Co')->toString();
        $slugBase = Str::slug($baseName);

        $slug = $slugBase;
        $i = 2;
        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $i;
            $i++;
        }

        $workspace = Workspace::create([
            'name' => $baseName,
            'slug' => $slug,
            'default_timezone' => 'America/New_York',
            'case_label' => 'Ticket',
            'credits_balance' => 0,
            'plan_key' => 'free',
            'onboarding_step' => 'company',
            'integration_token' => Str::random(48),
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        VoiceConfig::create([
            'workspace_id' => $workspace->id,
            'provider' => 'vapi',
            'transcript_enabled' => true,
            'recording_enabled' => false,
        ]);

        IntakeConfig::create([
            'workspace_id' => $workspace->id,
            'system_prompt' => "You are a customer support intake agent.\nCollect: callerName, callbackNumber, category, priority, description.\nConfirm summary then create a ticket.",
            'required_fields' => ['callerName', 'callbackNumber', 'category', 'priority', 'description'],
            'category_options' => ['billing', 'technical', 'account', 'general'],
            'priority_rules' => ['not urgent' => 'low', 'urgent' => 'high'],
        ]);

        // Generate 6-digit OTP
        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        // Send OTP
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OtpVerificationMail($otp));

        Auth::login($user);

        return redirect()->route('app.onboarding.company');
    }

}
