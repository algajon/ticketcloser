@extends('layouts.guest')

@section('title')
    ticketcloser • Forgot Password
@endsection

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-white">Forgot password?</h1>
        <p class="text-sm mt-1" style="color:#94a3b8">Enter your email and we'll send you a reset link.</p>
    </div>

    @if(session('status'))
        <div class="mb-5 rounded-lg p-3 text-sm" style="background:rgba(16,185,129,0.12);color:#6ee7b7">{{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div class="space-y-1.5">
            <label for="email" class="block text-sm font-medium" style="color:#cbd5e1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autofocus autocomplete="username"
                class="tc-input-dark" />
            @if($errors->first('email'))
            <p class="text-xs" style="color:#f87171">{{ $errors->first('email') }}</p>@endif
        </div>

        <button type="submit" class="tc-btn-glow w-full mt-2" style="padding:0.625rem 1.5rem;font-size:0.875rem">Send reset
            link</button>
    </form>

    <p class="mt-6 text-center text-sm" style="color:#64748b">
        <a href="{{ route('login') }}" class="font-medium hover:underline" style="color:#f97316">Back to sign in</a>
    </p>
@endsection