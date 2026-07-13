@extends('layouts.guest')

@section('title', 'tickIt - Create Account')
@section('guest_layout', 'centered')
@section('auth_width', 'max-w-md')

@section('content')
    <div>
        <div class="text-sm font-medium text-slate-400">Create account</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Create your account</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">First we verify your email. Then we help you set up the call line in a few simple choices.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            @if(request('discount_code'))
                <div class="sm:col-span-2 rounded-[1.25rem] border border-orange-300/20 bg-orange-400/10 px-4 py-3 text-sm leading-6 text-orange-100">
                    Promo code <span class="font-semibold tracking-[0.12em]">{{ strtoupper((string) request('discount_code')) }}</span> is attached to this signup.
                </div>
            @endif

            <div class="tc-field sm:col-span-2">
                <label for="name" class="tc-field-label text-slate-200">Full name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                    class="tc-input-dark" placeholder="Jane Smith" />
                @if($errors->first('name'))
                    <p class="tc-error text-red-300">{{ $errors->first('name') }}</p>
                @endif
            </div>

            <div class="tc-field sm:col-span-2">
                <label for="email" class="tc-field-label text-slate-200">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email', request('email')) }}" required autocomplete="username"
                    class="tc-input-dark" placeholder="name@company.com" />
                @if($errors->first('email'))
                    <p class="tc-error text-red-300">{{ $errors->first('email') }}</p>
                @endif
            </div>

            <div class="tc-field">
                <label for="password" class="tc-field-label text-slate-200">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    class="tc-input-dark" placeholder="Choose a password" />
                <p class="tc-help text-slate-400">Use at least 8 characters.</p>
                @if($errors->first('password'))
                    <p class="tc-error text-red-300">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <div class="tc-field">
                <label for="password_confirmation" class="tc-field-label text-slate-200">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                    class="tc-input-dark" placeholder="Repeat your password" />
                @if($errors->first('password_confirmation'))
                    <p class="tc-error text-red-300">{{ $errors->first('password_confirmation') }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-3 pt-1">
            <label for="terms" class="flex cursor-pointer items-start gap-3 text-sm leading-6 text-slate-300">
                <input id="terms" type="checkbox" name="terms" value="1" required {{ old('terms') ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-white/10 bg-slate-950/20 text-orange-500 focus:ring-orange-500 focus:ring-offset-0" />
                <span>
                    I agree to the
                    <a href="{{ route('terms') }}" target="_blank" class="font-medium text-orange-300 transition hover:text-orange-200">terms and conditions</a>.
                </span>
            </label>

            <label for="marketing_opt_in" class="flex cursor-pointer items-start gap-3 text-sm leading-6 text-slate-300">
                <input id="marketing_opt_in" type="checkbox" name="marketing_opt_in" value="1" {{ old('marketing_opt_in') ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-white/10 bg-slate-950/20 text-orange-500 focus:ring-orange-500 focus:ring-offset-0" />
                <span>
                    Send me product updates and the tickIt newsletter.
                </span>
            </label>
        </div>
        @if($errors->first('terms'))
            <p class="tc-error text-red-300">{{ $errors->first('terms') }}</p>
        @endif

        <div class="rounded-[1.25rem] border border-white/10 bg-white/[0.04] px-4 py-3 text-sm leading-6 text-slate-300">
            <span class="font-semibold text-white">Next:</span>
            verify your email, choose what calls you handle, then open the dashboard.
        </div>

        <button type="submit" class="tc-btn-glow mt-3 w-full justify-center !py-3 text-base">
            Create account
        </button>

        <p class="text-center text-sm text-slate-400">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-orange-300 transition hover:text-orange-200">Sign in</a>
        </p>
    </form>
@endsection
