@extends('layouts.saas')

@section('title', 'tickIt • Profile')
@section('header', 'Profile')

@section('content')
    <div class="max-w-3xl space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">Profile information</h2>
                <p class="mt-1 text-sm text-slate-500">Update your name, email address, and verification status.</p>
            </div>

            <form id="send-verification" method="POST" action="{{ route('verification.send') }}">
                @csrf
            </form>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label for="profile-name" class="mb-1 block text-sm font-medium text-slate-700">Name</label>
                    <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus
                        autocomplete="name"
                        class="tc-input" />
                    @if($errors->first('name'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('name') }}</p>
                    @endif
                </div>

                <div>
                    <label for="profile-email" class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                    <input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                        autocomplete="username"
                        class="tc-input" />
                    @if($errors->first('email'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('email') }}</p>
                    @endif

                    @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <p>Your email is unverified.</p>
                            <button form="send-verification" type="submit" class="mt-2 font-medium text-amber-900 underline underline-offset-2">
                                Resend verification code
                            </button>
                            @if(session('status') === 'verification-link-sent')
                                <p class="mt-2 text-emerald-700">A fresh verification code was sent.</p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="tc-btn-primary">
                        Save changes
                    </button>
                    @if(session('status') === 'profile-updated')
                        <span class="text-sm font-medium text-emerald-700">Saved.</span>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">Change password</h2>
                <p class="mt-1 text-sm text-slate-500">Use your current password to set a new one.</p>
            </div>

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="mb-1 block text-sm font-medium text-slate-700">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                        class="tc-input" />
                    @if($errors->first('current_password'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('current_password') }}</p>
                    @endif
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-slate-700">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password"
                        class="tc-input" />
                    @if($errors->first('password'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('password') }}</p>
                    @endif
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-slate-700">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                        class="tc-input" />
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                        Update password
                    </button>
                    @if(session('status') === 'password-updated')
                        <span class="text-sm font-medium text-emerald-700">Password updated.</span>
                    @endif
                </div>
            </form>
        </div>

        <div class="rounded-2xl border border-red-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-red-700">Delete account</h2>
                <p class="mt-1 text-sm text-slate-500">This permanently deletes your account and cannot be undone.</p>
            </div>

            <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label for="delete-password" class="mb-1 block text-sm font-medium text-slate-700">Confirm with your password</label>
                    <input id="delete-password" name="password" type="password" autocomplete="current-password"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-200" />
                    @if($errors->userDeletion->first('password'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->userDeletion->first('password') }}</p>
                    @endif
                </div>

                <button type="submit"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                    Delete my account
                </button>
            </form>
        </div>
    </div>
@endsection
