<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Mail\WelcomeToTickItMail;
use App\Models\IntakeConfig;
use App\Models\User;
use App\Models\VoiceConfig;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'terms_accepted_at' => now(),
            'terms_version' => '2026-03-31',
            'marketing_opted_in_at' => $request->boolean('marketing_opt_in') ? now() : null,
        ]);

        $baseName = Str::of($user->name)->trim()->append(' Co')->toString();
        $slugBase = Str::slug($baseName);

        $slug = $slugBase;
        $suffix = 2;
        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $suffix;
            $suffix++;
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
            'recording_enabled' => true,
        ]);

        IntakeConfig::create([
            'workspace_id' => $workspace->id,
            'system_prompt' => "You are a customer support intake agent.\nCollect: callerName, callbackNumber, category, priority, description.\nConfirm summary then create a ticket.",
            'required_fields' => ['callerName', 'callbackNumber', 'category', 'priority', 'description'],
            'category_options' => ['billing', 'technical', 'account', 'general'],
            'priority_rules' => ['not urgent' => 'low', 'urgent' => 'high'],
        ]);

        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        Mail::to($user->email)->send(new OtpVerificationMail($otp));
        Mail::to($user->email)->send(new WelcomeToTickItMail($user, $workspace));

        Auth::login($user);
        $request->session()->put('current_workspace_id', $workspace->id);

        return redirect()->route('dashboard');
    }
}
