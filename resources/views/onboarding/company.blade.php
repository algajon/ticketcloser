@extends('layouts.guest')
@section('title', 'ticketcloser - Workspace')

@section('content')
<div class="mb-8 text-center">
    <h1 class="text-2xl font-bold text-white tracking-tight">Create workspace</h1>
    <p class="text-[14px] text-slate-400 mt-2">Let's set up your ticketcloser workspace.</p>
</div>

<form method="POST" action="{{ route('app.onboarding.company.save') }}" class="space-y-4">
    @csrf

    <div class="space-y-1.5 text-left">
        <label for="name" class="block text-[13px] font-medium text-slate-300">Workspace name</label>
        <input id="name" name="name" value="{{ old('name', $workspace->name) }}" placeholder="Acme Support"
            class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]"
            autocomplete="organization" autofocus />
        @error('name') <p class="text-[13px] text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-1.5 text-left">
        <label for="slug" class="block text-[13px] font-medium text-slate-300">Workspace URL</label>
        <div
            class="flex rounded-lg border border-white/10 bg-[#0b0f19]/50 overflow-hidden focus-within:border-[#f97316] focus-within:ring-1 focus-within:ring-[#f97316] transition-all">
            <span
                class="px-3 py-2 text-[14px] border-r border-white/10 select-none whitespace-nowrap text-slate-500 bg-black/20">
                ticketcloser/
            </span>
            <input id="slug" name="slug" value="{{ old('slug', $workspace->slug) }}" placeholder="acme"
                class="flex-1 w-full border-0 bg-transparent focus:ring-0 text-[14px] text-white px-3 py-2 placeholder-slate-500 outline-none"
                autocapitalize="none" autocomplete="off" spellcheck="false">
        </div>
        @error('slug') <p class="text-[13px] text-red-500 mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-4 text-left">
        <div class="space-y-1.5">
            <label for="case_label" class="block text-[13px] font-medium text-slate-300">Label</label>
            @php($label = old('case_label', $workspace->case_label ?? 'Ticket'))
            <select id="case_label" name="case_label"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]">
                <option value="Ticket" @selected($label === 'Ticket') class="bg-[#0b0f19]">Ticket</option>
                <option value="Case" @selected($label === 'Case') class="bg-[#0b0f19]">Case</option>
                <option value="Request" @selected($label === 'Request') class="bg-[#0b0f19]">Request</option>
            </select>
        </div>

        <div class="space-y-1.5">
            <label for="default_timezone" class="block text-[13px] font-medium text-slate-300">Timezone</label>
            @php($tz = old('default_timezone', $workspace->default_timezone ?? 'America/New_York'))
            <select id="default_timezone" name="default_timezone"
                class="w-full bg-[#0b0f19]/50 border border-white/10 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-[#f97316] focus:ring-1 focus:ring-[#f97316] transition-all text-[14px]">
                <option value="America/New_York" @selected($tz === 'America/New_York') class="bg-[#0b0f19]">Eastern
                </option>
                <option value="America/Chicago" @selected($tz === 'America/Chicago') class="bg-[#0b0f19]">Central</option>
                <option value="America/Denver" @selected($tz === 'America/Denver') class="bg-[#0b0f19]">Mountain</option>
                <option value="America/Los_Angeles" @selected($tz === 'America/Los_Angeles') class="bg-[#0b0f19]">Pacific
                </option>
            </select>
        </div>
    </div>

    <button type="submit"
        class="w-full mt-6 px-4 py-2.5 bg-[#f97316] hover:bg-[#ea580c] text-white rounded-lg font-medium text-[14px] transition-all"
        style="box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);">
        Continue &rarr;
    </button>
</form>
@endsection