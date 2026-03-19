@extends('layouts.guest')

@section('title')
    ticketcloser • Confirm Password
@endsection

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-white">Confirm password</h1>
        <p class="text-sm mt-1" style="color:#94a3b8">This is a secure area. Please confirm your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div class="space-y-1.5">
            <label for="password" class="block text-sm font-medium" style="color:#cbd5e1">Password</label>
            <input id="password" type="password" name="password" autofocus autocomplete="current-password"
                class="tc-input-dark" />
            @if($errors->first('password'))
            <p class="text-xs" style="color:#f87171">{{ $errors->first('password') }}</p>@endif
        </div>

        <button type="submit" class="tc-btn-glow w-full" style="padding:0.625rem 1.5rem;font-size:0.875rem">Confirm</button>
    </form>
@endsection