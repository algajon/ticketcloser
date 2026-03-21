@extends('layouts.guest')

@section('title')
    ticketcloser • Verify Email
@endsection

@section('content')
    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-white">Verify your email</h2>
        <p class="text-sm mt-2" style="color:#94a3b8">
            We sent a 6-digit code to your email address. Enter it below to activate your account.
        </p>
    </div>

    @if(session('status') === 'verification-link-sent')
        <div class="mb-5 rounded-lg p-3 text-sm" style="background:rgba(16,185,129,0.12);color:#6ee7b7">
            A new verification code has been sent to your email.
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-lg p-3 text-sm" style="background:rgba(239,68,68,0.12);color:#fca5a5">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify.otp') }}" class="mb-6">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="otp" class="sr-only">Verification Code</label>
                <input id="otp" type="text" name="otp" required autofocus placeholder="XXXXXX"
                    class="tc-input text-center text-2xl tracking-[0.5em] font-mono py-3" maxlength="6" />
                @error('otp')
                    <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="tc-btn-primary w-full justify-center">
                Verify Account
            </button>
        </div>
    </form>

    <div class="flex items-center justify-between gap-3 border-t border-slate-800 pt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-sm hover:underline transition-colors" style="color:#94a3b8">
                Resend Code
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm hover:underline transition-colors text-slate-500">Sign out</button>
        </form>
    </div>
@endsection