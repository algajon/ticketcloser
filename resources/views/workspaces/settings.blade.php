@extends('layouts.saas')

@section('title', 'ticketcloser • Workspace Settings')
@section('header', 'Workspace Settings')

@section('content')
    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-900">{{ $workspace->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">Update the display name, timezone, and case label for this workspace.</p>
        </div>

        <form method="POST" action="{{ route('app.workspaces.settings.update', $workspace) }}" class="space-y-4">
            @csrf

            <div>
                <label for="workspace-name" class="mb-1 block text-sm font-medium text-slate-700">Workspace name</label>
                <input id="workspace-name" name="name" type="text" value="{{ old('name', $workspace->name) }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200" />
                @if($errors->first('name'))
                    <p class="mt-1 text-sm text-red-600">{{ $errors->first('name') }}</p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="default_timezone" class="mb-1 block text-sm font-medium text-slate-700">Default timezone</label>
                    <input id="default_timezone" name="default_timezone" type="text"
                        value="{{ old('default_timezone', $workspace->default_timezone) }}" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200" />
                    @if($errors->first('default_timezone'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('default_timezone') }}</p>
                    @endif
                </div>

                <div>
                    <label for="case_label" class="mb-1 block text-sm font-medium text-slate-700">Case label</label>
                    <input id="case_label" name="case_label" type="text" value="{{ old('case_label', $workspace->case_label) }}" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200" />
                    @if($errors->first('case_label'))
                        <p class="mt-1 text-sm text-red-600">{{ $errors->first('case_label') }}</p>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                    Save settings
                </button>
                <a href="{{ route('app.workspaces.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Back
                </a>
            </div>
        </form>
    </div>
@endsection
