@extends('layouts.saas')

@section('title')
Setup • Company
@endsection

@section('header')
Company
@endsection

@section('content')
<div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
    <form method="POST" action="{{ route('app.onboarding.company.save') }}" class="space-y-4">
        @csrf

        <div>
            <label class="text-sm font-medium">Company name</label>
            <input name="name" value="{{ old('name', $workspace->name) }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200">
            @error('name') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium">Workspace slug</label>
                <input name="slug" value="{{ old('slug', $workspace->slug) }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200">
                <div class="text-xs text-slate-500 mt-1">Used for integration header: X-Workspace-Slug</div>
                @error('slug') <div class="text-sm text-red-600 mt-1">{{ $message }}</div> @enderror
            </div>

            <div>
                <label class="text-sm font-medium">Ticket label</label>
                <input name="case_label" value="{{ old('case_label', $workspace->case_label) }}" class="mt-1 w-full rounded-xl border-slate-300 focus:border-slate-400 focus:ring-slate-200">
            </div>
        </div>

        <div>
            <label class="text-sm font-medium">Timezone</label>
            <select name="default_timezone" class="mt-1 w-full rounded-xl border-slate-300 focus:ring-slate-200">
                <option value="America/New_York" @selected(old('default_timezone', $workspace->default_timezone) === 'America/New_York')>America/New_York</option>
                <option value="America/Chicago" @selected(old('default_timezone', $workspace->default_timezone) === 'America/Chicago')>America/Chicago</option>
                <option value="America/Denver" @selected(old('default_timezone', $workspace->default_timezone) === 'America/Denver')>America/Denver</option>
                <option value="America/Los_Angeles" @selected(old('default_timezone', $workspace->default_timezone) === 'America/Los_Angeles')>America/Los_Angeles</option>
            </select>
        </div>

        <div class="flex justify-end">
            <button class="px-4 py-2 rounded-xl bg-slate-900 text-white hover:bg-slate-800">Continue</button>
        </div>
    </form>
</div>

@endsection
