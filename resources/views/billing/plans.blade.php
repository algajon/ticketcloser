@extends('layouts.saas')

@section('title', 'ticketcloser • Choose Your Plan')

@section('header', 'Choose Your Plan')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-extrabold text-slate-900">Pick the right plan for your team</h1>
            <p class="text-slate-500 mt-2 text-base max-w-lg mx-auto">Start free and upgrade as you grow. All plans include
                core AI voice ticketing features.</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($plans as $key => $plan)
                @php
                    $isCurrent = $currentPlan === $key;
                    $isPopular = $key === 'pro';
                    $isEnterprise = $key === 'enterprise';
                    $badgeColors = [
                        'slate' => 'bg-slate-100 text-slate-600',
                        'blue' => 'bg-blue-100 text-blue-700',
                        'indigo' => 'bg-indigo-100 text-indigo-700',
                        'amber' => 'bg-amber-100 text-amber-700',
                    ];
                    $borderColor = $isPopular ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-slate-200';
                @endphp

                <div
                    class="relative flex flex-col tc-card p-6 {{ $borderColor }} transition-shadow hover:shadow-lg {{ $isPopular ? 'shadow-md' : '' }}">
                    {{-- Popular badge --}}
                    @if($isPopular)
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-600 text-white shadow-sm">
                                Most Popular
                            </span>
                        </div>
                    @endif

                    {{-- Plan header --}}
                    <div class="mb-5 {{ $isPopular ? 'pt-2' : '' }}">
                        <span
                            class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold {{ $badgeColors[$plan['badge_color']] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $plan['label'] }}
                        </span>
                    </div>

                    {{-- Price --}}
                    <div class="mb-4">
                        @if($plan['price_monthly'] === 0)
                            <div class="flex items-baseline gap-1">
                                <span class="text-3xl font-extrabold text-slate-900">Free</span>
                            </div>
                        @else
                            <div class="flex items-baseline gap-1">
                                <span
                                    class="text-3xl font-extrabold text-slate-900">${{ number_format($plan['price_monthly']) }}</span>
                                <span class="text-sm text-slate-500 font-medium">/mo</span>
                            </div>
                        @endif
                    </div>

                    {{-- Description --}}
                    <p class="text-sm text-slate-500 mb-6 leading-relaxed">{{ $plan['description'] }}</p>

                    {{-- Features --}}
                    <ul class="space-y-2.5 mb-8 flex-grow">
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>{{ $plan['max_minutes'] == -1 ? 'Unlimited' : number_format($plan['max_minutes']) }} voice
                                minutes</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>{{ $plan['max_assistants'] == -1 ? 'Unlimited' : $plan['max_assistants'] }}
                                assistant{{ $plan['max_assistants'] != 1 ? 's' : '' }}</span>
                        </li>
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            <span>{{ $plan['max_phone_numbers'] == -1 ? 'Unlimited' : $plan['max_phone_numbers'] }} phone
                                number{{ $plan['max_phone_numbers'] != 1 ? 's' : '' }}</span>
                        </li>
                        @if(in_array('calendar_booking', $plan['features']))
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>Calendar booking</span>
                            </li>
                        @endif
                        @if(in_array('prompt_writer', $plan['features']))
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>AI Prompt Writer</span>
                            </li>
                        @endif
                        @if(in_array('priority_support', $plan['features']))
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>Priority support</span>
                            </li>
                        @endif
                        @if(in_array('dedicated_account_manager', $plan['features']))
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>Dedicated account manager</span>
                            </li>
                        @endif
                        @if(in_array('sla', $plan['features']))
                            <li class="flex items-start gap-2 text-sm text-slate-700">
                                <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>SLA guarantee</span>
                            </li>
                        @endif
                    </ul>

                    {{-- CTA --}}
                    @if($isCurrent)
                        <button disabled
                            class="w-full py-2.5 text-sm font-semibold rounded-lg border-2 border-slate-200 text-slate-400 bg-slate-50 cursor-not-allowed">
                            Current Plan
                        </button>
                    @else
                        <form action="{{ route('app.billing.selectPlan') }}" method="POST" x-data="{ loading: false }"
                            @submit="loading = true">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $key }}">
                            <button type="submit"
                                class="w-full py-2.5 text-sm font-semibold rounded-lg transition-all duration-200
                                               {{ $isPopular
                        ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-md'
                        : ($isEnterprise
                            ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm hover:shadow-md'
                            : 'bg-white border-2 border-slate-200 text-slate-700 hover:border-indigo-300 hover:text-indigo-600') }}"
                                :disabled="loading">
                                <span x-show="loading" class="tc-spinner w-4 h-4 mr-1 inline-block" aria-hidden="true"></span>
                                <span
                                    x-text="loading ? 'Redirecting...' : '{{ $key === 'free' ? 'Start Free Trial' : ($isEnterprise ? 'Contact Sales' : 'Get Started') }}'"></span>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Already on free, skip link --}}
        @if($currentPlan === 'free')
            <div class="text-center mt-8">
                <a href="{{ route('app.dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700 underline">
                    Continue with Free Trial →
                </a>
            </div>
        @endif
    </div>
@endsection