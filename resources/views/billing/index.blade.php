@extends('layouts.saas')

@section('title', 'tickIt - Billing')
@section('header_eyebrow', 'Plan and usage')
@section('header', 'Billing & usage')
@section('header_description', 'See your base plan, included usage, projected overage, and invoices.')

@section('content')
    @php
        $cycleEndsOn = $subscription?->current_period_end ?? now()->endOfMonth();
        $currency = strtoupper($plan['currency'] ?? 'EUR');
        $currencySymbol = match ($currency) {
            'USD' => '$',
            'GBP' => "\u{00A3}",
            default => "\u{20AC}",
        };
        $includedMinutes = isset($includedMinutes) ? (int) $includedMinutes : (int) ($plan['max_minutes'] ?? 0);
        $hasUnlimitedMinutes = isset($hasUnlimitedMinutes) ? (bool) $hasUnlimitedMinutes : $includedMinutes === -1;
        $overageRate = $overageRate ?? ($plan['overage_per_minute'] ?? null);
        $basePrice = isset($basePrice) ? (float) $basePrice : (float) ($plan['price_monthly'] ?? 0);
        $extraMinutes = isset($extraMinutes)
            ? (float) $extraMinutes
            : ($hasUnlimitedMinutes ? 0.0 : max(0, round((float) ($usageMinutes ?? 0) - $includedMinutes, 1)));
        $estimatedOverage = isset($estimatedOverage)
            ? (float) $estimatedOverage
            : ($overageRate !== null ? round($extraMinutes * (float) $overageRate, 2) : 0.0);
        $estimatedCycleTotal = isset($estimatedCycleTotal)
            ? (float) $estimatedCycleTotal
            : round($basePrice + $estimatedOverage, 2);
        $usagePercent = isset($usagePercent)
            ? (float) $usagePercent
            : ($hasUnlimitedMinutes || $includedMinutes <= 0 ? 0 : min(100, (((float) ($usageMinutes ?? 0)) / $includedMinutes) * 100));
        $barClass = $usagePercent > 95 ? 'bg-red-500' : ($usagePercent > 75 ? 'bg-amber-500' : '');
        $featureHighlights = $plan['feature_highlights'] ?? [];
    @endphp

    <div class="space-y-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(340px,0.95fr)]">
            <x-ui.panel title="Current plan" description="Your base fee and plan setup for this workspace.">
                <div class="flex h-full flex-col justify-between gap-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="text-2xl font-semibold tracking-tight text-slate-950">{{ $plan['label'] ?? 'Free plan' }}</div>
                            @if($subscription && $subscription->isActive())
                                <x-ui.badge tone="success">Active</x-ui.badge>
                            @else
                                <x-ui.badge tone="warning">{{ ($workspace->plan_key ?? 'free') === 'free' ? 'Free plan' : 'Unsubscribed' }}</x-ui.badge>
                            @endif
                        </div>

                        <div class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ $currencySymbol }}{{ number_format($basePrice, $basePrice == floor($basePrice) ? 0 : 2) }}
                            <span class="text-base font-medium text-slate-500">/ month base</span>
                        </div>

                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            {{ $plan['description'] }}
                        </p>

                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{ $plan['usage_copy'] ?? 'Your monthly bill is based on plan and usage.' }}
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Included minutes</div>
                            <div class="mt-2 text-xl font-semibold text-slate-950">{{ $hasUnlimitedMinutes ? 'Unlimited' : number_format($includedMinutes) }}</div>
                        </div>
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Overage</div>
                            <div class="mt-2 text-xl font-semibold text-slate-950">
                                @if($overageRate !== null)
                                    {{ $currencySymbol }}{{ number_format($overageRate, 2) }}<span class="text-sm font-medium text-slate-500">/ min</span>
                                @else
                                    Upgrade
                                @endif
                            </div>
                        </div>
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Cycle ends</div>
                            <div class="mt-2 text-xl font-semibold text-slate-950">{{ \Carbon\Carbon::parse($cycleEndsOn)->format('M d') }}</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        @if($subscription && $subscription->isActive())
                            <form action="{{ route('app.billing.portal') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                                @csrf
                                <button type="submit" class="tc-btn-primary" :disabled="loading">
                                    <span x-show="loading" class="tc-spinner" aria-hidden="true"></span>
                                    <span x-text="loading ? 'Redirecting...' : 'Manage subscription'">Manage subscription</span>
                                </button>
                            </form>
                            <a href="{{ route('app.billing.plans') }}" class="tc-btn-secondary">Change plan</a>
                        @else
                            <a href="{{ route('app.billing.plans') }}" class="tc-btn-primary">View plans</a>
                        @endif
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="This cycle" description="Live usage and what this billing cycle is trending toward.">
                <div class="space-y-5">
                    <div>
                        <div class="flex items-end justify-between gap-4">
                            <div class="text-sm font-medium text-slate-700">Voice minutes used</div>
                            <div class="text-sm font-semibold text-slate-950">
                                {{ number_format($usageMinutes, 1) }}
                                <span class="text-xs font-normal text-slate-500">/ {{ $hasUnlimitedMinutes ? 'Unlimited' : number_format($includedMinutes) }}</span>
                            </div>
                        </div>
                            <div class="tc-progress mt-3">
                                <div class="tc-progress-bar {{ $barClass }}" style="width: {{ $usagePercent }}%"></div>
                            </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Extra minutes</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($extraMinutes, 1) }}</div>
                            <div class="mt-1 text-sm text-slate-500">
                                @if($overageRate !== null)
                                    {{ $currencySymbol }}{{ number_format($estimatedOverage, 2) }} in projected overage
                                @else
                                    Free stays inside trial usage
                                @endif
                            </div>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Projected total</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $currencySymbol }}{{ number_format($estimatedCycleTotal, 2) }}</div>
                            <div class="mt-1 text-sm text-slate-500">Base fee plus current usage for this cycle</div>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Calls</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($usageCalls) }}</div>
                        </div>
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Credits</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-950">{{ number_format($creditsBalance) }}</div>
                        </div>
                    </div>
                </div>
            </x-ui.panel>
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.9fr)]">
            <x-ui.panel title="Included every month" description="What this plan gives you before overage starts.">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Included minutes</div>
                        <div class="mt-2 text-xl font-semibold text-slate-950">{{ $hasUnlimitedMinutes ? 'Unlimited' : number_format($includedMinutes) }}</div>
                    </div>
                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Assistants</div>
                        <div class="mt-2 text-xl font-semibold text-slate-950">{{ $plan['max_assistants'] == -1 ? 'Unlimited' : $plan['max_assistants'] }}</div>
                    </div>
                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Phone numbers</div>
                        <div class="mt-2 text-xl font-semibold text-slate-950">{{ $plan['max_phone_numbers'] == -1 ? 'Unlimited' : $plan['max_phone_numbers'] }}</div>
                    </div>
                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Tickets</div>
                        <div class="mt-2 text-xl font-semibold text-slate-950">{{ $plan['max_cases'] == -1 ? 'Unlimited' : number_format($plan['max_cases']) }}</div>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Why teams choose this tier" description="{{ $plan['recommended_for'] ?? 'Built for growing teams.' }}">
                <div class="space-y-3">
                    @foreach($featureHighlights as $highlight)
                        <div class="flex items-start gap-3 rounded-[1.1rem] border border-slate-200 bg-slate-50/80 px-4 py-3">
                            <span class="tc-accent-fill mt-2 inline-block h-2 w-2 flex-shrink-0 rounded-full"></span>
                            <span class="text-sm leading-6 text-slate-700">{{ $highlight }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.panel>
        </div>

        <x-ui.panel title="Recent invoices" description="Your billing history.">
            @if($invoices->isEmpty())
                <x-ui.empty-state title="No invoices yet" description="Invoices will show up here once paid billing starts." />
            @else
                <div class="tc-table-wrap">
                    <table class="tc-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $inv)
                                <tr>
                                    <td class="font-medium text-slate-950">{{ $inv->created_at->format('M d, Y') }}</td>
                                    <td class="font-mono text-slate-700">{{ $inv->formattedAmount() }}</td>
                                    <td>
                                        <x-ui.badge tone="{{ $inv->status === 'paid' ? 'success' : 'slate' }}">{{ $inv->status }}</x-ui.badge>
                                    </td>
                                    <td class="text-right">
                                        @if($inv->hosted_invoice_url)
                                            <a href="{{ $inv->hosted_invoice_url }}" target="_blank" rel="noopener noreferrer" class="tc-accent-link text-sm font-medium">View invoice</a>
                                        @else
                                            <span class="text-sm text-slate-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>
    </div>
@endsection
