@extends('layouts.saas')

@section('title')
    ticketcloser • Cases
@endsection

@section('header')
    Cases
@endsection

@section('content')
    @php
        $statusColors = [
            'new' => 'info',
            'triaged' => 'primary',
            'in_progress' => 'info',
            'waiting' => 'warning',
            'resolved' => 'success',
            'closed' => 'slate',
        ];
        $priorityColors = [
            'low' => 'slate',
            'normal' => 'slate',
            'high' => 'warning',
            'critical' => 'danger',
        ];

        $badgeColors = [
            'slate' => 'bg-slate-100 text-slate-700',
            'success' => 'bg-success-light text-success-fg',
            'warning' => 'bg-warning-light text-warning-fg',
            'danger' => 'bg-danger-light text-danger-fg',
            'info' => 'bg-info-light text-info-fg',
            'primary' => 'bg-orange-50 text-orange-700',
        ];
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="tc-page-header mb-0">
            <h1>Cases</h1>
            <p>All tickets for {{ $workspace->name }}.</p>
        </div>
        <a href="{{ route('app.assistant.edit', $workspace) }}"
            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs bg-slate-100 text-slate-700 rounded-btn hover:bg-slate-200">Assistant
            settings</a>
    </div>

    <div class="tc-card p-4 sm:p-6 mb-4">
        <form method="GET" class="grid md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
                <div class="space-y-1.5">
                    <label for="search-q" class="block text-sm font-medium text-slate-800">Search</label>
                    <input id="search-q" name="q" value="{{ $q }}" placeholder="Case number, title, phone, email…"
                        class="tc-input" />
                </div>
            </div>
            <div>
                <div class="space-y-1.5">
                    <label for="filter-status" class="block text-sm font-medium text-slate-800">Status</label>
                    <select id="filter-status" name="status" class="tc-input">
                        <option value="" @selected($status === '')>All statuses</option>
                        <option value="new" @selected($status === 'new')>New</option>
                        <option value="triaged" @selected($status === 'triaged')>Triaged</option>
                        <option value="in_progress" @selected($status === 'in_progress')>In progress</option>
                        <option value="waiting" @selected($status === 'waiting')>Waiting</option>
                        <option value="resolved" @selected($status === 'resolved')>Resolved</option>
                        <option value="closed" @selected($status === 'closed')>Closed</option>
                    </select>
                </div>
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="tc-btn-primary">Apply filters</button>
            </div>
        </form>
    </div>

    {{-- Assistant tabs --}}
    @if($assistants->count() > 0)
        <div class="flex items-center gap-1 overflow-x-auto pb-1 mb-4 -mt-1">
            <a href="{{ request()->fullUrlWithQuery(['assistant' => '']) }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors outline-none
                    {{ $assistant === '' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All
                <span class="text-xs opacity-70">({{ \App\Models\SupportCase::where('workspace_id', $workspace->id)->count() }})</span>
            </a>
            @foreach($assistants as $asst)
                <a href="{{ request()->fullUrlWithQuery(['assistant' => $asst->id]) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors outline-none
                        {{ $assistant == $asst->id ? 'bg-orange-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $asst->name }}
                    <span class="text-xs opacity-70">({{ \App\Models\SupportCase::where('workspace_id', $workspace->id)->where('assistant_config_id', $asst->id)->count() }})</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="tc-card">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200 flex items-center justify-between">
            <span class="tc-h3">{{ $cases->total() }} {{ Str::plural('case', $cases->total()) }}</span>
            <span class="tc-small">Newest first</span>
        </div>

        @if($cases->count() === 0)
            <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                <h3 class="tc-h3 text-slate-700">No cases yet</h3>
                <p class="mt-1.5 text-sm text-muted max-w-sm">Make a test call from the Setup page to create your first ticket.
                </p>
                <div class="mt-5"><a href="{{ route('app.dashboard') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-btn bg-slate-900 text-white">Go to dashboard →</a>
                </div>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($cases as $case)
                    <a href="{{ route('app.tickets.show', $case->id) }}"
                        class="block px-4 sm:px-6 py-3 sm:py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs font-semibold text-muted">{{ $case->case_number }}</span>
                                    @php $sc = $statusColors[$case->status] ?? 'slate';
                                    $pc = $priorityColors[$case->priority] ?? 'slate'; @endphp
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColors[$sc] ?? $badgeColors['slate'] }}">{{ str_replace('_', ' ', $case->status) }}</span>
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColors[$pc] ?? $badgeColors['slate'] }}">{{ $case->priority }}</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-900 truncate">{{ $case->title }}</p>
                                <p class="mt-0.5 text-xs text-muted line-clamp-2">{{ $case->description }}</p>
                                <div class="mt-1.5 flex gap-3 text-xs text-muted">
                                    @if($case->category)
                                        <span>{{ $case->category }}</span>
                                    @endif
                                    @if($case->source)
                                        <span>via {{ $case->source }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="tc-small whitespace-nowrap shrink-0 hidden sm:block">
                                {{ $case->created_at->format('M j, g:i A') }}
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($cases->hasPages())
                <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-slate-200">
                    {{ $cases->withQueryString()->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection