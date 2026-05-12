@extends('layouts.saas')

@section('title', 'tickIt - Admin - ' . $workspace->name)

@section('header', 'Admin - ' . $workspace->name)

@section('content')
    <div class="max-w-4xl">
        <a href="{{ route('admin.billing.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-6 transition-colors">
            Back to all workspaces
        </a>

        <div class="tc-card p-6 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $workspace->name }}</h2>
                    <p class="text-sm text-slate-500 mt-1">Slug: <span class="font-mono">{{ $workspace->slug }}</span> / Created {{ $workspace->created_at->format('M d, Y') }}</p>
                </div>
                <div>
                    @php
                        $badgeColors = [
                            'slate' => 'bg-slate-100 text-slate-600',
                            'blue' => 'bg-blue-100 text-blue-700',
                            'indigo' => 'bg-indigo-100 text-indigo-700',
                            'amber' => 'bg-amber-100 text-amber-700',
                        ];
                    @endphp
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-md text-sm font-semibold {{ $badgeColors[$plan['badge_color']] ?? 'bg-slate-100 text-slate-600' }}">
                        {{ $plan['label'] }}
                    </span>
                </div>
            </div>

            <div class="grid sm:grid-cols-3 gap-4 mt-6">
                <div class="bg-slate-50 rounded-lg p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Voice Minutes Used</div>
                    <div class="text-lg font-bold text-slate-900">
                        {{ number_format($usageMinutes, 1) }}
                        <span class="text-sm font-normal text-slate-400">/
                            {{ $plan['max_minutes'] == -1 ? 'Unlimited' : number_format($plan['max_minutes']) }}</span>
                    </div>
                </div>
                <div class="bg-slate-50 rounded-lg p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Credit Balance</div>
                    <div class="text-lg font-bold text-slate-900">{{ number_format($workspace->credits_balance) }}</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-4">
                    <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Assistants</div>
                    <div class="text-lg font-bold text-slate-900">
                        {{ $workspace->assistantConfigs()->count() }}
                        <span class="text-sm font-normal text-slate-400">/
                            {{ $plan['max_assistants'] == -1 ? 'Unlimited' : $plan['max_assistants'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mb-6">
            <div class="tc-card p-6">
                <h3 class="text-base font-bold text-slate-900 mb-4">Grant Credits</h3>
                <form action="{{ route('admin.billing.grantCredits', $workspace) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
                        <input type="number" name="amount" min="1" max="100000" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition"
                            placeholder="e.g. 500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Reason (optional)</label>
                        <input type="text" name="reason" maxlength="255"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition"
                            placeholder="e.g. Promotional credits">
                    </div>
                    <button type="submit" class="tc-btn-primary w-full">Grant Credits</button>
                </form>
            </div>

            <div class="tc-card p-6">
                <h3 class="text-base font-bold text-slate-900 mb-4">Override Plan</h3>
                <p class="text-sm text-slate-500 mb-4">Change this workspace's plan directly, bypassing Stripe. Use for
                    trials, partnerships, or comp accounts.</p>
                <form action="{{ route('admin.billing.changePlan', $workspace) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">New Plan</label>
                        <select name="plan_key" required
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 outline-none transition">
                            @foreach(config('plans') as $key => $p)
                                <option value="{{ $key }}" {{ ($workspace->plan_key ?? 'free') === $key ? 'selected' : '' }}>
                                    {{ $p['label'] }} / {{ match (strtoupper($p['currency'] ?? 'EUR')) { 'USD' => '$', 'GBP' => '£', default => '€' } }}{{ number_format($p['price_monthly']) }}/mo
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="tc-btn-primary w-full">Change Plan</button>
                </form>
            </div>
        </div>

        <div class="tc-card">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Credit History</h3>
            </div>
            @if($credits->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-slate-400">No credit transactions yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" aria-label="Credit history">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Date</th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Type</th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Amount</th>
                                <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($credits as $entry)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-3 text-slate-600">{{ $entry->created_at->format('M d, Y H:i') }}</td>
                                    <td class="px-6 py-3">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                                    {{ $entry->amount > 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                            {{ ucfirst(str_replace('_', ' ', $entry->type)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 font-mono {{ $entry->amount > 0 ? 'text-green-700' : 'text-red-600' }}">
                                        {{ $entry->amount > 0 ? '+' : '' }}{{ number_format($entry->amount) }}
                                    </td>
                                    <td class="px-6 py-3 text-slate-500 text-xs">{{ $entry->meta['reason'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="tc-card mt-6">
            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-900">Members</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" aria-label="Workspace members">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Name</th>
                            <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Email</th>
                            <th scope="col" class="px-6 py-3 text-xs font-semibold uppercase text-slate-500">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($workspace->memberships as $m)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 font-medium text-slate-900">{{ $m->user->name ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $m->user->email ?? '' }}</td>
                                <td class="px-6 py-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-600 capitalize">{{ $m->role }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
