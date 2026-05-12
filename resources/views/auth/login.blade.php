@extends('layouts.guest')

@section('title', 'tickIt - Sign In')
@section('guest_layout', 'centered')

@section('content')
    <div>
        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Welcome back</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Sign in</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">Use your email and password.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div class="tc-field">
            <label for="email" class="tc-field-label text-slate-200">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                class="tc-input-dark" placeholder="name@company.com" />
            @if($errors->first('email'))
                <p class="tc-error text-red-300">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <div class="tc-field">
            <div class="flex items-center justify-between gap-3">
                <label for="password" class="tc-field-label text-slate-200">Password</label>
                @if(Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-orange-300 transition hover:text-orange-200">Forgot password?</a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                class="tc-input-dark" placeholder="Enter your password" />
            @if($errors->first('password'))
                <p class="tc-error text-red-300">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <label for="remember_me" class="flex cursor-pointer items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
            <input id="remember_me" type="checkbox" name="remember" class="h-4 w-4 rounded border-white/10 bg-slate-950/20 text-orange-500 focus:ring-orange-500 focus:ring-offset-0">
            <span>Keep me signed in</span>
        </label>

        <button type="submit" class="tc-btn-glow mt-3 w-full justify-center !py-3 text-base">
            Sign in
        </button>
    </form>

    <div class="mt-8 border-t border-white/10 pt-6 text-sm text-slate-400">
        New here?
        <a href="{{ route('register') }}" class="font-medium text-orange-300 transition hover:text-orange-200">Create one here</a>
    </div>
@endsection