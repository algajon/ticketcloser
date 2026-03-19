@extends('layouts.saas')

@section('title')
    ticketcloser • Dashboard
@endsection

@section('header')
    Dashboard
@endsection

@section('content')
    @php
        $ws = $workspace;
        $config = $ws ? \App\Models\AssistantConfig::where('workspace_id', $ws->id)->first() : null;
        $phone = $ws ? \App\Models\WorkspacePhoneNumber::where('workspace_id', $ws->id)->first() : null;

        $checks = [
            [
                'label' => 'Workspace configured',
                'done' => (bool) $ws?->name && (bool) $ws?->slug,
                'href' => route('app.onboarding.company')
            ],
            [
                'label' => 'Assistant synced to Vapi',
                'done' => (bool) $config?->vapi_assistant_id,
                'href' => $ws ? route('app.assistant.edit', $ws) : '#'
            ],
            [
                'label' => 'Phone number provisioned',
                'done' => (bool) $phone?->e164,
                'href' => $ws ? route('app.phone_numbers.index', $ws) : '#'
            ],
        ];
        $setupComplete = collect($checks)->every('done');
        $nextStep = collect($checks)->first(fn($c) => !$c['done']);
        $openCount = $ws ? \App\Models\SupportCase::where('workspace_id', $ws->id)->whereNotIn('status', ['resolved', 'closed'])->count() : 0;
        $latest = $ws ? \App\Models\SupportCase::where('workspace_id', $ws->id)->latest()->first() : null;
        $recentCases = $ws ? \App\Models\SupportCase::where('workspace_id', $ws->id)->latest()->limit(5)->get() : collect();
        $assistantCount = $ws ? \App\Models\AssistantConfig::where('workspace_id', $ws->id)->count() : 0;
        $syncedCount = $ws ? \App\Models\AssistantConfig::where('workspace_id', $ws->id)->whereNotNull('vapi_assistant_id')->count() : 0;
    @endphp

    @if(!$setupComplete)
        <div class="mb-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Get started</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Complete these tasks to start receiving tickets.</p>
                </div>
                <span class="text-xs font-medium text-slate-400">{{ collect($checks)->filter(fn($c) => $c['done'])->count() }} /
                    {{ count($checks) }} done</span>
            </div>

            <div class="grid sm:grid-cols-3 gap-3">
                @php
                    $taskMeta = [
                        [
                            'title' => 'Configure workspace',
                            'desc' => 'Set your company name, slug, and timezone.',
                            'action' => 'Edit settings',
                        ],
                        [
                            'title' => 'Create an assistant',
                            'desc' => 'Build your AI voice agent and sync it to Vapi.',
                            'action' => 'Create assistant',
                        ],
                        [
                            'title' => 'Add a phone number',
                            'desc' => 'Provision a US number so callers can reach your assistant.',
                            'action' => 'Add number',
                        ],
                    ];
                @endphp

                @foreach($checks as $i => $check)
                    @php $meta = $taskMeta[$i]; @endphp
                    <div class="relative rounded-2xl border p-4 flex flex-col gap-3 transition-all
                                                                                                {{ $check['done']
                        ? 'border-green-200 bg-green-50/60'
                        : 'border-slate-200 bg-white shadow-sm hover:shadow-md hover:border-slate-300' }}">

                        {{-- Check indicator --}}
                        @if($check['done'])
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full bg-green-500 flex items-center justify-center">
                                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        @else
                            <div class="absolute top-3 right-3 w-5 h-5 rounded-full border-2 border-slate-300"></div>
                        @endif


                        <div class="flex-1">
                            <p
                                class="text-sm font-semibold {{ $check['done'] ? 'text-green-700 line-through' : 'text-slate-900' }}">
                                {{ $meta['title'] }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1 leading-relaxed">{{ $meta['desc'] }}</p>
                        </div>

                        @if(!$check['done'])
                            <a href="{{ $check['href'] }}"
                                class="w-full text-center text-xs font-semibold py-1.5 rounded-lg transition-colors
                                                                                                                                    bg-slate-900 text-white hover:bg-slate-700">
                                {{ $meta['action'] }} →
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif


    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6 shrink-0">

        {{-- Open cases --}}
        <div class="tc-card-hover p-4 sm:p-6">
            <div>
                <p class="tc-small uppercase tracking-wide font-medium">Open cases</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold tabular-nums" x-data="{ count: 0, target: {{ $openCount }} }"
                    x-init="let start = performance.now(); let dur = 600; (function step(t) { let p = Math.min((t - start) / dur, 1); count = Math.floor(p * target); if(p < 1) requestAnimationFrame(step); else count = target; })(start)"
                    x-text="count">{{ $openCount }}</p>
                <p class="mt-0.5 tc-small">{{ $openCount === 1 ? 'case needs' : 'cases need' }} attention</p>
            </div>
        </div>

        {{-- Assistants --}}
        <a href="{{ $ws ? route('app.assistant.edit', $ws) : '#' }}" class="tc-card-hover p-4 sm:p-6 block group">
            <div>
                <p class="tc-small uppercase tracking-wide font-medium">Assistants</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold tabular-nums">{{ $assistantCount }}</p>
                <p class="mt-0.5 tc-small">{{ $syncedCount }} synced to Vapi</p>
            </div>
        </a>

        {{-- Latest case --}}
        <div class="tc-card-hover p-4 sm:p-6 overflow-hidden">
            <div class="min-w-0">
                <p class="tc-small uppercase tracking-wide font-medium">Latest case</p>
                @if($latest)
                    <p class="mt-1 text-sm font-semibold truncate">{{ $latest->case_number }}</p>
                    <p class="mt-0.5 text-xs text-slate-600 truncate">{{ $latest->title }}</p>
                    <p class="mt-0.5 tc-small">{{ $latest->created_at->diffForHumans() }}</p>
                @else
                    <p class="mt-0.5 tc-small">No cases yet</p>
                @endif
            </div>
        </div>
    </div>

    <div class="tc-card flex-1 flex flex-col min-h-0">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between shrink-0">
            <h2 class="tc-h3">Recent cases</h2>
            <a href="{{ route('app.tickets.index') }}" class="text-xs text-muted hover:text-slate-700 underline">View all
                →</a>
        </div>

        @if($recentCases->isEmpty())
            <div class="flex flex-col flex-1 items-center justify-center py-16 px-4 text-center">
                <h3 class="tc-h3 text-slate-700">No cases yet</h3>
                <p class="mt-1.5 text-sm text-muted max-w-sm">Once a caller creates a ticket via your Vapi assistant, it appears
                    here.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100 overflow-y-auto">
                @foreach($recentCases as $case)
                    <a href="{{ route('app.tickets.show', $case->id) }}"
                        class="block px-6 py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-muted">{{ $case->case_number }}</p>
                                <p class="text-sm font-semibold mt-0.5 truncate">{{ $case->title }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $case->requester_phone ?? $case->requester_email ?? $case->source ?? '' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ match ($case->status ?? 'open') {
                        'open' => 'bg-info-light text-info-fg',
                        'resolved' => 'bg-success-light text-success-fg',
                        'closed' => 'bg-slate-100 text-slate-700',
                        default => 'bg-slate-100 text-slate-700'
                    } }}">{{ ucfirst($case->status ?? 'open') }}</span>
                                <span class="text-xs text-muted whitespace-nowrap">{{ $case->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

@endsection