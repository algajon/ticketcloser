@extends('layouts.saas')

@section('title', 'tickIt - Calendar Settings')

@section('header_eyebrow', 'Scheduling')
@section('header', 'Calendar settings')
@section('header_description', 'Choose where booked meetings should go.')

@section('content')
    <div class="mb-6">
        <a href="{{ route('app.calendar.index') }}"
            class="text-sm font-medium text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
            Back to calendar
        </a>
    </div>

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Connections</h1>
        <p class="text-sm text-slate-500 mt-1">Connect the calendar tools your assistant can use for booking.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-md" role="alert" aria-live="assertive">
            <div class="flex">
                <div class="shrink-0 rounded-full border border-green-200 bg-white px-2 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-green-700">
                    Saved
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700 font-medium">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md" role="alert" aria-live="assertive">
            <div class="flex">
                <div class="shrink-0 rounded-full border border-red-200 bg-white px-2 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-red-700">
                    Error
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid max-w-5xl gap-8 lg:grid-cols-2">
        <div class="tc-card overflow-hidden flex flex-col group transition-shadow hover:shadow-md h-full">
            <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/50 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="w-10 h-10 rounded bg-white flex items-center justify-center shrink-0 border border-slate-200 shadow-sm">
                        <span class="text-sm font-black tracking-tight text-emerald-700">G</span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 leading-tight">Google Calendar</h2>
                        <p class="text-sm text-slate-500">Direct sync</p>
                    </div>
                </div>
                @if(isset($connections['google']))
                    <span class="tc-badge-synced">
                        <span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block"></span>
                        Connected
                    </span>
                @endif
            </div>

            <div class="p-6 flex-grow flex flex-col justify-between h">
                <p class="text-sm text-slate-600 mb-6 leading-relaxed">
                    Send booked meetings straight to Google Calendar.
                </p>

                <a href="{{ route('app.calendar.google.auth') }}"
                    class="tc-btn-primary inline-flex w-full items-center gap-2 sm:w-fit"
                    aria-label="Sign in to connect Google Calendar">
                    {{ isset($connections['google']) ? 'Reconnect Google' : 'Connect Google' }}
                </a>
            </div>
        </div>

        <div class="tc-card overflow-hidden flex flex-col group transition-shadow hover:shadow-md h-full">
            <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/50 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="w-10 h-10 rounded bg-white flex items-center justify-center shrink-0 border border-slate-200 shadow-sm text-blue-600 font-black tracking-tighter text-lg">
                        C
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 leading-tight">Calendly</h2>
                        <p class="text-sm text-slate-500">Booking link</p>
                    </div>
                </div>
                @if(isset($connections['calendly']))
                    <span class="tc-badge-synced">
                        <span style="width:6px;height:6px;border-radius:50%;background:#10b981;display:inline-block"></span>
                        Configured
                    </span>
                @endif
            </div>

            <div class="flex-grow flex flex-col h-full">
                <form action="{{ route('app.calendar.calendly.save') }}" method="POST"
                    class="p-6 h-full flex flex-col justify-between" x-data="{ loads: false }" @submit="loads = true">
                    @csrf
                    <div>
                        <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                            Add a Calendly link if you want callers sent to your booking page.
                        </p>

                        <label for="calendly_link" class="block text-sm font-semibold text-slate-700 mb-1">Calendly link</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="url" name="calendly_link" id="calendly_link"
                                class="block w-full rounded-md border-0 py-2 pl-3 pr-3 text-slate-900 ring-1 ring-inset {{ $errors->has('calendly_link') ? 'ring-red-400 focus:ring-red-500' : 'ring-slate-300 focus:ring-indigo-600' }} sm:text-sm sm:leading-6 tc-input"
                                placeholder="https://calendly.com/your-name/30min"
                                value="{{ old('calendly_link', $connections['calendly']->calendly_scheduling_link ?? '') }}"
                                required>
                        </div>
                        @error('calendly_link')
                            <p class="text-xs text-red-600 mt-1 font-semibold" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="tc-btn-primary flex w-full items-center justify-center gap-1.5 sm:w-auto"
                            :disabled="loads" aria-label="Save Calendly Link">
                            <span x-show="loads" class="tc-spinner w-4 h-4" aria-hidden="true" style="display: none"></span>
                            <span x-text="loads ? 'Saving...' : 'Save link'"></span>
                        </button>
                        <noscript>
                            <button type="submit" class="tc-btn-primary">Save link</button>
                        </noscript>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
