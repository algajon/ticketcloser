@extends('layouts.saas')

@section('title', 'tickIt - Call analytics')
@section('header_eyebrow', 'Call analytics')
@section('header', 'Call analytics')
@section('header_description', 'See call volume, minutes, ticket rate, and bookings.')

@section('content')
    <div class="space-y-6">
        @include('calls.partials.nav', ['workspace' => $workspace, 'active' => 'analytics'])

        @php
            $costCoverageLabel = $analytics['total_calls'] > 0
                ? $analytics['cost_count'].'/'.$analytics['total_calls'].' priced'
                : 'No recent calls yet';
            $averageCostLabel = $analytics['avg_cost'] !== null
                ? 'Avg '.\App\Models\CallEvent::formatUsdCost($analytics['avg_cost'])
                : 'No cost yet';
        @endphp

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <x-ui.stat-card label="Calls (30 days)" :value="$analytics['total_calls']" :hint="$analytics['total_minutes'].' minutes'" />
            <x-ui.stat-card label="Call to ticket rate" :value="$analytics['call_to_case_rate'].'%'" :hint="$analytics['voice_cases'].' tickets'" />
            <x-ui.stat-card label="Recording coverage" :value="$analytics['recording_rate'].'%'" :hint="$analytics['transcript_rate'].'% transcripts'" />
            <x-ui.stat-card label="Call cost (30 days)" :value="\App\Models\CallEvent::formatUsdCost($analytics['total_cost'])" :hint="$analytics['avg_cost'] !== null ? $averageCostLabel : $costCoverageLabel" />
            <x-ui.stat-card label="Booked meetings" :value="$analytics['booked_meetings']" :hint="$analytics['avg_duration'].'s avg'" />
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
            <x-ui.panel title="7 day trend" description="Calls vs tickets over the last week.">
                <div class="space-y-4">
                    @php
                        $peak = max(1, collect($analytics['daily_trend'])->flatMap(fn ($day) => [$day['calls'], $day['cases']])->max());
                    @endphp

                    @foreach($analytics['daily_trend'] as $day)
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div class="text-sm font-semibold text-slate-900">{{ $day['label'] }}</div>
                                <div class="flex items-center gap-4 text-xs uppercase tracking-[0.16em] text-slate-500">
                                    <span>{{ $day['calls'] }} calls</span>
                                    <span>{{ $day['cases'] }} tickets</span>
                                </div>
                            </div>

                            <div class="mt-3 grid gap-2">
                                <div class="rounded-full bg-slate-200/90">
                                    <div class="h-2 rounded-full bg-slate-900" style="width: {{ ($day['calls'] / $peak) * 100 }}%"></div>
                                </div>
                                <div class="tc-accent-track rounded-full">
                                    <div class="tc-accent-fill h-2 rounded-full" style="width: {{ ($day['cases'] / $peak) * 100 }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.panel>

            <x-ui.panel title="Recent calls" description="Quick links to the latest calls.">
                @if($analytics['recent_calls']->isEmpty())
                    <x-ui.empty-state title="No recent calls" description="Recent calls will show up here once traffic starts." />
                @else
                    <div class="space-y-3">
                        @foreach($analytics['recent_calls'] as $call)
                            <a href="{{ route('app.calls.show', [$workspace, $call]) }}" class="block rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4 transition hover:border-slate-300 hover:bg-white">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-950">{{ $call->from_number ?? 'Unknown caller' }}</div>
                                        <div class="mt-1 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $call->created_at->format('M j, g:i A') }}</div>
                                        <div class="mt-2 text-xs font-medium text-slate-600">
                                            Cost:
                                            <span class="font-semibold text-slate-900">{{ $call->formattedCost() }}</span>
                                        </div>
                                    </div>
                                    <x-ui.badge tone="slate">{{ $call->duration_seconds ? $call->duration_seconds.'s' : 'n/a' }}</x-ui.badge>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ui.panel>
        </div>
    </div>
@endsection
