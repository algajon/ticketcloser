@extends('layouts.saas')

@section('title', 'tickIt - Dashboard')
@section('header_eyebrow', 'Operations overview')
@section('header', 'Dashboard')
@section('header_description', 'See what is live, what still needs setup, and what came in most recently.')

@section('header_actions')
    @if($workspace)
        <a href="{{ route('app.calls.analytics', $workspace) }}" class="tc-btn-secondary">Analytics</a>
        <a href="{{ route('app.tickets.index') }}" class="tc-btn-primary">Review tickets</a>
    @endif
@endsection

@section('content')
    @php
        $dashboard = $dashboard ?? [];
        $checks = $dashboard['checks'] ?? [];
        $setupComplete = $dashboard['setup_complete'] ?? false;
        $launchReady = $dashboard['launch_ready'] ?? false;
        $primaryAttention = $dashboard['primary_attention'] ?? null;
        $secondaryAttention = $dashboard['secondary_attention'] ?? collect();
        $recentCases = $dashboard['recent_cases'] ?? collect();
        $recentCalls = $dashboard['recent_calls'] ?? collect();
        $isPropertyManagement = $dashboard['is_property_management'] ?? false;
        $maintenanceStats = $dashboard['maintenance_stats'] ?? null;
        $urgentQueue = $dashboard['urgent_queue'] ?? collect();
        $nextVisit = $dashboard['next_visit'] ?? null;
        $openCount = (int) ($dashboard['open_count'] ?? 0);
        $newToday = (int) ($dashboard['new_today'] ?? 0);
        $callCountWeek = (int) ($dashboard['call_count_week'] ?? 0);
        $minutesUsed = (int) ($dashboard['minutes_used'] ?? 0);
        $setupPercent = (int) ($dashboard['setup_percent'] ?? 0);
        $completeCount = (int) ($dashboard['complete_count'] ?? 0);
        $hasSyncedAssistant = (bool) ($dashboard['has_synced_assistant'] ?? false);
        $livePhoneCount = (int) ($dashboard['live_phone_count'] ?? 0);
        $hasCalendarConnection = (bool) ($dashboard['has_calendar_connection'] ?? false);
    @endphp

    <div class="tc-dashboard space-y-10">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.18fr)_minmax(320px,0.82fr)]" style="align-items:start;">
            <x-ui.panel class="tc-dashboard-panel" style="align-self:start;" title="Workspace pulse" description="See what is live and what the team will feel first.">
                <div class="tc-dashboard-pulse-grid">
                    <div class="tc-dashboard-pulse-metric">
                        <p class="tc-dashboard-pulse-label">Open tickets</p>
                        <p class="tc-dashboard-pulse-value">{{ $openCount }}</p>
                        <p class="tc-dashboard-pulse-hint">{{ $newToday }} today</p>
                    </div>

                    <div class="tc-dashboard-pulse-metric">
                        <p class="tc-dashboard-pulse-label">Calls this week</p>
                        <p class="tc-dashboard-pulse-value">{{ $callCountWeek }}</p>
                        <p class="tc-dashboard-pulse-hint">{{ $minutesUsed }} minutes used</p>
                    </div>

                    <div class="tc-dashboard-pulse-metric">
                        <p class="tc-dashboard-pulse-label">Launch progress</p>
                        <p class="tc-dashboard-pulse-value">{{ $setupPercent }}%</p>
                        <p class="tc-dashboard-pulse-hint">{{ $completeCount }}/{{ count($checks) }} steps complete</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-2.5">
                    <x-ui.badge tone="{{ $hasSyncedAssistant ? 'success' : 'warning' }}">{{ $hasSyncedAssistant ? 'Assistant live' : 'Assistant pending' }}</x-ui.badge>
                    <x-ui.badge tone="{{ $livePhoneCount > 0 ? 'success' : 'warning' }}">{{ $livePhoneCount > 0 ? 'Number live' : 'Number pending' }}</x-ui.badge>
                    <x-ui.badge tone="{{ $hasCalendarConnection ? 'success' : 'slate' }}">{{ $hasCalendarConnection ? 'Calendar connected' : 'Calendar optional' }}</x-ui.badge>
                </div>

                @if($launchReady && !($dashboard['has_recent_call'] ?? false))
                    <div class="tc-dashboard-pulse-callout mt-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-sm font-semibold text-slate-950">Ready for a first proof call.</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Your workflow is set to <span class="font-semibold text-slate-900">{{ $dashboard['use_case_label'] ?? 'Customer support' }}</span>,
                                    at least one assistant is synced, and callers can already reach the line.
                                    One fresh test call will confirm the full flow end to end.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <a href="{{ route('app.phone_numbers.index', $workspace) }}" class="tc-btn-primary">See live number</a>
                                <a href="{{ route('app.assistant.edit', $workspace) }}" class="tc-btn-secondary">Review assistant</a>
                            </div>
                        </div>
                    </div>
                @endif
            </x-ui.panel>

            <div class="space-y-6">
                <x-ui.panel class="tc-dashboard-panel" title="Next move" description="The clearest action from here.">
                    @if(! $primaryAttention)
                        <x-ui.empty-state title="Everything looks good" description="Setup is done and there are no urgent blockers right now." />
                    @else
                        <div class="tc-dashboard-next-card tc-meta-card-strong {{ $primaryAttention['tone'] === 'danger' ? 'border-red-200 bg-red-50/80' : ($primaryAttention['tone'] === 'warning' ? 'border-amber-200 bg-amber-50/80' : ($primaryAttention['tone'] === 'info' ? 'border-blue-200 bg-blue-50/80' : 'tc-accent-surface')) }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-slate-950">{{ $primaryAttention['title'] }}</div>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $primaryAttention['copy'] }}</p>
                                </div>
                                <x-ui.badge tone="{{ $primaryAttention['tone'] }}">{{ $primaryAttention['tone'] }}</x-ui.badge>
                            </div>
                            <div class="mt-5">
                                <a href="{{ $primaryAttention['href'] }}" class="tc-btn-secondary !px-3 !py-2 text-xs">{{ $primaryAttention['action'] }}</a>
                            </div>
                        </div>

                        @if($secondaryAttention->isNotEmpty())
                            <div class="mt-4 space-y-3">
                                @foreach($secondaryAttention as $item)
                                    <div class="tc-dashboard-next-card-muted tc-meta-card">
                                        <div class="text-sm font-semibold text-slate-950">{{ $item['title'] }}</div>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['copy'] }}</p>
                                        <div class="mt-3">
                                            <a href="{{ $item['href'] }}" class="tc-accent-link text-sm font-semibold">{{ $item['action'] }}</a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </x-ui.panel>
            </div>
        </div>

        <div class="space-y-4">
            <div class="tc-dashboard-section-heading">
                <p class="tc-dashboard-section-label">Recent activity</p>
                <h2 class="tc-dashboard-section-title">Newest tickets and latest calls</h2>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <x-ui.panel class="tc-dashboard-panel" title="Recent tickets" description="Newest first." bodyClass="p-0">
                    @if($recentCases->isEmpty())
                        <div class="p-6">
                            <x-ui.empty-state title="No tickets yet" description="Tickets will show up here after your first call." actionText="Open assistants" :actionHref="$workspace ? route('app.assistant.edit', $workspace) : '#'" />
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($recentCases as $case)
                                @php
                                    $statusTone = match ($case->status) {
                                        'resolved' => 'success',
                                        'waiting' => 'warning',
                                        'in_progress' => 'info',
                                        'triaged' => 'primary',
                                        default => 'slate',
                                    };
                                    $priorityTone = match ($case->priority) {
                                        'critical' => 'danger',
                                        'high' => 'warning',
                                        default => 'slate',
                                    };
                                @endphp
                                <a href="{{ route('app.tickets.show', $case->id) }}" class="block px-6 py-5 transition hover:bg-slate-50/80">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="tc-label-eyebrow-tight">{{ $case->case_number }}</span>
                                                <x-ui.badge :tone="$statusTone">{{ str_replace('_', ' ', $case->status) }}</x-ui.badge>
                                                <x-ui.badge :tone="$priorityTone">{{ $case->priority }}</x-ui.badge>
                                            </div>
                                            <p class="mt-3 text-base font-semibold text-slate-950">{{ $case->title }}</p>
                                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                @if($case->requester_phone)
                                                    <span>{{ $case->requester_phone }}</span>
                                                @endif
                                                @if($case->category)
                                                    <span>{{ $case->category }}</span>
                                                @endif
                                                <span>{{ $case->source ?? 'voice' }}</span>
                                            </div>
                                        </div>

                                        <div class="shrink-0 text-sm text-slate-500">
                                            {{ $case->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <x-slot:actions>
                        <a href="{{ route('app.tickets.index') }}" class="tc-btn-ghost !px-3 !py-2 text-xs">View all tickets</a>
                    </x-slot:actions>
                </x-ui.panel>

                <x-ui.panel class="tc-dashboard-panel" title="Recent calls" description="Latest activity on the line.">
                    @if($recentCalls->isEmpty())
                        <x-ui.empty-state title="No recent calls" description="Place a test call to check the flow." />
                    @else
                        <div class="tc-dashboard-call-list">
                            @foreach($recentCalls as $call)
                                <a href="{{ route('app.calls.show', [$workspace, $call]) }}" class="tc-dashboard-call-item tc-meta-card block transition hover:border-slate-300 hover:bg-white">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-950">{{ $call->from_number ?? 'Unknown caller' }}</div>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                <span>{{ $call->created_at->format('M j, g:i A') }}</span>
                                                @if($call->duration_seconds)
                                                    <span>{{ $call->duration_seconds }}s</span>
                                                @endif
                                                @if($call->transcriptLanguageLabel())
                                                    <span>{{ $call->transcriptLanguageLabel() }}</span>
                                                @endif
                                            </div>
                                            @if($call->transcript)
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($call->transcript, 110) }}</p>
                                            @endif
                                        </div>

                                        @if($call->recording_url)
                                            <x-ui.badge tone="success">Recording</x-ui.badge>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <x-slot:actions>
                        <a href="{{ route('app.calls.index', $workspace) }}" class="tc-btn-ghost !px-3 !py-2 text-xs">Call log</a>
                        <a href="{{ route('app.calls.analytics', $workspace) }}" class="tc-btn-secondary !px-3 !py-2 text-xs">Analytics</a>
                    </x-slot:actions>
                </x-ui.panel>
            </div>
        </div>

        @if($isPropertyManagement && $maintenanceStats)
            <div class="space-y-4">
                <div class="tc-dashboard-section-heading">
                    <p class="tc-dashboard-section-label">Maintenance queue</p>
                    <h2 class="tc-dashboard-section-title">What needs movement today</h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-ui.stat-card class="tc-dashboard-panel" label="Urgent review" :value="(int) ($maintenanceStats->urgent_review_count ?? 0)" hint="Urgent tickets open" tone="red" />
                    <x-ui.stat-card class="tc-dashboard-panel" label="Dispatched" :value="(int) ($maintenanceStats->dispatched_count ?? 0)" hint="Assigned now" tone="blue" />
                    <x-ui.stat-card class="tc-dashboard-panel" label="Scheduled" :value="(int) ($maintenanceStats->scheduled_count ?? 0)" hint="Visits booked" tone="emerald" />
                    <x-ui.stat-card class="tc-dashboard-panel" label="Waiting on resident" :value="(int) ($maintenanceStats->waiting_on_resident_count ?? 0)" hint="Resident follow-up needed" tone="amber" />
                </div>
            </div>
        @endif

        @if(! $setupComplete && count($checks) > 0)
            <x-ui.panel class="tc-dashboard-panel" title="Finish setup" description="These are the next steps between signup and a dependable first live workflow.">
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($checks as $check)
                        <div class="tc-meta-card-strong {{ $check['done'] ? 'border-emerald-200 bg-emerald-50/80' : 'border-slate-200 bg-slate-50/80' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold {{ $check['done'] ? 'text-emerald-800' : 'text-slate-900' }}">{{ $check['label'] }}</div>
                                    <p class="mt-2 text-sm leading-6 {{ $check['done'] ? 'text-emerald-700' : 'text-slate-600' }}">{{ $check['description'] }}</p>
                                </div>
                                <x-ui.badge tone="{{ $check['done'] ? 'success' : 'slate' }}">{{ $check['done'] ? 'Done' : 'Pending' }}</x-ui.badge>
                            </div>

                            @if(! $check['done'])
                                <div class="mt-4">
                                    <a href="{{ $check['href'] }}" class="tc-btn-secondary !px-3 !py-2 text-xs">{{ $check['action'] }}</a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-ui.panel>
        @endif

        @if($isPropertyManagement)
            <div class="grid gap-8 xl:grid-cols-[minmax(0,1.08fr)_minmax(320px,0.92fr)]">
                <x-ui.panel class="tc-dashboard-panel" title="Maintenance priority queue" description="The work a property team usually wants first.">
                    @if($urgentQueue->isEmpty())
                        <x-ui.empty-state title="No urgent maintenance queue" description="Critical and high-priority maintenance tickets will show up here." />
                    @else
                        <div class="space-y-3">
                            @foreach($urgentQueue as $case)
                                <a href="{{ route('app.tickets.show', $case->id) }}" class="tc-meta-card block transition hover:border-slate-300 hover:bg-white">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="tc-label-eyebrow-tight">{{ $case->case_number }}</span>
                                                <x-ui.badge :tone="\App\Models\SupportCase::priorityTone($case->priority)">{{ $case->priority }}</x-ui.badge>
                                                @if($case->ops_stage)
                                                    <x-ui.badge :tone="\App\Models\SupportCase::opsStageTone($case->ops_stage)">{{ \App\Models\SupportCase::opsStageLabel($case->ops_stage) }}</x-ui.badge>
                                                @endif
                                            </div>
                                            <div class="mt-3 text-base font-semibold text-slate-950">{{ $case->title }}</div>
                                            <div class="mt-2 text-sm text-slate-600">
                                                {{ $case->contact?->name ?: 'Caller on file' }}
                                                @if($case->contact?->property_code)
                                                    <span class="text-slate-400">•</span>
                                                    {{ $case->contact->property_code }}@if($case->contact?->unit), Unit {{ $case->contact->unit }}@endif
                                                @endif
                                            </div>
                                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                                @if($case->vendor_name)
                                                    <span>Vendor: {{ $case->vendor_name }}</span>
                                                @endif
                                                @if($case->preferred_visit_window)
                                                    <span>Visit: {{ $case->preferred_visit_window }}</span>
                                                @endif
                                                <span>{{ $case->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <x-slot:actions>
                        <a href="{{ route('app.tickets.index', ['ops_stage' => 'urgent_review']) }}" class="tc-btn-ghost !px-3 !py-2 text-xs">Open urgent queue</a>
                    </x-slot:actions>
                </x-ui.panel>

                <div class="space-y-6">
                    <x-ui.panel class="tc-dashboard-panel" title="Next scheduled visit" description="The closest maintenance follow-up on the calendar.">
                        @if(!$nextVisit)
                            <x-ui.empty-state title="No scheduled visit yet" description="Visits will show up here once maintenance follow-up is booked." />
                        @else
                            <div class="tc-meta-card-strong border-emerald-200 bg-emerald-50/80">
                                <div class="tc-label-eyebrow text-emerald-700">Upcoming visit</div>
                                <div class="mt-3 text-lg font-semibold text-emerald-950">{{ $nextVisit->supportCase?->title ?? 'Maintenance follow-up' }}</div>
                                <div class="mt-2 text-sm leading-6 text-emerald-800">
                                    {{ $nextVisit->starts_at?->format('M j, Y \a\t g:i A') }}
                                    @if($nextVisit->contact?->property_code)
                                        <span class="text-emerald-600">•</span>
                                        {{ $nextVisit->contact->property_code }}@if($nextVisit->contact?->unit), Unit {{ $nextVisit->contact->unit }}@endif
                                    @endif
                                </div>
                                @if($nextVisit->supportCase)
                                    <div class="mt-4">
                                        <a href="{{ route('app.tickets.show', $nextVisit->supportCase->id) }}" class="tc-accent-link text-sm font-semibold">Open related ticket</a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </x-ui.panel>
                </div>
            </div>
        @endif

    </div>
@endsection
