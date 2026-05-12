@extends('layouts.guest')

@section('title', 'tickIt - Reset Link')
@section('guest_eyebrow', 'Account recovery')
@section('guest_title', 'Get a reset link by email.')
@section('guest_copy', 'We will send a secure link so you can choose a new password.')

@section('content')
    <div>
        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Password recovery</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Forgot password?</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">Enter the email for your account and we will send a secure reset link.</p>
    </div>

    @if(session('status'))
        <div class="mt-6 rounded-[1.35rem] border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf

        <div class="tc-field">
            <label for="email" class="tc-field-label text-slate-200">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autofocus autocomplete="username"
                class="tc-input-dark" placeholder="name@company.com" />
            @if($errors->first('email'))
                <p class="tc-error text-red-300">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <button type="submit" class="tc-btn-glow w-full justify-center !py-3 text-base">Send reset link</button>
    </form>

    <div class="mt-8 border-t border-white/10 pt-6 text-sm text-slate-400">
        Remembered it?
        <a href="{{ route('login') }}" class="font-medium text-orange-300 transition hover:text-orange-200">Back to sign in</a>
    </div>
@endsection
