@extends('layouts.saas')

@section('title')
    ticketcloser • Profile
@endsection

@section('header')
    Profile
@endsection

@section('content')
    <div class="tc-page-header">
        <h1>Profile</h1>
        <p>Update your personal details and password.</p>
    </div>

    <div class="space-y-4 max-w-2xl">

        <div class="tc-card p-6">
            <h2 class="tc-h3 mb-4">Profile information</h2>

            <form id="send-verification" method="POST" action="{{ route('verification.send') }}">@csrf</form>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')

                <div class="space-y-1.5">
                    <label for="profile-name" class="block text-sm font-medium text-slate-800">Name</label>
                    <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}" autofocus
                        autocomplete="name" class="tc-input" />
                    @if($errors->get('name'))
                    <p class="text-xs text-danger">{{ $errors->first('name') }}</p>@endif
                </div>

                <div class="space-y-1.5">
                    <label for="profile-email" class="block text-sm font-medium text-slate-800">Email</label>
                    <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}"
                        autocomplete="username" class="tc-input" />
                    @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail())
                        <div class="mt-2">
                            <p class="text-sm text-warning-fg">Your email is unverified.
                                <button form="send-verification"
                                    class="underline underline-offset-2 text-primary hover:text-primary-hover transition-colors text-sm">Resend
                                    verification email</button>
                            </p>
                            @if(session('status') === 'verification-link-sent')
                                <p class="mt-1.5 text-sm text-success-fg font-medium">Verification link sent!</p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="tc-btn-primary">Save changes</button>
                    @if(session('status') === 'profile-updated')
                        <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                            class="text-sm text-success-fg font-medium">Saved</span>
                    @endif
                </div>
            </form>
        </div>

        <div class="tc-card p-6">
            <h2 class="tc-h3 mb-4">Change password</h2>
            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <div class="space-y-1.5">
                    <label for="current_password" class="block text-sm font-medium text-slate-800">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                        class="tc-input" />
                    @if($errors->get('current_password'))
                    <p class="text-xs text-danger">{{ $errors->first('current_password') }}</p>@endif
                </div>

                <div class="space-y-1.5">
                    <label for="new_password" class="block text-sm font-medium text-slate-800">New password</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" class="tc-input" />
                    @if($errors->get('password'))
                    <p class="text-xs text-danger">{{ $errors->first('password') }}</p>@endif
                </div>

                <div class="space-y-1.5">
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-800">Confirm new
                        password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        autocomplete="new-password" class="tc-input" />
                    @if($errors->get('password_confirmation'))
                    <p class="text-xs text-danger">{{ $errors->first('password_confirmation') }}</p>@endif
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="tc-btn-primary">Update password</button>
                    @if(session('status') === 'password-updated')
                        <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                            class="text-sm text-success-fg font-medium">Updated</span>
                    @endif
                </div>
            </form>
        </div>

        <div x-data="{ confirm: false }" class="tc-card p-6">
            <h2 class="tc-h3 text-danger mb-1">Delete account</h2>
            <p class="tc-small mb-4">Permanently delete your account and all workspaces. This cannot be undone.</p>

            <div x-show="!confirm">
                <button class="tc-btn-danger" @click="confirm = true">Delete my account</button>
            </div>

            <div x-show="confirm" x-cloak class="space-y-3">
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <div class="font-medium mb-1">Are you absolutely sure?</div>
                    <div>This will permanently delete your account. There is no undo.</div>
                </div>
                <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-3">
                    @csrf
                    @method('delete')
                    <div class="space-y-1.5">
                        <label for="delete-password" class="block text-sm font-medium text-slate-800">Enter your password to
                            confirm</label>
                        <input id="delete-password" name="password" type="password" placeholder="Your current password"
                            autocomplete="current-password" class="tc-input" />
                        @if($errors->get('password'))
                        <p class="text-xs text-danger">{{ $errors->first('password') }}</p>@endif
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="tc-btn-danger">Yes, delete my account</button>
                        <button type="button" class="tc-btn-ghost" @click.prevent="confirm = false">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection