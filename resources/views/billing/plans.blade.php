@extends('layouts.saas')

@section('title', 'tickIt - Plans')
@section('header_eyebrow', 'Billing')
@section('header', 'Plans')
@section('header_description', 'Pick a base plan, then pay for extra minutes only when usage goes over.')

@section('content')
    @php
        $salesEmail = config('mail.from.address');
        if (! filled($salesEmail) || $salesEmail === 'hello@example.com') {
            $salesEmail = 'jon@ticketcloser.online';
        }
    @endphp

    <div class="space-y-6">
        <div class="tc-accent-surface rounded-[1.5rem] border px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl">
                    <div class="tc-accent-text-strong text-[0.7rem] font-semibold uppercase tracking-[0.24em]">Usage-based pricing</div>
                    <h2 class="mt-2 text-xl font-semibold text-slate-950">Keep the base fee predictable. Let minutes scale with the team.</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Each paid plan includes a monthly block of voice minutes. If your team goes over, the extra minutes are billed at the plan's lower usage rate instead of forcing a jump to the next tier too early.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('app.dashboard') }}" class="tc-btn-secondary">Skip for now</a>
                    <a href="mailto:{{ $salesEmail }}?subject={{ rawurlencode('tickIt pricing inquiry') }}" class="tc-btn-primary">Talk to sales</a>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-4">
            @foreach($plans as $key => $plan)
                @php
                    $isCurrent = $currentPlan === $key;
                    $isPopular = $key === 'pro';
                    $isFree = $key === 'free';
                    $badgeColors = [
                        'slate' => 'bg-slate-100 text-slate-600',
                        'blue' => 'bg-blue-100 text-blue-700',
                        'indigo' => 'bg-indigo-100 text-indigo-700',
                        'amber' => 'bg-amber-100 text-amber-700',
                    ];
                    $currency = strtoupper($plan['currency'] ?? 'EUR');
                    $currencySymbol = match ($currency) {
                        'USD' => '$',
                        'GBP' => "\u{00A3}",
                        default => "\u{20AC}",
                    };
                    $contactHref = 'mailto:' . $salesEmail . '?subject=' . rawurlencode('tickIt ' . $plan['label'] . ' plan inquiry');
                    $featureHighlights = $plan['feature_highlights'] ?? [];
                @endphp

                <div class="relative flex h-full flex-col rounded-[1.5rem] border {{ $isPopular ? 'tc-accent-card-active bg-white' : 'border-slate-200 bg-white shadow-[0_18px_48px_-34px_rgba(15,23,42,0.18)]' }} p-6">
                    @if($isPopular)
                        <div class="absolute -top-3 left-6">
                            <span class="tc-accent-pill-solid inline-flex rounded-full px-3 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.18em]">Recommended</span>
                        </div>
                    @endif

                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em] {{ $badgeColors[$plan['badge_color']] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $plan['label'] }}
                            </span>
                            <h2 class="mt-4 text-xl font-semibold text-slate-950">{{ $plan['label'] }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $plan['recommended_for'] ?? $plan['description'] }}</p>
                        </div>

                        @if($isCurrent)
                            <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-emerald-700">Current</span>
                        @endif
                    </div>

                    <div class="mt-6">
                        <div class="text-3xl font-extrabold tracking-tight text-slate-950">
                            @if($isFree)
                                Free
                            @else
                                {{ $currencySymbol }}{{ number_format($plan['price_monthly']) }}
                            @endif
                        </div>
                        <div class="mt-1 text-sm font-medium text-slate-500">
                            @if($isFree)
                                Start without a sales call
                            @else
                                Per month base fee
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between rounded-[1rem] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm">
                            <span class="text-slate-500">Included minutes</span>
                            <span class="font-semibold text-slate-950">{{ $plan['max_minutes'] == -1 ? 'Unlimited' : number_format($plan['max_minutes']) }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-[1rem] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm">
                            <span class="text-slate-500">Extra usage</span>
                            <span class="font-semibold text-slate-950">
                                @if(($plan['overage_per_minute'] ?? null) !== null)
                                    {{ $currencySymbol }}{{ number_format($plan['overage_per_minute'], 2) }}/min
                                @else
                                    Upgrade to continue
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-[1rem] border border-slate-200 bg-slate-50/80 px-4 py-3 text-sm">
                            <span class="text-slate-500">Setup</span>
                            <span class="font-semibold text-slate-950">{{ $plan['max_phone_numbers'] == -1 ? 'Unlimited numbers' : $plan['max_phone_numbers'] . ' number' . ($plan['max_phone_numbers'] == 1 ? '' : 's') }}</span>
                        </div>
                    </div>

                    <p class="mt-5 text-sm leading-6 text-slate-600">{{ $plan['description'] }}</p>

                    <ul class="mt-6 space-y-2.5 text-sm text-slate-700">
                        @foreach($featureHighlights as $highlight)
                            <li class="flex items-start gap-2">
                                <span class="mt-2 inline-block h-2 w-2 flex-shrink-0 rounded-full bg-emerald-500"></span>
                                <span>{{ $highlight }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 flex-grow"></div>

                    @if($isCurrent)
                        <button disabled class="tc-btn-secondary w-full cursor-not-allowed !border-slate-200 !bg-slate-50 !text-slate-400">Current plan</button>
                    @elseif($isFree)
                        <form action="{{ route('app.billing.selectPlan') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
                            @csrf
                            <input type="hidden" name="plan" value="free">
                            <button type="submit" class="tc-btn-primary w-full" :disabled="loading">
                                <span x-show="loading" class="tc-spinner" aria-hidden="true"></span>
                                <span x-text="loading ? 'Continuing...' : 'Continue on free'"></span>
                            </button>
                        </form>
                    @else
                        <a href="{{ $contactHref }}" class="tc-btn-secondary w-full">Talk to sales</a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
            <div class="rounded-[1.35rem] border border-slate-200 bg-white px-5 py-5 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.16)]">
                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">How billing works</div>
                <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                    <p>Paid plans give you a monthly base fee plus a lower per-minute usage rate after the included block is used.</p>
                    <p>That keeps early pricing reasonable for smaller teams, while higher-volume teams can scale without jumping plans too fast.</p>
                    <p>Need CRM integration, SMS, or more rollout help? The Enterprise plan is capped at €1,249/month before usage and includes those add-ons.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 rounded-[1.35rem] border border-slate-200 bg-white px-5 py-5 shadow-[0_14px_36px_-28px_rgba(15,23,42,0.16)]">
                <div>
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Still exploring?</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Keep building on free, then move to a paid rollout when your team is ready.</p>
                </div>
                <a href="{{ route('app.dashboard') }}" class="tc-btn-ghost">Skip for now</a>
            </div>
        </div>
    </div>
@endsection
