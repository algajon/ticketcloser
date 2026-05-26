@extends('layouts.saas')

@section('title', 'tickIt - Calendar')
@section('header_eyebrow', 'Scheduling')
@section('header', 'Calendar & meetings')
@section('header_description', 'Review booking requests, monitor provider status, and keep upcoming meetings in one CRM-style workspace.')

@section('header_actions')
    <a href="{{ route('app.calendar.settings') }}" class="tc-btn-secondary">Calendar settings</a>
@endsection

@section('content')
    @php
        $pendingCount = $suggested->count();
        $upcomingCount = $upcoming->count();
        $googleConnected = isset($connections['google']);
        $calendlyConnected = isset($connections['calendly']);
        $providerCount = collect([$googleConnected, $calendlyConnected])->filter()->count();
        $nextMeeting = $upcoming->first();
        $nextMeetingValue = $nextMeeting ? $nextMeeting->starts_at->format('M j') : 'None';
        $nextMeetingHint = $nextMeeting ? $nextMeeting->starts_at->format('g:i A') : 'No meetings yet';
        $bookableProvider = $googleConnected ? 'Google Calendar' : ($calendlyConnected ? 'Calendly' : 'ICS fallback');
        $nextSevenDaysCount = $upcoming->filter(fn ($event) => $event->starts_at && $event->starts_at->between(now(), now()->copy()->addDays(7)))->count();
    @endphp

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stat-card label="Pending requests" :value="$pendingCount" hint="Need review" tone="amber" />
            <x-ui.stat-card label="Booked meetings" :value="$upcomingCount" hint="Upcoming" tone="blue" />
            <x-ui.stat-card label="Connected providers" :value="$providerCount" :hint="$providerCount ? 'Ready to book' : 'Connect to book'" tone="emerald" />
            <x-ui.stat-card label="Next meeting" :value="$nextMeetingValue" :hint="$nextMeetingHint" tone="orange" />
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.82fr)]">
            <div class="space-y-6">
                <x-ui.panel title="Requests needing review" description="Calls that mentioned a time and still need to be confirmed or dismissed.">
                    @if($suggested->isEmpty())
                        <x-ui.empty-state
                            title="No requests waiting"
                            description="When callers ask for times, those requests will show up here for quick review."
                        />
                    @else
                        <div class="space-y-3">
                            @foreach($suggested as $event)
                                @php
                                    $ticket = $event->supportCase;
                                    $ticketLabel = $ticket?->case_number ?: $ticket?->id;
                                    $contact = $ticket?->contact;
                                @endphp

                                <div class="tc-meta-card-strong border-amber-200 bg-amber-50/70">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="flex min-w-0 gap-5 sm:gap-6">
                                            <div class="tc-meta-icon-tile bg-amber-100/80 text-amber-700">
                                                <span class="tc-label-eyebrow-tight">{{ $event->starts_at->format('M') }}</span>
                                                <span class="text-lg font-semibold leading-none">{{ $event->starts_at->format('d') }}</span>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <x-ui.badge tone="warning">Pending</x-ui.badge>
                                                    <span class="tc-label-eyebrow-tight text-amber-800">
                                                        {{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}
                                                    </span>
                                                </div>

                                                <div class="mt-3 text-sm font-semibold text-slate-950">
                                                    {{ $ticket ? $ticket->title : 'Meeting request' }}
                                                </div>

                                                <div class="mt-1 text-sm text-slate-600">
                                                    {{ $contact?->name ?: ($ticket?->requester_phone ?: 'Caller not captured') }}
                                                </div>

                                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    <span class="tc-pill-mini border border-amber-200 bg-white text-amber-700">
                                                        {{ $bookableProvider }}
                                                    </span>
                                                    @if($ticketLabel)
                                                        <span>Linked to ticket {{ $ticketLabel }}</span>
                                                    @endif
                                                </div>

                                                @if($ticket)
                                                    <a href="{{ route('app.tickets.show', $ticket) }}"
                                                        class="mt-3 inline-flex items-center gap-1 text-sm font-medium tc-accent-link">
                                                        Open related ticket
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                                            <form action="{{ route('app.calendar.dismiss', $event) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="tc-btn-ghost w-full sm:w-auto">Dismiss</button>
                                            </form>

                                            <form action="{{ route('app.calendar.confirm', $event) }}" method="POST">
                                                @csrf
                                                @if($googleConnected)
                                                    <input type="hidden" name="provider" value="google">
                                                @elseif($calendlyConnected)
                                                    <input type="hidden" name="provider" value="calendly">
                                                @else
                                                    <input type="hidden" name="provider" value="ics">
                                                @endif
                                                <button type="submit" class="tc-btn-primary w-full sm:w-auto">Confirm</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.panel>

                <x-ui.panel title="Upcoming meetings" description="Confirmed meetings, shown in chronological order.">
                    @if($upcoming->isEmpty())
                        <x-ui.empty-state
                            title="Nothing booked yet"
                            description="Once a request is confirmed, upcoming meetings will appear here with quick access back to the ticket."
                        />
                    @else
                        <div class="space-y-3">
                            @foreach($upcoming as $event)
                                @php
                                    $ticket = $event->supportCase;
                                    $contact = $event->contact ?? $ticket?->contact;
                                    $ticketLabel = $ticket?->case_number ?: $ticket?->id;
                                @endphp

                                <div class="tc-meta-card-strong border-slate-200 bg-slate-50/70 transition hover:border-slate-300 hover:bg-white">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="flex min-w-0 gap-5 sm:gap-6">
                                            <div class="tc-meta-icon-tile bg-slate-100 text-slate-700">
                                                <span class="tc-label-eyebrow-tight">{{ $event->starts_at->format('M') }}</span>
                                                <span class="text-lg font-semibold leading-none">{{ $event->starts_at->format('d') }}</span>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <x-ui.badge tone="info">{{ ucfirst($event->provider) }}</x-ui.badge>
                                                    <span class="tc-label-eyebrow-tight">
                                                        {{ $event->starts_at->format('g:i A') }} to {{ $event->ends_at->format('g:i A') }}
                                                    </span>
                                                </div>

                                                <div class="mt-3 text-sm font-semibold text-slate-950">
                                                    {{ $ticket ? $ticket->title : 'Meeting booking' }}
                                                </div>

                                                <div class="mt-1 text-sm text-slate-600">
                                                    {{ $contact?->name ?: 'Caller not captured' }}
                                                </div>

                                                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                    @if($ticketLabel)
                                                        <span>Linked to ticket {{ $ticketLabel }}</span>
                                                    @endif
                                                    @if($event->url)
                                                        <span class="tc-pill-mini border border-slate-200 bg-white text-slate-500">
                                                            Join link ready
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                                            @if($ticket)
                                                <a href="{{ route('app.tickets.show', $ticket) }}" class="tc-btn-ghost w-full sm:w-auto">Open ticket</a>
                                            @endif

                                            @if($event->url)
                                                <a href="{{ $event->url }}" target="_blank" rel="noopener noreferrer" class="tc-btn-secondary w-full sm:w-auto">
                                                    Open meeting
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.panel>
            </div>

            <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
                <x-ui.panel title="Calendar status" description="At-a-glance readiness before you rely on live scheduling.">
                    <div class="space-y-3">
                        <div class="tc-meta-card {{ $googleConnected ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50/70' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Google Calendar</div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        {{ $googleConnected ? 'Connected for direct booking.' : 'Not connected yet.' }}
                                    </div>
                                </div>
                                <x-ui.badge :tone="$googleConnected ? 'success' : 'slate'">{{ $googleConnected ? 'Live' : 'Off' }}</x-ui.badge>
                            </div>
                        </div>

                        <div class="tc-meta-card {{ $calendlyConnected ? 'border-emerald-200 bg-emerald-50/70' : 'border-slate-200 bg-slate-50/70' }}">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-950">Calendly handoff</div>
                                    <div class="mt-1 text-sm text-slate-600">
                                        {{ $calendlyConnected ? 'Saved and ready for booking handoff.' : 'No scheduling link saved.' }}
                                    </div>
                                </div>
                                <x-ui.badge :tone="$calendlyConnected ? 'success' : 'slate'">{{ $calendlyConnected ? 'Live' : 'Off' }}</x-ui.badge>
                            </div>
                        </div>

                        <div class="tc-meta-card-white">
                            <div class="tc-label-eyebrow-tight">This week</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $nextSevenDaysCount }}</div>
                            <div class="mt-1 text-sm text-slate-600">Booked meetings in the next 7 days.</div>
                        </div>
                    </div>
                </x-ui.panel>

                <x-ui.panel title="Best practice flow" description="A calmer CRM-style flow for handling bookings from phone calls.">
                    <ul class="space-y-3 text-sm leading-6 text-slate-600">
                        <li>Review pending requests first so the oldest callers do not wait behind newer ones.</li>
                        <li>Confirm times from the linked ticket so the meeting and issue stay connected.</li>
                        <li>Keep at least one booking provider live before routing phone calls into scheduling.</li>
                    </ul>
                </x-ui.panel>

                <x-ui.panel title="Next step" description="Quickest route to make this calendar more useful today.">
                    @if(! $googleConnected && ! $calendlyConnected)
                        <x-ui.empty-state
                            title="Connect a booking provider"
                            description="Calendar works best when the assistant can hand off directly into a live provider."
                            actionText="Open calendar settings"
                            :actionHref="route('app.calendar.settings')"
                        />
                    @elseif($pendingCount > 0)
                        <div class="tc-meta-card border-amber-200 bg-amber-50/70">
                            <div class="text-sm font-semibold text-slate-950">You have {{ $pendingCount }} request{{ $pendingCount === 1 ? '' : 's' }} waiting.</div>
                            <div class="mt-2 text-sm text-slate-600">Start with the oldest pending request above and confirm or dismiss it.</div>
                        </div>
                    @else
                        <div class="tc-meta-card border-emerald-200 bg-emerald-50/70">
                            <div class="text-sm font-semibold text-slate-950">Calendar is in a good state.</div>
                            <div class="mt-2 text-sm text-slate-600">There are no pending meeting requests waiting for review right now.</div>
                        </div>
                    @endif
                </x-ui.panel>
            </div>
        </div>
    </div>
@endsection
