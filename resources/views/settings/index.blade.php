@extends('layouts.saas')

@section('title', 'ticketcloser • Settings')

@section('header', 'Settings')

@section('content')
@php
    $tabs = [
        'profile'      => ['label' => 'Profile', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>'],
        'security'     => ['label' => 'Security', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>'],
        'workspace'    => ['label' => 'Workspace', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008V7.5z" /></svg>'],
        'integrations' => ['label' => 'API & Providers', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>'],
        'calendar'     => ['label' => 'Calendar', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>'],
        // 'payment'      => ['label' => 'Payment Methods', 'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>'],
    ];
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Tab Navigation --}}
    <div class="flex flex-wrap gap-1 border-b border-slate-200 mb-8 -mx-1 overflow-x-auto" role="tablist">
        @foreach($tabs as $key => $t)
            <a href="{{ route('app.settings', ['tab' => $key]) }}"
               role="tab"
               aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
               class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-all duration-150 whitespace-nowrap
                      {{ $tab === $key
                          ? 'border-indigo-500 text-indigo-600 bg-indigo-50/50'
                          : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                <span class="{{ $tab === $key ? 'text-indigo-500' : 'text-slate-400' }}">{!! $t['icon'] !!}</span>
                {{ $t['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Tab content --}}
    @if($tab === 'profile')
        {{-- Profile Information --}}
        <div class="tc-card p-6 mb-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">Profile Information</h2>
            <p class="text-sm text-slate-500 mb-5">Update your name and email address.</p>

            <form method="POST" action="{{ route('app.settings.profile') }}" class="space-y-4 max-w-lg">
                @csrf
                @method('PATCH')

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                    @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="tc-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>

        {{-- Account Deletion --}}
        <div class="tc-card p-6" x-data="{ confirm: false }">
            <h2 class="text-base font-bold text-red-600 mb-1">Danger Zone</h2>
            <p class="text-sm text-slate-500 mb-4">Permanently delete your account and all associated data. This cannot be undone.</p>

            <div x-show="!confirm">
                <button class="px-4 py-2 text-sm font-semibold rounded-lg border-2 border-red-200 text-red-600 hover:bg-red-50 transition-colors" @click="confirm = true">
                    Delete My Account
                </button>
            </div>

            <div x-show="confirm" x-cloak class="space-y-3">
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <div class="font-medium mb-1">Are you absolutely sure?</div>
                    <div>This will permanently delete your account, workspaces, and all data. There is no undo.</div>
                </div>
                <form method="POST" action="{{ route('app.settings.destroy') }}" class="space-y-3">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="delete-password" class="block text-sm font-medium text-slate-700 mb-1">Enter your password to confirm</label>
                        <input id="delete-password" name="password" type="password" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition max-w-sm" />
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-red-600 text-white hover:bg-red-700 transition-colors">Yes, Delete</button>
                        <button type="button" class="px-4 py-2 text-sm font-medium rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" @click.prevent="confirm = false">Cancel</button>
                    </div>
                </form>
            </div>
        </div>


    @elseif($tab === 'security')
        {{-- Change Password --}}
        <div class="tc-card p-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">Change Password</h2>
            <p class="text-sm text-slate-500 mb-5">Use a strong, unique password to protect your account.</p>

            <form method="POST" action="{{ route('app.settings.password') }}" class="space-y-4 max-w-lg">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                    @error('current_password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                    @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="tc-btn-primary">Update Password</button>
                </div>
            </form>
        </div>


    @elseif($tab === 'workspace')
        {{-- Workspace Preferences --}}
        <div class="tc-card p-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">Workspace Preferences</h2>
            <p class="text-sm text-slate-500 mb-5">Customize your workspace identity and defaults.</p>

            <form method="POST" action="{{ route('app.settings.workspace') }}" class="space-y-4 max-w-lg">
                @csrf
                @method('PATCH')

                <div>
                    <label for="ws-name" class="block text-sm font-medium text-slate-700 mb-1">Company Name</label>
                    <input id="ws-name" name="name" type="text" value="{{ old('name', $workspace->name ?? '') }}" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                    @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="ws-tz" class="block text-sm font-medium text-slate-700 mb-1">Default Timezone</label>
                    <select id="ws-tz" name="default_timezone" required
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition">
                        @foreach(['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','America/Anchorage','Pacific/Honolulu','Europe/London','Europe/Paris','Europe/Berlin','Asia/Tokyo','Asia/Shanghai','Australia/Sydney','UTC'] as $tz)
                            <option value="{{ $tz }}" {{ ($workspace->default_timezone ?? 'America/New_York') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="ws-label" class="block text-sm font-medium text-slate-700 mb-1">Case Label</label>
                    <input id="ws-label" name="case_label" type="text" value="{{ old('case_label', $workspace->case_label ?? 'Ticket') }}" required maxlength="40"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition" />
                    <p class="text-xs text-slate-400 mt-1">What you call your support items (e.g. Ticket, Case, Issue).</p>
                </div>

                <div class="pt-1">
                    <button type="submit" class="tc-btn-primary">Save Workspace Settings</button>
                </div>
            </form>
        </div>

        {{-- Workspace info --}}
        <div class="tc-card p-6 mt-6">
            <h2 class="text-base font-bold text-slate-900 mb-3">Workspace Info</h2>
            <div class="grid sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-slate-500">Slug</span>
                    <div class="font-mono text-slate-700 mt-0.5">{{ $workspace->slug ?? 'N/A' }}</div>
                </div>
                {{-- 
                <div>
                    <span class="text-slate-500">Plan</span>
                    <div class="font-medium text-slate-700 mt-0.5">{{ $workspace->planLabel() ?? 'Free' }}</div>
                </div>
                --}}
                <div>
                    <span class="text-slate-500">Created</span>
                    <div class="text-slate-700 mt-0.5">{{ $workspace->created_at?->format('M d, Y') ?? 'N/A' }}</div>
                </div>
                {{-- 
                <div>
                    <span class="text-slate-500">Credits</span>
                    <div class="text-slate-700 mt-0.5">{{ number_format($workspace->credits_balance ?? 0) }}</div>
                </div>
                <div>
                    <span class="text-slate-500">Minutes Used</span>
                    <div class="text-slate-700 mt-0.5">{{ number_format($workspace->vapiMinutesUsed()) }}</div>
                </div>
                --}}
            </div>
        </div>


    @elseif($tab === 'integrations')
        {{-- API Token --}}
        <div class="tc-card p-6 mb-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">API Integration Token</h2>
            <p class="text-sm text-slate-500 mb-4">Send as <code class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">Authorization: Bearer &lt;token&gt;</code> on all API requests.</p>

            <div class="flex items-center gap-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono mb-4">
                <span class="flex-1 truncate text-slate-700 select-all">{{ $integrationToken ?? 'N/A' }}</span>
                <button onclick="navigator.clipboard.writeText('{{ $integrationToken ?? '' }}')" class="flex-shrink-0 text-xs text-slate-500 hover:text-slate-800 font-medium transition-colors">Copy</button>
            </div>

            @if($workspace)
                <form method="POST" action="{{ route('app.integrations.token.regenerate', $workspace) }}"
                    onsubmit="return confirm('Regenerate token? Existing integrations using the old token will stop working immediately.')">
                    @csrf
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 mb-3">
                        Regenerating breaks any active Vapi tool connections. Only do this if the token is compromised.
                    </div>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg border-2 border-red-200 text-red-600 hover:bg-red-50 transition-colors">
                        Regenerate Token
                    </button>
                </form>
            @endif
        </div>

        {{-- Vapi Provider --}}
        <div class="tc-card p-6 mb-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-violet-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" /></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Vapi — Voice AI Provider</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Powers all voice assistant interactions and call handling.</p>
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase">Webhook URL</span>
                    <div class="flex items-center gap-2 rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono mt-1">
                        <span class="flex-1 truncate text-slate-700 select-all">{{ $vapiWebhookUrl ?? 'Not configured' }}</span>
                        <button onclick="navigator.clipboard.writeText('{{ $vapiWebhookUrl ?? '' }}')" class="flex-shrink-0 text-xs text-slate-500 hover:text-slate-800 font-medium">Copy</button>
                    </div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase">Status</span>
                    <div class="mt-1">
                        @if(config('services.vapi.key'))
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-green-50 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Connected
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Not configured
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Stripe Provider --}}
        {{--
        <div class="tc-card p-6">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold text-slate-900">Stripe — Payments</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Handles all subscription billing and payment processing.</p>
                </div>
            </div>
            <div>
                <span class="text-xs font-semibold text-slate-500 uppercase">Status</span>
                <div class="mt-1">
                    @if(config('services.stripe.key'))
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-green-50 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Connected
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Not configured
                        </span>
                    @endif
                </div>
            </div>
        </div>
        --}}


    @elseif($tab === 'calendar')
        {{-- Google Calendar --}}
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="tc-card overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center border border-slate-200 shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#296b38ff"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 15.907 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z" /></svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Google Calendar</h3>
                            <p class="text-xs text-slate-500">Native Integration</p>
                        </div>
                    </div>
                    @if(isset($connections['google']))
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-semibold bg-green-50 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Connected
                        </span>
                    @endif
                </div>
                <div class="p-5 flex-grow flex flex-col justify-between">
                    <p class="text-sm text-slate-600 mb-5 leading-relaxed">Connect your Google account to automatically insert recognized bookings directly into your primary calendar.</p>
                    <a href="{{ route('app.calendar.google.auth') }}" class="tc-btn-primary w-fit inline-flex items-center gap-2 text-sm">
                        {{ isset($connections['google']) ? 'Reconnect Google' : 'Connect with Google' }}
                    </a>
                </div>
            </div>

            {{-- Calendly --}}
            <div class="tc-card overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center border border-slate-200 shadow-sm text-blue-600 font-black text-lg">C</div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Calendly</h3>
                            <p class="text-xs text-slate-500">Scheduling Link</p>
                        </div>
                    </div>
                    @if(isset($connections['calendly']))
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-xs font-semibold bg-green-50 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Configured
                        </span>
                    @endif
                </div>
                <div class="flex-grow flex flex-col">
                    <form action="{{ route('app.calendar.calendly.save') }}" method="POST" class="p-5 flex-grow flex flex-col justify-between">
                        @csrf
                        <div>
                            <p class="text-sm text-slate-600 mb-4 leading-relaxed">Paste your Calendly scheduling URL to embed it in the dashboard.</p>
                            <label for="calendly_link" class="block text-sm font-medium text-slate-700 mb-1">Scheduling Link URL</label>
                            <input type="url" name="calendly_link" id="calendly_link" required
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition"
                                placeholder="https://calendly.com/your-name/30min"
                                value="{{ old('calendly_link', $connections['calendly']->calendly_scheduling_link ?? '') }}">
                            @error('calendly_link') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="tc-btn-primary">Save Calendly Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


    @elseif($tab === 'payment')
        {{-- Payment Methods --}}
        <div class="tc-card p-6 mb-6">
            <h2 class="text-base font-bold text-slate-900 mb-1">Payment Methods</h2>
            <p class="text-sm text-slate-500 mb-5">Manage your credit cards for automatic subscription billing.</p>

            @if(!empty($paymentMethods))
                <div class="space-y-3 mb-6">
                    @foreach($paymentMethods as $pm)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-7 rounded bg-white border border-slate-200 flex items-center justify-center">
                                    @if(strtolower($pm['brand']) === 'visa')
                                        <span class="text-xs font-bold text-blue-700">VISA</span>
                                    @elseif(strtolower($pm['brand']) === 'mastercard')
                                        <span class="text-xs font-bold text-orange-600">MC</span>
                                    @elseif(strtolower($pm['brand']) === 'amex')
                                        <span class="text-xs font-bold text-blue-500">AMEX</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-500">{{ strtoupper(substr($pm['brand'], 0, 4)) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-slate-900">{{ $pm['brand'] }} •••• {{ $pm['last4'] }}</div>
                                    <div class="text-xs text-slate-500">Expires {{ $pm['exp_month'] }}/{{ $pm['exp_year'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border-2 border-dashed border-slate-200 p-8 text-center mb-6">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">No payment methods on file</h3>
                    <p class="text-sm text-slate-500 mt-1">Add a card to enable automatic subscription billing.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('app.settings.payment') }}" x-data="{ loading: false }" @submit="loading = true">
                @csrf
                <button type="submit" class="tc-btn-primary inline-flex items-center gap-2" :disabled="loading">
                    <span x-show="loading" class="tc-spinner w-4 h-4" aria-hidden="true"></span>
                    <span x-text="loading ? 'Redirecting to Stripe...' : '{{ empty($paymentMethods) ? 'Add Payment Method' : 'Manage Payment Methods' }}'"></span>
                </button>
            </form>
        </div>

        {{-- Current Subscription --}}
        @if(isset($subscription) && $subscription)
            <div class="tc-card p-6">
                <h2 class="text-base font-bold text-slate-900 mb-3">Current Subscription</h2>
                <div class="grid sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-slate-500">Plan</span>
                        <div class="font-semibold text-slate-900 mt-0.5">{{ $subscription->planLabel() }}</div>
                    </div>
                    <div>
                        <span class="text-slate-500">Status</span>
                        <div class="mt-0.5">
                            @if($subscription->isActive())
                                <span class="inline-flex items-center gap-1 text-green-700 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Active
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-slate-600 font-semibold capitalize">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> {{ $subscription->status }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-slate-500">Next Billing</span>
                        <div class="font-medium text-slate-700 mt-0.5">{{ $subscription->current_period_end?->format('M d, Y') ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
