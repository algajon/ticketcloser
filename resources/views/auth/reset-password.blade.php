@extends('layouts.guest')

@section('title')
    ticketcloser • Reset Password
@endsection

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold text-white">Reset password</h1>
        <p class="text-sm mt-1" style="color:#94a3b8">Choose a new password for your account.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="space-y-1.5">
            <label for="email" class="block text-sm font-medium" style="color:#cbd5e1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" autofocus
                autocomplete="username" class="tc-input-dark" />
            @if($errors->first('email'))
            <p class="text-xs" style="color:#f87171">{{ $errors->first('email') }}</p>@endif
        </div>

        <div class="space-y-1.5">
            <label for="password" class="block text-sm font-medium" style="color:#cbd5e1">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" class="tc-input-dark" />
            @if($errors->first('password'))
            <p class="text-xs" style="color:#f87171">{{ $errors->first('password') }}</p>@endif
        </div>

        <div class="space-y-1.5">
            <label for="password_confirmation" class="block text-sm font-medium" style="color:#cbd5e1">Confirm new
                password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"
                class="tc-input-dark" />
            @if($errors->first('password_confirmation'))
            <p class="text-xs" style="color:#f87171">{{ $errors->first('password_confirmation') }}</p>@endif
        </div>

        <button type="submit" class="tc-btn-glow w-full mt-2" style="padding:0.625rem 1.5rem;font-size:0.875rem">Reset
            password</button>
    </form>
@endsection