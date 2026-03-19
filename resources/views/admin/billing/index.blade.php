@extends('layouts.saas')

@section('title', 'ticketcloser • Admin Billing')

@section('header', 'Admin — Workspace Billing')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Billing Administration</h1>
        <p class="text-sm text-slate-500 mt-1">Manage workspace subscriptions, grant credits, and override plans.</p>
    </div>

    {{-- Summary cards --}}
    <div class="grid sm:grid-cols-3 gap-4 mb-8">
        <div class="tc-card p-5">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Total Workspaces</div>
            <div class="text-2xl font-bold text-slate-900">{{ $workspaces->total() }}</div>
        </div>
        <div class="tc-card p-5">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Active Subscriptions</div>
            <div class="text-2xl font-bold text-green-600">
                {{ $workspaces->filter(fn($w) => $w->plan_key !== 'free')->count() }}</div>
        </div>
        <div class="tc-card p-5">
            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Free Tier</div>
            <div class="text-2xl font-bold text-slate-400">
                {{ $workspaces->filter(fn($w) => $w->plan_key === 'free')->count() }}</div>
        </div>
    </div>

    {{-- Workspace table --}}
    <div class="tc-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" aria-label="Workspace billing">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Workspace</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Owner</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Plan</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Credits</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Cases</th>
                        <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500 text-right">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($workspaces as $ws)
                        @php
                            $owner = $ws->memberships->firstWhere('role', 'owner')?->user;
                            $planCfg = config('plans.' . ($ws->plan_key ?? 'free'), config('plans.free'));
                            $badgeColors = [
                                'slate' => 'bg-slate-100 text-slate-600',
                                'blue' => 'bg-blue-100 text-blue-700',
                                'indigo' => 'bg-indigo-100 text-indigo-700',
                                'amber' => 'bg-amber-100 text-amber-700',
                            ];
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $ws->name }}</div>
                                <div class="text-xs text-slate-400">{{ $ws->slug }}</div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $owner->name ?? 'N/A' }}
                                <div class="text-xs text-slate-400">{{ $owner->email ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold {{ $badgeColors[$planCfg['badge_color']] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $planCfg['label'] ?? ucfirst($ws->plan_key ?? 'free') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-700">{{ number_format($ws->credits_balance) }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ number_format($ws->cases_count) }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.billing.show', $ws) }}"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium transition-colors">
                                    Manage →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">No workspaces found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($workspaces->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $workspaces->links() }}
            </div>
        @endif
    </div>
@endsection