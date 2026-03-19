@extends('layouts.guest')

@section('title')
    ticketcloser • Verify Email
@endsection

@section('content')
    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-white">Verify your email</h2>
        <p class="text-sm mt-2" style="color:#94a3b8">
            We sent a verification link to your email address. Click it to activate your account.
        </p>
    </div>

    @if(session('status') === 'verification-link-sent')
        <div class="mb-5 rounded-lg p-3 text-sm" style="background:rgba(16,185,129,0.12);color:#6ee7b7">A new verification link
            has been sent to your email address.</div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="tc-btn-glow" style="padding:0.5rem 1.25rem;font-size:0.875rem">Resend
                email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm hover:underline transition-colors" style="color:#94a3b8">Sign out</button>
        </form>
    </div>
@endsection