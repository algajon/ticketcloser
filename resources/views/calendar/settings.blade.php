@extends('layouts.saas')

@section('title', 'ticketcloser • Calendar Settings')

@section('header', 'Calendar Settings')

@section('content')
    <div class="mb-6">
        <a href="{{ route('app.calendar.index') }}"
            class="text-sm font-medium text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Calendar
        </a>
    </div>

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Connections</h1>
        <p class="text-sm text-slate-500 mt-1">Configure which calendar platforms your voice assistants can use to schedule
            appointments.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-md" role="alert" aria-live="assertive">
            <div class="flex">
                <div class="shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                        fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                            clip-rule="evenodd" />
                    </svg>
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
                <div class="shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                        aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-medium">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8 max-w-5xl">
        {{-- Google Calendar Card --}}
        <div class="tc-card overflow-hidden flex flex-col group transition-shadow hover:shadow-md h-full">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded bg-white flex items-center justify-center shrink-0 border border-slate-200 shadow-sm">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#296b38ff">
                            <path
                                d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 15.907 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 leading-tight">Google Calendar</h2>
                        <p class="text-sm text-slate-500">Native Integration</p>
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
                    Connect your Google account to automatically insert recognized bookings directly into your primary
                    calendar in real-time.
                </p>

                <a href="{{ route('app.calendar.google.auth') }}"
                    class="tc-btn-primary w-fit inline-flex items-center gap-2"
                    aria-label="Sign in to connect Google Calendar">
                    <svg class="w-4 h-4 rounded-sm text-white p-0.5" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 15.907 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z" />
                    </svg>
                    {{ isset($connections['google']) ? 'Reconnect Google Account' : 'Connect with Google' }}
                </a>
            </div>
        </div>

        {{-- Calendly Card --}}
        <div class="tc-card overflow-hidden flex flex-col group transition-shadow hover:shadow-md h-full">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded bg-white flex items-center justify-center shrink-0 border border-slate-200 shadow-sm text-blue-600 font-black tracking-tighter text-lg">
                        C
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 leading-tight">Calendly</h2>
                        <p class="text-sm text-slate-500">Scheduling Link</p>
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
                            If you prefer to send a booking link after a voice interaction, paste your primary Calendly URL
                            below to embed it into the dashboard.
                        </p>

                        <label for="calendly_link" class="block text-sm font-semibold text-slate-700 mb-1">Scheduling Link
                            URL</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="url" name="calendly_link" id="calendly_link"
                                class="block w-full rounded-md border-0 py-2 pl-9 pr-3 text-slate-900 ring-1 ring-inset {{ $errors->has('calendly_link') ? 'ring-red-400 focus:ring-red-500' : 'ring-slate-300 focus:ring-indigo-600' }} sm:text-sm sm:leading-6 tc-input"
                                placeholder="https://calendly.com/your-name/30min"
                                value="{{ old('calendly_link', $connections['calendly']->calendly_scheduling_link ?? '') }}"
                                required>
                        </div>
                        @error('calendly_link')
                            <p class="text-xs text-red-600 mt-1 font-semibold" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="tc-btn-primary flex items-center justify-center gap-1.5"
                            :disabled="loads" aria-label="Save Calendly Link">
                            <span x-show="loads" class="tc-spinner w-4 h-4" aria-hidden="true" style="display: none"></span>
                            <span x-text="loads ? 'Saving...' : 'Save Settings'"></span>
                        </button>
                        <noscript>
                            <button type="submit" class="tc-btn-primary">Save Settings</button>
                        </noscript>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection