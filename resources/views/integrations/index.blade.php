@extends('layouts.saas')

@section('title')
    ticketcloser • Integrations
@endsection

@section('header')
    Integrations
@endsection

@section('content')
<div class="tc-page-header">
    <h1>Integrations</h1>
    <p>Manage your API token and external connections.</p>
</div>

<div class="space-y-4">
    <div class="tc-card p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h2 class="tc-h3">Integration token</h2>
                <p class="tc-small mt-0.5">Send as <code
                        class="font-mono text-xs bg-slate-100 px-1 py-0.5 rounded">Authorization: Bearer &lt;token&gt;</code>
                    on all API requests.</p>
            </div>
        </div>

        <div
            class="group flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono mb-4">
            <span class="flex-1 truncate text-slate-700 select-all">{{ $workspace->integration_token }}</span>
            <button onclick="navigator.clipboard.writeText('{{ $workspace->integration_token }}')"
                class="flex-shrink-0 text-xs text-muted hover:text-slate-800">Copy</button>
        </div>

        <form method="POST" action="{{ route('app.integrations.token.regenerate', $workspace) }}"
            x-data="{ loading: false }"
            @submit="if(!confirm('Regenerate token? Existing integrations using the old token will stop working immediately.')) { $event.preventDefault(); return; } loading = true;">
            @csrf
            <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800 mb-3">Regenerating
                breaks any active Vapi tool connections, only do this if the token is compromised.</div>
            <button type="submit" class="tc-btn-danger">Regenerate token</button>
        </form>
    </div>

    <div class="tc-card p-6">
        <h2 class="tc-h3 mb-4">API reference</h2>
        @php($baseUrl = config('app.url'))
        <div class="space-y-5">
            <div>
                <p class="tc-small uppercase tracking-wide font-medium mb-2">Create a case, POST
                    {{ $baseUrl }}/api/cases
                </p>
                <div class="bg-slate-900 text-slate-100 rounded-xl p-4 font-mono text-xs overflow-auto">
                    <pre>POST {{ $baseUrl }}/api/cases
Authorization: Bearer {{ $workspace->integration_token }}
X-Workspace-Slug: {{ $workspace->slug }}

{
  "title": "Broken login",
  "description": "User cannot log in since yesterday.",
  "category": "account",
  "priority": "high",
  "requesterPhone": "+14155550100"
}
</pre>
                    <button onclick="navigator.clipboard.writeText(document.querySelector('pre').innerText)"
                        class="mt-1 text-slate-400 hover:text-slate-100 text-xs">Copy</button>
                </div>
            </div>

            <div>
                <p class="tc-small uppercase tracking-wide font-medium mb-2">Required headers</p>
                <table class="w-full text-sm">
                    <thead class="text-xs text-muted border-b border-slate-200">
                        <tr>
                            <th class="py-2 text-left font-medium">Header</th>
                            <th class="py-2 text-left font-medium">Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-2 font-mono text-xs">Authorization</td>
                            <td class="py-2 text-slate-600">Bearer &lt;integration_token&gt;</td>
                        </tr>
                        <tr>
                            <td class="py-2 font-mono text-xs">X-Workspace-Slug</td>
                            <td class="py-2"><code
                                    class="font-mono text-xs bg-slate-100 px-1.5 py-0.5 rounded">{{ $workspace->slug }}</code>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tc-card p-6">
        <h2 class="tc-h3 mb-1">Vapi webhook</h2>
        <p class="tc-small mb-4">This is the URL Vapi sends tool-call events to. Paste this into your Vapi tool server
            URL field.</p>
        <div
            class="group flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono">
            <span class="flex-1 truncate text-slate-700 select-all">{{ config('services.vapi.webhook_url') }}</span>
            <button onclick="navigator.clipboard.writeText('{{ config('services.vapi.webhook_url') }}')"
                class="flex-shrink-0 text-xs text-muted hover:text-slate-800">Copy</button>
        </div>
    </div>
</div>

@endsection