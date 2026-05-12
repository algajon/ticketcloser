@extends('layouts.saas')

@section('title', 'tickIt - Call Log')
@section('header_eyebrow', 'Conversation review')
@section('header', 'Call log')
@section('header_description', 'Review calls, transcripts, and recordings.')

@section('content')
    <div class="space-y-6">
        @include('calls.partials.nav', ['workspace' => $workspace, 'active' => 'log'])

        <x-ui.panel title="Recent calls" description="Newest calls first.">
            @if($events->isEmpty())
                <x-ui.empty-state title="No calls yet" description="Calls will show up here after your first test or live call." />
            @else
                <div class="space-y-4">
                    @foreach($events as $e)
                        <div class="rounded-[1.45rem] border border-slate-200 bg-slate-50/70 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-ui.badge tone="slate">{{ $e->created_at->format('M j, Y') }}</x-ui.badge>
                                        @if($e->duration_seconds)
                                            <x-ui.badge tone="info">{{ $e->duration_seconds }}s</x-ui.badge>
                                        @endif
                                        @if($e->transcriptLanguageLabel())
                                            <x-ui.badge tone="warning">{{ $e->transcriptLanguageLabel() }}</x-ui.badge>
                                        @endif
                                        @if($e->queue_id)
                                            <x-ui.badge tone="primary">Queue {{ $e->queue_id }}</x-ui.badge>
                                        @endif
                                    </div>

                                    <div class="mt-3 text-base font-semibold text-slate-950">
                                        {{ $e->from_number ?? 'Unknown caller' }}
                                        <span class="font-normal text-slate-500">to {{ $e->to_number ?? 'workspace line' }}</span>
                                    </div>

                                    @if($e->transcript)
                                        <div class="mt-4 rounded-[1.25rem] border border-slate-200 bg-white px-4 py-4">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Transcript preview</div>
                                                @if($e->transcriptLanguageSourceLabel())
                                                    <span class="text-[0.7rem] font-medium uppercase tracking-[0.14em] text-slate-400">{{ $e->transcriptLanguageSourceLabel() }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-slate-700">{{ \Illuminate\Support\Str::limit($e->transcript, 900) }}</p>
                                        </div>
                                    @else
                                        <p class="mt-4 text-sm leading-6 text-slate-600">No transcript was saved for this call.</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-3 lg:flex-col lg:items-end">
                                    <div class="text-sm text-slate-500">{{ $e->created_at->diffForHumans() }}</div>
                                    <a href="{{ route('app.calls.show', [$workspace, $e]) }}" class="tc-btn-ghost !px-3 !py-2 text-xs">View details</a>
                                    @if($e->recording_url)
                                        <a href="{{ $e->recording_url }}" class="tc-btn-secondary !px-3 !py-2 text-xs" target="_blank">Recording</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($events->hasPages())
                    <div class="mt-6">
                        {{ $events->links() }}
                    </div>
                @endif
            @endif
        </x-ui.panel>
    </div>
@endsection
