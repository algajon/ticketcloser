@extends('layouts.saas')

@section('title', 'ticketcloser • Calendar')

@section('header', 'Calendar')

@section('content')
    <div class="mb-8 flex justify-between items-center flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Calendar & Meetings</h1>
            <p class="text-sm text-slate-500 mt-1">Manage synced calendar providers and see upcoming automatic bookings.</p>
        </div>
        <div>
            <a href="{{ route('app.calendar.settings') }}" class="tc-btn-ghost inline-flex items-center gap-1.5"
                aria-label="Go to Calendar Settings">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Calendar Settings
            </a>
        </div>
    </div>

    <div class="grid lg:grid-cols-4 gap-8">
        {{-- Left column (Upcoming Meetings) --}}
        <div class="lg:col-span-3">
            @if(isset($suggested) && $suggested->isNotEmpty())
                <div class="tc-card mb-8 border-amber-200 bg-amber-50">
                    <div class="px-6 py-4 border-b border-amber-200/50 flex justify-between items-center">
                        <h2 class="text-base font-bold text-amber-900">Suggested Meetings to Confirm</h2>
                    </div>
                    <ul class="divide-y divide-amber-100/50">
                        @foreach($suggested as $event)
                            <li class="p-4 sm:p-6 flex flex-col sm:flex-row gap-4 justify-between sm:items-center">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="bg-amber-100/50 text-amber-700 w-12 h-12 rounded flex flex-col items-center justify-center shrink-0">
                                        <span class="text-xs font-bold uppercase">{{ $event->starts_at->format('M') }}</span>
                                        <span class="text-lg font-bold leading-none">{{ $event->starts_at->format('d') }}</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">
                                            {{ $event->supportCase ? $event->supportCase->title : 'Meeting Booking' }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-1">
                                            <p class="text-xs text-slate-500 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $event->starts_at->format('g:i A') }} - {{ $event->ends_at->format('g:i A') }}
                                            </p>
                                        </div>
                                        @if($event->supportCase)
                                            <a href="{{ route('app.tickets.show', $event->supportCase) }}"
                                                class="text-xs text-amber-700 hover:text-amber-900 font-medium inline-block mt-2 flex items-center gap-1 hover:underline">
                                                View related case #{{ $event->supportCase->case_number ?? $event->supportCase->id }}
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex gap-2 shrink-0 mt-3 sm:mt-0">
                                    <form action="{{ route('app.calendar.dismiss', $event) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="tc-btn-ghost text-xs text-slate-500 hover:text-red-600 px-3 py-1.5 h-auto">Dismiss</button>
                                    </form>
                                    <form action="{{ route('app.calendar.confirm', $event) }}" method="POST" class="inline">
                                        @csrf
                                        @if(isset($connections['google']))
                                            <input type="hidden" name="provider" value="google">
                                        @elseif(isset($connections['calendly']))
                                            <input type="hidden" name="provider" value="calendly">
                                        @else
                                            <input type="hidden" name="provider" value="ics">
                                        @endif
                                        <button type="submit"
                                            class="tc-btn-primary text-xs w-full justify-center !bg-amber-600 hover:!bg-amber-700 border-transparent !text-white px-4 py-1.5 h-auto">Confirm</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="tc-card">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h2 class="text-base font-bold text-slate-900">Upcoming Meetings</h2>
                </div>

                @if($upcoming->isEmpty())
                    <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-slate-800">Clear Schedule</h3>
                        <p class="mt-1 text-sm text-slate-500 max-w-sm">You have no upcoming confirmed calendar meetings. Voice
                            assistant suggested events will appear here once confirmed.</p>
                    </div>
                @else
                    <ul class="divide-y divide-slate-100" aria-label="Upcoming Meetings List">
                        @foreach($upcoming as $event)
                            <li
                                class="p-4 sm:p-6 hover:bg-slate-50 transition-colors flex flex-col sm:flex-row gap-4 justify-between sm:items-center group">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="bg-indigo-50 text-indigo-700 w-12 h-12 rounded flex flex-col items-center justify-center shrink-0">
                                        <span class="text-xs font-bold uppercase">{{ $event->starts_at->format('M') }}</span>
                                        <span class="text-lg font-bold leading-none">{{ $event->starts_at->format('d') }}</span>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-slate-900">
                                            {{ $event->supportCase ? $event->supportCase->subject : 'Meeting Booking' }}
                                        </h3>
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-4 mt-1">
                                            <p class="text-xs text-slate-500 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ $event->starts_at->format('g:i A') }} - {{ $event->ends_at->format('g:i A') }}
                                            </p>
                                            <span
                                                class="text-xs border border-slate-200 px-1.5 py-0.5 rounded text-slate-500 capitalize bg-white flex items-center gap-1">
                                                @if($event->provider === 'google')
                                                    <svg class="w-3 h-3 text-red-500" viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.122 17.653c-.322.321-.84.321-1.162 0l-4.542-4.542V6h1.644v6.43l4.06 4.06c.321.322.321.84 0 1.163z" />
                                                    </svg>
                                                @endif
                                                {{ $event->provider }}
                                            </span>
                                        </div>
                                        @if($event->supportCase)
                                            <a href="{{ route('app.tickets.show', $event->supportCase) }}"
                                                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium inline-block mt-2 flex items-center gap-1 group-hover:underline">
                                                View related case #{{ $event->supportCase->id }}
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                @if($event->url)
                                    <div class="shrink-0 mt-3 sm:mt-0">
                                        <a href="{{ $event->url }}" target="_blank" rel="noopener noreferrer"
                                            class="tc-btn-ghost text-xs inline-flex items-center gap-1">
                                            Join Link
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                            </svg>
                                        </a>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Right column (Providers overview) --}}
        <div class="lg:col-span-1">
            <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Integrations</h3>
            <div class="space-y-3">
                @php $hasConnection = false; @endphp

                @if(isset($connections['google']))
                    @php $hasConnection = true; @endphp
                    <div class="tc-card p-4 border-l-4 border-l-green-500">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 15.907 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900 leading-tight">Google Calendar</p>
                                <span class="text-xs text-green-600 font-medium tracking-wide">Connected</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if(isset($connections['calendly']))
                    @php $hasConnection = true; @endphp
                    <div class="tc-card p-4 border-l-4 border-l-green-500">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100">
                                <span class="font-bold text-slate-700">C</span>
                            </div>
                            <div class="w-full">
                                <p class="text-sm font-bold text-slate-900 leading-tight">Calendly</p>
                                <span class="text-xs text-green-600 font-medium tracking-wide block truncate w-full"
                                    title="{{ $connections['calendly']->calendly_scheduling_link }}">{{ parse_url($connections['calendly']->calendly_scheduling_link, PHP_URL_PATH) ?? 'Connected' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!$hasConnection)
                    <div class="tc-card p-5 bg-slate-50 border-dashed border-2">
                        <p class="text-sm font-semibold text-slate-800 mb-1">No integrations</p>
                        <p class="text-xs text-slate-500 mb-3">Connect a calendar to allow your voice agent to schedule
                            meetings.</p>
                        <a href="{{ route('app.calendar.settings') }}"
                            class="tc-btn-primary text-xs w-full justify-center">Connect Provider</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection