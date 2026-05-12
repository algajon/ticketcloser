@extends('layouts.saas')

@section('title', 'tickIt - Integrations')
@section('header_eyebrow', 'External systems')
@section('header', 'Integrations')
@section('header_description', 'Copy your token, check the webhook, and connect outside tools.')

@section('content')
    @php($baseUrl = config('app.url'))

    <div class="space-y-6">
        <x-ui.panel title="API token" description="Use this token when another tool sends data into this workspace.">
            <div class="space-y-5">
                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Bearer token</div>
                    <div class="mt-3 flex items-center gap-3">
                        <code class="min-w-0 flex-1 truncate text-xs text-slate-700">{{ $workspace->integration_token }}</code>
                        <button type="button" class="tc-btn-ghost !px-3 !py-2 text-xs" onclick="navigator.clipboard.writeText('{{ $workspace->integration_token }}')">Copy</button>
                    </div>
                </div>

                <form method="POST" action="{{ route('app.integrations.token.regenerate', $workspace) }}" x-data="{ loading: false }"
                    @submit="if(!confirm('Regenerate token? Existing integrations using the old token will stop working immediately.')) { $event.preventDefault(); return; } loading = true;">
                    @csrf
                    <div class="mb-4 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm leading-6 text-amber-800">
                        Regenerating the token will break any active integrations still using the current value.
                    </div>
                    <button type="submit" class="tc-btn-danger" x-bind:disabled="loading">
                        <span x-text="loading ? 'Regenerating...' : 'Regenerate token'">Regenerate token</span>
                    </button>
                </form>
            </div>
        </x-ui.panel>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
            <x-ui.panel title="API example" description="Example request for creating a ticket.">
                <div class="rounded-[1.35rem] border border-slate-200 bg-slate-950 p-5 text-slate-100">
<pre class="overflow-auto text-xs leading-6"><code>POST {{ $baseUrl }}/api/tickets
Authorization: Bearer {{ $workspace->integration_token }}
X-Workspace-Slug: {{ $workspace->slug }}

{
  "title": "Broken login",
  "description": "User cannot log in since yesterday.",
  "category": "account",
  "priority": "high",
  "requesterPhone": "+14155550100"
}</code></pre>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Provider status" description="Quick check for the main connection path.">
                <div class="space-y-4">
                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Vapi webhook</div>
                        <div class="mt-3 text-sm leading-6 text-slate-700">{{ config('services.vapi.webhook_url') }}</div>
                        <div class="mt-4">
                            <x-ui.badge tone="{{ config('services.vapi.key') ? 'success' : 'warning' }}">{{ config('services.vapi.key') ? 'Connected' : 'Missing API key' }}</x-ui.badge>
                        </div>
                    </div>

                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Required header</div>
                        <div class="mt-3 text-sm text-slate-700"><code class="rounded bg-slate-200 px-1.5 py-0.5 text-xs">X-Workspace-Slug: {{ $workspace->slug }}</code></div>
                    </div>
                </div>
            </x-ui.panel>
        </div>
    </div>
@endsection
