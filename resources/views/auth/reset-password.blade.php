@extends('layouts.guest')

@section('title', 'tickIt - Reset Password')
@section('guest_eyebrow', 'Account recovery')
@section('guest_title', 'Choose a new password and get back in.')
@section('guest_copy', 'Reset your password and return to your workspace.')

@section('content')
    <div>
        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Secure reset</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Reset password</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">Set a new password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="tc-field">
            <label for="email" class="tc-field-label text-slate-200">Email address</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" autofocus autocomplete="username"
                class="tc-input-dark" placeholder="name@company.com" />
            @if($errors->first('email'))
                <p class="tc-error text-red-300">{{ $errors->first('email') }}</p>
            @endif
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="tc-field">
                <label for="password" class="tc-field-label text-slate-200">New password</label>
                <input id="password" type="password" name="password" autocomplete="new-password" class="tc-input-dark" placeholder="New password" />
                @if($errors->first('password'))
                    <p class="tc-error text-red-300">{{ $errors->first('password') }}</p>
                @endif
            </div>

            <div class="tc-field">
                <label for="password_confirmation" class="tc-field-label text-slate-200">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                    class="tc-input-dark" placeholder="Confirm password" />
                @if($errors->first('password_confirmation'))
                    <p class="tc-error text-red-300">{{ $errors->first('password_confirmation') }}</p>
                @endif
            </div>
        </div>

        <button type="submit" class="tc-btn-glow w-full justify-center !py-3 text-base">Reset password</button>
    </form>
@endsection
