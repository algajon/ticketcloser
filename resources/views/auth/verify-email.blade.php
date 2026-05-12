@extends('layouts.guest')

@section('title', 'tickIt - Verify Email')
@section('guest_eyebrow', 'Account verification')
@section('guest_title', 'Verify your email to continue.')
@section('guest_copy', 'Enter the six-digit code from your inbox to finish setting up your account.')

@section('content')
    <div>
        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Email verification</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Verify your email</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">Enter the six-digit code we sent to your inbox.</p>
    </div>

    @if(session('status') === 'verification-link-sent')
        <div class="mt-6 rounded-[1.35rem] border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-200">
            A new verification code has been sent to your email address.
        </div>
    @endif

    @if(session('error'))
        <div class="mt-6 rounded-[1.35rem] border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm font-medium text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify.otp') }}" class="mt-8 space-y-5">
        @csrf

        <div class="tc-field">
            <label for="otp" class="tc-field-label text-slate-200">Verification code</label>
            <input id="otp" type="text" name="otp" value="{{ old('otp') }}" required maxlength="6" inputmode="numeric"
                autocomplete="one-time-code" class="tc-input-dark text-center tracking-[0.35em]" placeholder="123456" />
            <p class="tc-help text-slate-400">Use the code from your email. It expires soon.</p>
            @if($errors->first('otp'))
                <p class="tc-error text-red-300">{{ $errors->first('otp') }}</p>
            @endif
        </div>

        <button type="submit" class="tc-btn-glow w-full justify-center !py-3 text-base">Verify code</button>
    </form>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-6">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-orange-300 transition hover:text-orange-200">Resend code</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-400 transition hover:text-white">Sign out</button>
        </form>
    </div>
@endsection
