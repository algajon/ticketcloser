@extends('layouts.saas')

@section('title', 'tickIt - Contact')
@section('header_eyebrow', 'Contacts')
@section('header', $contact->name ?: 'Contact')
@section('header_description', 'Calls, tickets, and meetings tied to this person.')

@section('header_actions')
    <a href="{{ route('app.contacts.index', $workspace) }}" class="tc-btn-secondary">Back to contacts</a>
    <a href="{{ route('app.contacts.edit', [$workspace, $contact]) }}" class="tc-btn-ghost">Edit contact</a>
@endsection

@section('content')
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)] [&>*]:min-w-0">
        <div class="min-w-0 space-y-6">
            <x-ui.panel title="Recent tickets" description="The latest tickets linked to this contact.">
                @if($cases->isEmpty())
                    <x-ui.empty-state title="No tickets yet" description="Tickets tied to this contact will show up here." />
                @else
                    <div class="space-y-3">
                        @foreach($cases as $case)
                            <a href="{{ route('app.tickets.show', $case) }}" class="block min-w-0 rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4 transition hover:border-slate-300 hover:bg-white">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $case->case_number }}</div>
                                        <div class="mt-2 break-words text-sm font-semibold text-slate-950">{{ $case->title }}</div>
                                        <div class="mt-2 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($case->description, 140) }}</div>
                                    </div>
                                    <div class="shrink-0 text-xs uppercase tracking-[0.16em] text-slate-500">{{ $case->created_at->format('M j') }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-ui.panel>

            <x-ui.panel title="Meetings" description="Confirmed and pending bookings for this contact.">
                @if($calendarEvents->isEmpty() && $suggestedEvents->isEmpty())
                    <x-ui.empty-state title="No meetings yet" description="Bookings will show up here once follow-up is scheduled." />
                @else
                    <div class="space-y-3">
                        @foreach($calendarEvents as $event)
                            <div class="min-w-0 rounded-[1.25rem] border border-emerald-200 bg-emerald-50/70 p-4">
                                <div class="text-sm font-semibold text-emerald-900">Confirmed meeting</div>
                                <div class="mt-2 break-words text-xs uppercase tracking-[0.16em] text-emerald-700">{{ $event->starts_at?->format('M j, Y \a\t g:i A') }}</div>
                                @if($event->supportCase)
                                    <a href="{{ route('app.tickets.show', $event->supportCase) }}" class="mt-3 inline-flex text-sm font-medium text-emerald-800 transition hover:text-emerald-950">Open related ticket</a>
                                @endif
                            </div>
                        @endforeach

                        @foreach($suggestedEvents as $event)
                            <div class="min-w-0 rounded-[1.25rem] border border-amber-200 bg-amber-50/70 p-4">
                                <div class="text-sm font-semibold text-amber-900">Pending meeting</div>
                                <div class="mt-2 break-words text-xs uppercase tracking-[0.16em] text-amber-700">{{ $event->starts_at?->format('M j, Y \a\t g:i A') }}</div>
                                <a href="{{ route('app.calendar.index') }}" class="mt-3 inline-flex text-sm font-medium text-amber-800 transition hover:text-amber-950">Review in calendar</a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.panel>
        </div>

        <div class="min-w-0 space-y-6">
            <x-ui.panel title="Contact details" description="Saved details your assistants can reuse.">
                <div class="space-y-4">
                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Full name</div>
                        <div class="mt-2 break-words text-sm font-medium text-slate-900">{{ $contact->name ?? 'Unknown caller' }}</div>
                    </div>
                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Phone</div>
                        <div class="mt-2 break-all text-sm font-medium text-slate-900">{{ $contact->phone_e164 ?? 'Not provided' }}</div>
                    </div>
                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Email</div>
                        <div class="mt-2 break-all text-sm font-medium text-slate-900">{{ $contact->email ?? 'Not provided' }}</div>
                    </div>
                    @if($contact->property_code || $contact->unit)
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Location</div>
                            <div class="mt-2 break-words text-sm font-medium text-slate-900">{{ trim($contact->property_code . ' ' . $contact->unit) }}</div>
                        </div>
                    @endif
                </div>
            </x-ui.panel>

            <x-ui.panel title="Summary" description="Quick counts for this contact.">
                <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1 [&>*]:min-w-0">
                    <div class="min-w-0 rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Tickets</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $contact->cases_count }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Confirmed meetings</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $contact->calendar_events_count }}</div>
                    </div>
                    <div class="min-w-0 rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Pending meetings</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-950">{{ $contact->suggested_events_count }}</div>
                    </div>
                </div>
            </x-ui.panel>
        </div>
    </div>
@endsection
