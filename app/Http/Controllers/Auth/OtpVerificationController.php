<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpVerificationMail;

class OtpVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('app.dashboard', absolute: false));
        }

        if ($user->otp_code === $request->otp && $user->otp_expires_at && $user->otp_expires_at->isFuture()) {
            $user->markEmailAsVerified();
            $user->otp_code = null;
            $user->otp_expires_at = null;
            $user->save();

            return redirect()->intended(route('app.onboarding.company', absolute: false));
        }

        return back()->with('error', 'The verification code provided is invalid or has expired.');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('app.dashboard', absolute: false));
        }

        $otp = sprintf('%06d', mt_rand(100000, 999999));
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        Mail::to($user->email)->send(new OtpVerificationMail($otp));

        return back()->with('status', 'verification-link-sent');
    }
}
