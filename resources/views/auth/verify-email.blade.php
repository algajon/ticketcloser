@extends('layouts.guest')

@section('title')
    ticketcloser • Verify Email
@endsection

@section('content')
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-white tracking-tight">Verify your email</h1>
        <p class="mt-2 text-sm text-slate-400">
            Enter the 6-digit code we sent to your email address to finish setting up your account.
        </p>
    </div>

    @if(session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
            A new verification code has been sent to your email address.
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.verify.otp') }}" class="space-y-4">
        @csrf

        <div class="space-y-1.5 text-left">
            <label for="otp" class="block text-[13px] font-medium text-slate-300">Verification code</label>
            <input id="otp" type="text" name="otp" value="{{ old('otp') }}" required maxlength="6" inputmode="numeric"
                autocomplete="one-time-code"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px] tracking-[0.35em]"
                placeholder="123456" />
            @if($errors->first('otp'))
                <p class="text-[13px] text-red-400 mt-1">{{ $errors->first('otp') }}</p>
            @endif
        </div>

        <button type="submit"
            class="w-full mt-2 px-4 py-2.5 bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg font-medium text-[14px] transition-all"
            style="box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);">
            Verify code
        </button>
    </form>

    <div class="mt-6 flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-[#f97316] hover:text-[#ea580c] transition-colors">
                Resend code
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="text-sm text-slate-400 hover:text-white underline underline-offset-2 transition-colors">
                Sign out
            </button>
        </form>
    </div>
@endsection