@extends('layouts.saas')

@section('title', 'tickIt - Call detail')
@section('header_eyebrow', 'Conversation review')
@section('header', 'Call detail')
@section('header_description', 'Recording, transcript, and linked tickets for this call.')

@section('header_actions')
    <a href="{{ route('app.calls.index', $workspace) }}" class="tc-btn-ghost">Back to calls</a>
    <a href="{{ route('app.calls.analytics', $workspace) }}" class="tc-btn-secondary">Call analytics</a>
@endsection

@section('content')
    <div class="min-w-0 space-y-6">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)] [&>*]:min-w-0">
            <x-ui.panel title="Call summary" description="Main details for this call.">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 [&>*]:min-w-0">
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Caller</div>
                        <div class="mt-2 break-all text-sm font-semibold text-slate-950 sm:text-base">{{ $call->from_number ?? 'Unknown caller' }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Destination</div>
                        <div class="mt-2 break-all text-sm font-semibold text-slate-950 sm:text-base">{{ $call->to_number ?? 'Workspace line' }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Started</div>
                        <div class="mt-2 break-words text-sm font-semibold text-slate-950 sm:text-base">{{ $call->created_at->format('M j, Y g:i A') }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Duration</div>
                        <div class="mt-2 break-words text-sm font-semibold text-slate-950 sm:text-base">{{ $call->duration_seconds ? $call->duration_seconds.' seconds' : 'Not captured' }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Call cost</div>
                        <div class="mt-2 break-words text-sm font-semibold text-slate-950 sm:text-base">{{ $call->formattedCost() }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Transcript language</div>
                        <div class="mt-2 break-words text-sm font-semibold text-slate-950 sm:text-base">{{ $call->transcriptLanguageLabel() ?: 'Not labeled yet' }}</div>
                        @if($call->transcriptLanguageSourceLabel())
                            <div class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">{{ $call->transcriptLanguageSourceLabel() }}</div>
                        @endif
                    </div>
                    <div class="min-w-0 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Speech stack</div>
                        <div class="mt-2 break-words text-sm font-semibold text-slate-950 sm:text-base">{{ $call->transcriberLabel() ?: 'Not captured' }}</div>
                        @if($call->configuredLanguageLabel())
                            <div class="mt-1 text-xs uppercase tracking-[0.14em] text-slate-500">Assistant default: {{ $call->configuredLanguageLabel() }}</div>
                        @endif
                    </div>
                </div>

                @if($call->recording_url)
                    <div class="mt-6 min-w-0 rounded-[1.25rem] border border-slate-200 bg-white p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Recording</div>
                        <audio controls class="mt-3 w-full min-w-0">
                            <source src="{{ $call->recording_url }}">
                        </audio>
                        <div class="mt-3">
                            <a href="{{ $call->recording_url }}" target="_blank" class="tc-btn-secondary w-full justify-center !px-3 !py-2 text-xs sm:w-auto">Open recording in new tab</a>
                        </div>
                    </div>
                @endif

                <div class="mt-6 min-w-0 rounded-[1.25rem] border border-slate-200 bg-white p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Transcript</div>
                        @if($call->transcriptLanguageLabel())
                            <x-ui.badge tone="warning">{{ $call->transcriptLanguageLabel() }}</x-ui.badge>
                        @endif
                    </div>
                    @if($call->transcript)
                        <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-7 text-slate-700">{{ $call->transcript }}</p>
                    @else
                        <p class="mt-3 text-sm leading-6 text-slate-600">No transcript was saved for this call.</p>
                    @endif
                </div>
            </x-ui.panel>

            <div class="min-w-0 space-y-6">
                <x-ui.panel title="Related tickets" description="Tickets matched by caller or call ID.">
                    @if($relatedCases->isEmpty())
                        <x-ui.empty-state title="No linked tickets" description="This call has not been matched to a ticket yet." />
                    @else
                        <div class="space-y-3">
                            @foreach($relatedCases as $case)
                                <a href="{{ route('app.tickets.show', $case) }}" class="block min-w-0 rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4 transition hover:border-slate-300 hover:bg-white">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $case->case_number }}</div>
                                            <div class="mt-2 break-words text-sm font-semibold text-slate-950">{{ $case->title }}</div>
                                        </div>
                                        <div class="sm:shrink-0">
                                            <x-ui.badge tone="slate">{{ str_replace('_', ' ', $case->status) }}</x-ui.badge>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </x-ui.panel>
            </div>
        </div>
    </div>
@endsection
