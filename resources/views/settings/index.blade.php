@extends('layouts.saas')

@section('title', 'tickIt - Settings')
@section('header_eyebrow', 'Configuration')
@section('header', 'Settings')
@section('header_description', 'Manage account details, workspace defaults, integrations, security, and calendar providers from one place.')

@section('content')
@php
    $tabs = [
        'profile'      => ['label' => 'Profile'],
        'security'     => ['label' => 'Security'],
        'workspace'    => ['label' => 'Workspace'],
        'integrations' => ['label' => 'API & providers'],
        'calendar'     => ['label' => 'Calendar'],
    ];
@endphp

    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            @foreach($tabs as $key => $tabItem)
                <a href="{{ route('app.settings', ['tab' => $key]) }}" class="tc-chip {{ $tab === $key ? 'tc-chip-active' : '' }}" role="tab" aria-selected="{{ $tab === $key ? 'true' : 'false' }}">
                    {{ $tabItem['label'] }}
                </a>
            @endforeach
        </div>

        @if($tab === 'profile')
            <x-ui.panel title="Profile information" description="Update the name and email used across the app.">
                <form method="POST" action="{{ route('app.settings.profile') }}" class="grid max-w-2xl gap-5">
                    @csrf
                    @method('PATCH')

                    <div class="tc-field">
                        <label for="name" class="tc-field-label">Full name</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="tc-input" />
                        @error('name') <p class="tc-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="tc-field">
                        <label for="email" class="tc-field-label">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="tc-input" />
                        @error('email') <p class="tc-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="tc-btn-primary">Save changes</button>
                    </div>
                </form>
            </x-ui.panel>

            <x-ui.panel title="Danger zone" description="Permanent account deletion removes your access and all associated workspace data.">
                <div x-data="{ confirm: false }">
                    <div x-show="!confirm">
                        <button type="button" class="tc-btn-danger" @click="confirm = true">Delete my account</button>
                    </div>

                    <div x-show="confirm" x-cloak class="space-y-4">
                        <div class="tc-meta-card-strong border-red-200 bg-red-50/80 text-sm leading-6 text-red-800">
                            This action permanently deletes your account, workspaces, assistants, and related data. There is no undo.
                        </div>

                        <form method="POST" action="{{ route('app.settings.destroy') }}" class="max-w-lg space-y-4">
                            @csrf
                            @method('DELETE')

                            <div class="tc-field">
                                <label for="delete-password" class="tc-field-label">Confirm with password</label>
                                <input id="delete-password" name="password" type="password" required class="tc-input" />
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button type="submit" class="tc-btn-danger">Delete account</button>
                                <button type="button" class="tc-btn-ghost" @click.prevent="confirm = false">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </x-ui.panel>
        @elseif($tab === 'security')
            <x-ui.panel title="Change password" description="Update your password for this account.">
                <form method="POST" action="{{ route('app.settings.password') }}" class="grid max-w-2xl gap-5">
                    @csrf
                    @method('PUT')

                    <div class="tc-field">
                        <label for="current_password" class="tc-field-label">Current password</label>
                        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="tc-input" />
                        @error('current_password') <p class="tc-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="tc-field">
                            <label for="password" class="tc-field-label">New password</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password" class="tc-input" />
                            @error('password') <p class="tc-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="tc-field">
                            <label for="password_confirmation" class="tc-field-label">Confirm new password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="tc-input" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="tc-btn-primary">Update password</button>
                    </div>
                </form>
            </x-ui.panel>
        @elseif($tab === 'workspace')
            <x-ui.panel title="Workspace preferences" description="Set the defaults your team sees across the app.">
                <form method="POST" action="{{ route('app.settings.workspace') }}" class="grid max-w-3xl gap-5" enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    <div class="tc-field">
                        <label for="ws-name" class="tc-field-label">Company name</label>
                        <input id="ws-name" name="name" type="text" value="{{ old('name', $workspace->name ?? '') }}" required class="tc-input" />
                        @error('name') <p class="tc-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="tc-field">
                            <label for="ws-tz" class="tc-field-label">Default timezone</label>
                            <select id="ws-tz" name="default_timezone" required class="tc-input">
                                @foreach(['America/New_York','America/Chicago','America/Denver','America/Los_Angeles','America/Anchorage','Pacific/Honolulu','Europe/London','Europe/Paris','Europe/Berlin','Asia/Tokyo','Asia/Shanghai','Australia/Sydney','UTC'] as $tz)
                                    <option value="{{ $tz }}" {{ ($workspace->default_timezone ?? 'America/New_York') === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="tc-field">
                            <label for="ws-label" class="tc-field-label">Ticket label</label>
                            <input id="ws-label" name="case_label" type="text" value="{{ old('case_label', $workspace->case_label ?? 'Ticket') }}" required maxlength="40" class="tc-input" />
                            <p class="tc-help">For example: Ticket or Request.</p>
                        </div>
                    </div>

                    <div class="tc-field">
                        <label for="ws-logo" class="tc-field-label">Company logo <span class="text-slate-500">Optional</span></label>
                        <div class="tc-meta-card">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
                                        @if($workspace?->logoUrl())
                                            <img src="{{ $workspace->logoUrl() }}" alt="{{ $workspace->name }} logo" class="h-full w-full object-contain">
                                        @else
                                            <span class="text-lg font-semibold uppercase tracking-[0.16em] text-slate-400">
                                                {{ \Illuminate\Support\Str::of($workspace->name ?? 'WS')->substr(0, 2)->upper() }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Shown at the top of the sidebar</div>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">PNG, JPG, WebP, GIF, or SVG up to 2MB.</p>
                                    </div>
                                </div>

                                @if($workspace?->logoUrl())
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                        <span>Remove current logo</span>
                                    </label>
                                @endif
                            </div>

                            <div class="mt-4">
                                <input id="ws-logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" />
                            </div>
                        </div>
                        @error('logo') <p class="tc-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="tc-btn-primary">Save workspace settings</button>
                    </div>
                </form>
            </x-ui.panel>

            <x-ui.panel title="Workspace details" description="Basic details for this workspace.">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="tc-meta-card">
                        <div class="tc-label-eyebrow">Slug</div>
                        <div class="mt-2 font-mono text-sm text-slate-900">{{ $workspace->slug ?? 'N/A' }}</div>
                    </div>
                    <div class="tc-meta-card">
                        <div class="tc-label-eyebrow">Plan</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">{{ $workspace->planLabel() ?? 'Free' }}</div>
                    </div>
                    <div class="tc-meta-card">
                        <div class="tc-label-eyebrow">Created</div>
                        <div class="mt-2 text-sm font-medium text-slate-900">{{ $workspace->created_at?->format('M d, Y') ?? 'N/A' }}</div>
                    </div>
                </div>
            </x-ui.panel>
        @elseif($tab === 'integrations')
            <x-ui.panel title="API integration token" description="Use this token when another system connects to tickIt.">
                <div class="space-y-5">
                    <div class="tc-meta-card">
                        <div class="tc-label-eyebrow">Bearer token</div>
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <code class="min-w-0 flex-1 break-all text-xs text-slate-700">{{ $integrationToken ?? 'N/A' }}</code>
                            <button type="button" class="tc-btn-ghost w-full !px-3 !py-2 text-xs sm:w-auto" onclick="navigator.clipboard.writeText('{{ $integrationToken ?? '' }}')">Copy</button>
                        </div>
                    </div>

                    @if($workspace)
                        <form method="POST" action="{{ route('app.integrations.token.regenerate', $workspace) }}" onsubmit="return confirm('Regenerate token? Existing integrations using the old token will stop working immediately.')">
                            @csrf
                            <div class="mb-4 tc-meta-card border-amber-200 bg-amber-50/80 text-sm leading-6 text-amber-800">
                                Regenerating the token breaks any active Vapi tool connections and API clients using the previous value.
                            </div>
                            <button type="submit" class="tc-btn-danger">Regenerate token</button>
                        </form>
                    @endif
                </div>
            </x-ui.panel>

            <x-ui.panel title="Provider health" description="Current connection status for core platform providers.">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="tc-meta-card-strong">
                        <div class="tc-label-eyebrow">Vapi webhook</div>
                        <div class="mt-3 text-sm leading-6 text-slate-700">{{ $vapiWebhookUrl ?? 'Not configured' }}</div>
                        <div class="mt-4">
                            <x-ui.badge tone="{{ config('services.vapi.key') ? 'success' : 'warning' }}">{{ config('services.vapi.key') ? 'Connected' : 'Not configured' }}</x-ui.badge>
                        </div>
                    </div>
                    <div class="tc-meta-card-strong">
                        <div class="tc-label-eyebrow">Workspace API</div>
                        <div class="mt-3 text-sm leading-6 text-slate-700">Use <code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">Authorization: Bearer &lt;token&gt;</code> and your workspace slug on API requests.</div>
                        <div class="mt-4">
                            <x-ui.badge tone="info">Ready for integrations</x-ui.badge>
                        </div>
                    </div>
                </div>
            </x-ui.panel>
        @elseif($tab === 'calendar')
            <div class="grid gap-6 lg:grid-cols-2">
                <x-ui.panel title="Google Calendar" description="Connect your primary calendar so confirmed meetings can be inserted automatically.">
                    <div class="flex h-full flex-col justify-between gap-5">
                        <p class="text-sm leading-6 text-slate-600">Use Google Calendar for native booking inserts when the assistant or team confirms a meeting.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            @if(isset($connections['google']))
                                <x-ui.badge tone="success">Connected</x-ui.badge>
                            @else
                                <x-ui.badge tone="warning">Not connected</x-ui.badge>
                            @endif
                            <a href="{{ route('app.calendar.google.auth') }}" class="tc-btn-primary">{{ isset($connections['google']) ? 'Reconnect Google' : 'Connect Google' }}</a>
                        </div>
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Calendly" description="Store a scheduling link for teams that rely on a hosted booking flow.">
                    <form action="{{ route('app.calendar.calendly.save') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="tc-field">
                            <label for="calendly_link" class="tc-field-label">Scheduling link URL</label>
                            <input type="url" name="calendly_link" id="calendly_link" required class="tc-input"
                                placeholder="https://calendly.com/your-name/30min"
                                value="{{ old('calendly_link', $connections['calendly']->calendly_scheduling_link ?? '') }}">
                            @error('calendly_link') <p class="tc-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @if(isset($connections['calendly']))
                                <x-ui.badge tone="success">Configured</x-ui.badge>
                            @endif
                            <button type="submit" class="tc-btn-primary">Save Calendly settings</button>
                        </div>
                    </form>
                </x-ui.panel>
            </div>
        @endif
    </div>
@endsection
