@extends('layouts.guest')

@section('title', 'tickIt - Confirm Password')
@section('guest_eyebrow', 'Protected action')
@section('guest_title', 'Confirm your identity before entering this secure area.')
@section('guest_copy', 'Sensitive account changes and billing controls require a fresh password confirmation so workspace access stays protected.')

@section('content')
    <div>
        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400">Security checkpoint</div>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-white">Confirm password</h1>
        <p class="mt-3 text-sm leading-6 text-slate-300">This workspace action needs a quick security confirmation before we continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-8 space-y-5">
        @csrf

        <div class="tc-field">
            <label for="password" class="tc-field-label text-slate-200">Password</label>
            <input id="password" type="password" name="password" autofocus autocomplete="current-password"
                class="tc-input-dark" placeholder="Enter your password" />
            @if($errors->first('password'))
                <p class="tc-error text-red-300">{{ $errors->first('password') }}</p>
            @endif
        </div>

        <button type="submit" class="tc-btn-glow w-full justify-center !py-3 text-base">Confirm</button>
    </form>
@endsection
