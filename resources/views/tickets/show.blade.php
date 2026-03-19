@extends('layouts.saas')

@section('title')
    ticketcloser • {{ $case->case_number }}
@endsection

@section('header')
    Case {{ $case->case_number }}
@endsection

@section('content')
    @php
        $statusColor = match ($case->status) {
            'new' => 'info',
            'triaged' => 'primary',
            'in_progress' => 'info',
            'waiting' => 'warning',
            'resolved' => 'success',
            'closed' => 'slate',
            default => 'slate',
        };
        $priorityColor = match ($case->priority) {
            'critical' => 'danger',
            'high' => 'warning',
            default => 'slate',
        };

        $badgeColors = [
            'slate' => 'bg-slate-100 text-slate-700',
            'success' => 'bg-success-light text-success-fg',
            'warning' => 'bg-warning-light text-warning-fg',
            'danger' => 'bg-danger-light text-danger-fg',
            'info' => 'bg-info-light text-info-fg',
            'primary' => 'bg-orange-50 text-orange-700',
        ];
        $statusCls = $badgeColors[$statusColor] ?? $badgeColors['slate'];
        $priorityCls = $badgeColors[$priorityColor] ?? $badgeColors['slate'];
    @endphp

    {{-- Back link --}}
    <a href="{{ route('app.tickets.index') }}"
        class="inline-flex items-center gap-1 text-sm text-muted hover:text-slate-900 transition-colors mb-5">
        ← Back to Cases
    </a>

    <div class="grid lg:grid-cols-3 gap-4">

        <div class="lg:col-span-2 space-y-4">

            <div class="tc-card p-4 sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="tc-small uppercase tracking-wide font-semibold">{{ $case->case_number }}</p>
                        <h1 class="tc-h2 mt-1">{{ $case->title }}</h1>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="tc-small uppercase tracking-wide">Created</p>
                        <p class="text-sm text-slate-700 font-medium">{{ $case->created_at->format('M j, Y') }}</p>
                        <p class="tc-small">{{ $case->created_at->format('g:i A') }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">{{ str_replace('_', ' ', $case->status) }}</span>
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityCls }}">{{ $case->priority }}
                        priority</span>
                    @if($case->category)
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColors['slate'] }}">{{ $case->category }}</span>
                    @endif
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColors['slate'] }}">via
                        {{ $case->source ?? 'unknown' }}</span>
                </div>

                <div class="mt-5">
                    <p class="text-sm font-semibold text-slate-900 mb-2">Description</p>
                    <p class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $case->description }}</p>
                </div>
            </div>

            <div class="tc-card p-6">
                <h2 class="tc-h3 mb-4">Activity</h2>

                @forelse($case->events as $event)
                    <div class="rounded-xl border border-slate-200 p-4 mb-3 last:mb-0">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-sm font-semibold text-slate-800">{{ $event->type }}</p>
                            <span class="tc-small">{{ $event->created_at->format('M j, g:i A') }}</span>
                        </div>
                        @if($event->data)
                            <pre
                                class="text-xs bg-slate-50 border border-slate-200 rounded-xl p-3 overflow-auto max-h-48 font-mono text-slate-700">{{ json_encode($event->data, JSON_PRETTY_PRINT) }}</pre>
                        @endif
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                        <h3 class="tc-h3 text-slate-700">No events yet</h3>
                        <p class="mt-1.5 text-sm text-muted max-w-sm">Events will appear here as the case progresses.</p>
                    </div>
                @endforelse
            </div>

        </div>

        <div class="space-y-4">

            <div class="tc-card p-6">
                <h2 class="tc-h3 mb-3">Update status</h2>
                <form method="POST" action="{{ route('app.cases.status.update', [$workspace, $case]) }}"
                    x-data="{ loading: false }" @submit="loading = true" class="space-y-3">
                    @csrf
                    <label for="status-select" class="sr-only">Status</label>
                    <select id="status-select" name="status" class="tc-input">
                        @foreach(['new', 'triaged', 'in_progress', 'waiting', 'resolved', 'closed'] as $s)
                            <option value="{{ $s }}" @selected($case->status === $s)>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="tc-btn-primary w-full" x-bind:disabled="loading">
                        <span x-text="loading ? 'Saving…' : 'Update status'">Update status</span>
                    </button>
                </form>
            </div>

            <div class="tc-card p-6">
                <h2 class="tc-h3 mb-3">Requester</h2>
                <div class="space-y-3">
                    <div>
                        <p class="tc-small uppercase tracking-wide font-medium">Phone</p>
                        <p class="mt-0.5 font-mono text-sm text-slate-800">{{ $case->requester_phone ?? ',' }}</p>
                    </div>
                    <div>
                        <p class="tc-small uppercase tracking-wide font-medium">Email</p>
                        <p class="mt-0.5 text-sm text-slate-800">{{ $case->requester_email ?? ',' }}</p>
                    </div>
                </div>
            </div>

            <div class="tc-card p-6">
                <h2 class="tc-h3 mb-3">Integration</h2>
                <p class="tc-small uppercase tracking-wide font-medium mb-1.5">External call ID</p>
                @if($case->external_call_id)
                    <div x-data="{ copied: false, revealed: true }"
                        class="group flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono">
                        <span class="flex-1 truncate text-slate-700 select-all" x-text="'{{ $case->external_call_id }}'"></span>
                        <button
                            @click="navigator.clipboard.writeText('{{ $case->external_call_id }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                            class="flex-shrink-0 text-xs text-muted hover:text-slate-800 transition-colors"
                            :aria-label="copied ? 'Copied!' : 'Copy'">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" class="text-success-fg">Copied</span>
                        </button>
                    </div>
                @else
                    <p class="text-sm text-muted">,</p>
                @endif
            </div>

        </div>
    </div>

@endsection