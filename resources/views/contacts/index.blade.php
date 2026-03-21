@extends('layouts.saas')

@section('title')
    ticketcloser • Contacts
@endsection

@section('header')
    Contacts
@endsection

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div class="tc-page-header mb-0">
            <h1>Contacts</h1>
            <p>All prospect and client records for {{ $workspace->name }}.</p>
        </div>
    </div>

    <div class="tc-card p-4 sm:p-6 mb-4">
        <form method="GET" class="grid md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
                <div class="space-y-1.5">
                    <label for="search-q" class="block text-sm font-medium text-slate-800">Search</label>
                    <input id="search-q" name="q" value="{{ $q }}" placeholder="Name, phone, email, or property…"
                        class="tc-input" />
                </div>
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="tc-btn-primary">Apply filters</button>
            </div>
        </form>
    </div>

    <div class="tc-card">
        <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-200 flex items-center justify-between">
            <span class="tc-h3">{{ $contacts->total() }} {{ Str::plural('Contact', $contacts->total()) }}</span>
            <span class="tc-small">Newest first</span>
        </div>

        @if($contacts->count() === 0)
            <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                <h3 class="tc-h3 text-slate-700">No contacts found</h3>
                <p class="mt-1.5 text-sm text-muted max-w-sm">No clients or prospects discovered yet.</p>
            </div>
        @else
            <div class="divide-y divide-slate-100">
                @foreach($contacts as $contact)
                    <div class="block px-4 sm:px-6 py-3 sm:py-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900 truncate">{{ $contact->name ?? 'Unknown Caller' }}</span>
                                    @if($contact->cases_count > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-indigo-50 text-indigo-700 uppercase tracking-widest">{{ $contact->cases_count }} {{ Str::plural('Case', $contact->cases_count) }}</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        {{ $contact->phone_e164 ?? 'No phone' }}
                                    </span>
                                    @if($contact->email)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            {{ $contact->email }}
                                        </span>
                                    @endif
                                    @if($contact->property_code || $contact->unit)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                            {{ trim($contact->property_code . ' ' . $contact->unit) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="tc-small whitespace-nowrap shrink-0 hidden sm:flex flex-col items-end gap-2">
                                <span>Added {{ $contact->created_at->format('M j, Y') }}</span>
                                <a href="{{ route('app.contacts.edit', [$workspace, $contact]) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold bg-white border border-slate-200 text-slate-700 rounded hover:bg-slate-50 transition-colors shadow-sm">
                                    Edit Contact
                                </a>
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
@endsection
