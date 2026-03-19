@extends('layouts.saas')

@section('title', 'ticketcloser • Billing')

@section('header', 'Billing')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Billing & Usage</h1>
    <p class="text-sm text-slate-500 mt-1">Manage your active subscription, payment methods, and monitor usage limits.</p>
</div>

<div class="grid lg:grid-cols-3 gap-8 mb-8">
    {{-- Subscription Card --}}
    <div class="lg:col-span-2 tc-card p-6 flex flex-col group transition-shadow hover:shadow-md">
        <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            Current Plan
        </h2>

        @if($subscription && $subscription->isActive())
            <div class="flex-grow flex flex-col justify-center items-start">
                <div class="flex items-center gap-3">
                    <span class="text-2xl font-bold text-slate-900">{{ $plan['label'] ?? 'Pro Plan' }}</span>
                    <span class="tc-badge-synced">
                        <span style="width:5px;height:5px;border-radius:50%;background:#10b981;display:inline-block"></span>
                        Active
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-2">
                    Your next billing cycle occurs on <strong class="text-slate-700">{{ $subscription->current_period_end ? \Carbon\Carbon::parse($subscription->current_period_end)->format('M d, Y') : 'N/A' }}</strong>.
                </p>
                <div class="mt-6 flex gap-3 w-full flex-wrap">
                    <form action="{{ route('app.billing.portal') }}" method="POST" class="sm:w-auto" x-data="{ loads: false }" @submit="loads = true">
                        @csrf
                        <button type="submit" class="tc-btn-primary flex justify-center items-center" :disabled="loads" aria-label="Manage subscription in Stripe">
                            <span x-show="loads" class="tc-spinner w-4 h-4 mr-2" aria-hidden="true"></span>
                            <span x-text="loads ? 'Redirecting...' : 'Manage Subscription'"></span>
                        </button>
                    </form>
                    <a href="{{ route('app.billing.plans') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg border-2 border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600 transition-all">
                        Change Plan
                    </a>
                </div>
            </div>
        @else
            <div class="flex-grow flex flex-col justify-center items-start">
                <div class="flex items-center gap-3">
                    <span class="text-xl font-bold text-slate-900">{{ $plan['label'] ?? 'Free Trial' }}</span>
                    <span class="tc-badge-pending">
                        <span style="width:5px;height:5px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                        {{ ($workspace->plan_key ?? 'free') === 'free' ? 'Free Trial' : 'Unsubscribed' }}
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-2">
                    Upgrade to a premium plan to unlock advanced features, increased usage limits, and premium Vapi voices.
                </p>
                <div class="mt-6 flex flex-wrap gap-3 w-full">
                    <a href="{{ route('app.billing.plans') }}" class="tc-btn-primary inline-flex items-center">
                        View Plans & Upgrade
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Usage Card --}}
    <div class="tc-card p-6 flex flex-col group transition-shadow hover:shadow-md">
        <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0119.5 16.5h-2.25m-9 0h3m-3 0V3.75m6 12.75V3.75m0 12.75H18" />
            </svg>
            Current Usage
        </h2>

        <div class="space-y-6 flex-grow">
            {{-- Minutes --}}
            <div>
                <div class="flex justify-between items-end mb-2">
                    <span class="text-sm font-medium text-slate-700">Voice Minutes</span>
                    <span class="text-sm font-bold text-slate-900" aria-live="polite">
                        {{ number_format($usageMinutes, 1) }}
                        <span class="text-xs font-normal text-slate-500">/ {{ $plan['max_minutes'] == -1 ? '∞' : number_format($plan['max_minutes']) }}</span>
                    </span>
                </div>
                @php
                    $maxMin = $plan['max_minutes'] == -1 ? max(100, $usageMinutes * 2) : $plan['max_minutes'];
                    $pctMin = $maxMin > 0 ? min(100, ($usageMinutes / $maxMin) * 100) : 0;
                    $barColor = $pctMin > 90 ? 'bg-red-500' : ($pctMin > 70 ? 'bg-amber-500' : 'bg-sky-500');
                @endphp
                <div class="w-full bg-slate-100 rounded-full h-1.5" role="progressbar" aria-valuenow="{{ $pctMin }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="{{ $barColor }} h-1.5 rounded-full transition-all duration-500" style="width: {{ $pctMin }}%"></div>
                </div>
            </div>

            {{-- Calls --}}
            <div>
                <div class="flex justify-between items-end mb-2">
                    <span class="text-sm font-medium text-slate-700">Total Calls</span>
                    <span class="text-sm font-bold text-slate-900" aria-live="polite">{{ number_format($usageCalls) }}</span>
                </div>
            </div>

            {{-- Credits --}}
            <div>
                <div class="flex justify-between items-end mb-2">
                    <span class="text-sm font-medium text-slate-700">Credit Balance</span>
                    <span class="text-sm font-bold text-slate-900">{{ number_format($creditsBalance) }}</span>
                </div>
            </div>
        </div>

        <p class="text-xs text-slate-400 mt-4 leading-relaxed">Usage resets at the start of your billing cycle. Overage rates apply as per your plan limits.</p>
    </div>
</div>

{{-- Plan Limits --}}
<div class="tc-card p-6 mb-8">
    <h2 class="text-base font-bold text-slate-900 mb-4">Plan Limits</h2>
    <div class="grid sm:grid-cols-4 gap-4">
        <div class="bg-slate-50 rounded-lg p-4 text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Voice Minutes</div>
            <div class="text-lg font-bold text-slate-900">{{ $plan['max_minutes'] == -1 ? 'Unlimited' : number_format($plan['max_minutes']) }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Assistants</div>
            <div class="text-lg font-bold text-slate-900">{{ $plan['max_assistants'] == -1 ? 'Unlimited' : $plan['max_assistants'] }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Phone Numbers</div>
            <div class="text-lg font-bold text-slate-900">{{ $plan['max_phone_numbers'] == -1 ? 'Unlimited' : $plan['max_phone_numbers'] }}</div>
        </div>
        <div class="bg-slate-50 rounded-lg p-4 text-center">
            <div class="text-xs font-semibold text-slate-500 uppercase mb-1">Cases</div>
            <div class="text-lg font-bold text-slate-900">{{ $plan['max_cases'] == -1 ? 'Unlimited' : number_format($plan['max_cases']) }}</div>
        </div>
    </div>
</div>

{{-- Invoices Table --}}
<div class="tc-card">
    <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
        <h2 class="text-base font-bold text-slate-900">Recent Invoices</h2>
    </div>

    @if($invoices->isEmpty())
        <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
            <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m4.5 9v1.5m0 0v-1.5m0 1.5h1.5m-1.5 0H8.25m11.25 0c0 1.243-1.007 2.25-2.25 2.25h-5.625a1.875 1.875 0 01-1.875-1.875V11.25A1.875 1.875 0 0113.5 9.375h1.875A2.25 2.25 0 0117.625 11.625v2.625z" />
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">No invoices yet</h3>
            <p class="mt-1 text-sm text-slate-500 max-w-sm">Your payment history and downloadable invoices will appear here once billing activity occurs.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600" aria-label="Invoice History">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500">
                    <tr>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3">Amount</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3 text-right">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoices as $inv)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $inv->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 font-mono text-slate-700">
                                ${{ number_format($inv->amount_due / 100, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($inv->status === 'paid')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold bg-green-50 text-green-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500" aria-hidden="true"></span> Paid
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 capitalize">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400" aria-hidden="true"></span> {{ $inv->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($inv->hosted_invoice_url)
                                    <a href="{{ $inv->hosted_invoice_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:text-blue-800 font-medium inline-flex items-center gap-1 transition-colors" aria-label="View Invoice receipt">
                                        View
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
