@extends('layouts.saas')

@section('title', 'tickIt - Contacts')
@section('header_eyebrow', 'Contacts')
@section('header', 'Contacts')
@section('header_description', 'People captured from calls in '.$workspace->name.'.')

@section('content')
    <div class="space-y-4">
        <div class="tc-card min-w-0 p-4 sm:p-6">
            <form method="GET" class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                <div class="min-w-0">
                    <div class="mb-3 text-sm font-semibold text-slate-900">Filters</div>
                    <div class="space-y-1.5">
                        <label for="search-q" class="sr-only">Search contacts</label>
                        <input id="search-q" name="q" value="{{ $q }}" placeholder="Name, phone, email, or property..."
                            class="tc-input" />
                    </div>
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center lg:justify-end">
                    <button type="submit" class="tc-btn-primary w-full whitespace-nowrap sm:w-auto">Apply filters</button>
                    <a href="{{ route('app.contacts.index', $workspace) }}" class="tc-btn-ghost w-full justify-center whitespace-nowrap sm:w-auto">Reset</a>
                </div>
            </form>
        </div>

        <div class="tc-card min-w-0">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-4">
                <span class="tc-h3">{{ $contacts->total() }} {{ Str::plural('Contact', $contacts->total()) }}</span>
                <span class="tc-small">Newest first</span>
            </div>

            @if($contacts->count() === 0)
                <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                    <h3 class="tc-h3 text-slate-700">No contacts yet</h3>
                    <p class="mt-1.5 text-sm text-muted max-w-sm">Contacts will appear as callers come in.</p>
                </div>
            @else
                <div class="min-w-0 divide-y divide-slate-100">
                    @foreach($contacts as $contact)
                        <div class="group relative block min-w-0 px-4 py-3 transition-colors hover:bg-slate-50 sm:px-6 sm:py-4">
                            <a href="{{ route('app.contacts.show', [$workspace, $contact]) }}" class="absolute inset-0 z-0" aria-label="Open {{ $contact->name ?? 'contact' }}"></a>
                            <div class="relative z-10 pointer-events-none flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="break-words text-sm font-semibold text-slate-900">{{ $contact->name ?? 'Unknown Caller' }}</span>
                                        @if($contact->cases_count > 0)
                                            <span class="tc-pill-mini bg-indigo-50 text-indigo-700">{{ $contact->cases_count }} {{ Str::plural('Ticket', $contact->cases_count) }}</span>
                                        @endif
                                        @if($contact->calendar_events_count > 0)
                                            <span class="tc-pill-mini bg-emerald-50 text-emerald-700">{{ $contact->calendar_events_count }} {{ Str::plural('Meeting', $contact->calendar_events_count) }}</span>
                                        @endif
                                    </div>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                                        <span class="flex min-w-0 flex-wrap items-center gap-1.5">
                                            <span class="tc-label-eyebrow-tight">Phone</span>
                                            <span class="break-all">{{ $contact->phone_e164 ?? 'No phone' }}</span>
                                        </span>
                                        @if($contact->email)
                                            <span class="flex min-w-0 flex-wrap items-center gap-1.5">
                                                <span class="tc-label-eyebrow-tight">Email</span>
                                                <span class="break-all">{{ $contact->email }}</span>
                                            </span>
                                        @endif
                                        @if($contact->property_code || $contact->unit)
                                            <span class="flex min-w-0 flex-wrap items-center gap-1.5">
                                                <span class="tc-label-eyebrow-tight">Location</span>
                                                <span class="break-words">{{ trim($contact->property_code . ' ' . $contact->unit) }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-1 min-w-0 flex flex-col gap-2 sm:hidden">
                                    <span class="tc-small">Added {{ $contact->created_at->format('M j, Y') }}</span>
                                    <div class="flex min-w-0 flex-col gap-2">
                                        <a href="{{ route('app.contacts.show', [$workspace, $contact]) }}" class="pointer-events-auto tc-btn-compact w-full">
                                            View contact
                                        </a>
                                        <a href="{{ route('app.contacts.edit', [$workspace, $contact]) }}" class="pointer-events-auto tc-btn-compact relative z-20 w-full">
                                            Edit
                                        </a>
                                    </div>
                                </div>
                                <div class="tc-small hidden shrink-0 flex-col items-end gap-2 sm:flex">
                                    <span>Added {{ $contact->created_at->format('M j, Y') }}</span>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('app.contacts.show', [$workspace, $contact]) }}" class="pointer-events-auto inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded hover:bg-slate-50 transition-colors shadow-sm">
                                            View contact
                                        </a>
                                        <a href="{{ route('app.contacts.edit', [$workspace, $contact]) }}" class="pointer-events-auto relative z-20 inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded hover:bg-slate-50 transition-colors shadow-sm">
                                            Edit contact
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($contacts->hasPages())
                    <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-slate-200">
                        {{ $contacts->withQueryString()->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection
