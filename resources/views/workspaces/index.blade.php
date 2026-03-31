@extends('layouts.saas')

@section('title', 'ticketcloser • Workspaces')
@section('header', 'Workspaces')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Your workspaces</h2>
                <p class="mt-1 text-sm text-slate-500">Switch between companies or create a new workspace.</p>
            </div>
            <a href="{{ route('app.workspaces.create') }}"
                class="inline-flex items-center rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                New workspace
            </a>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($workspaces as $workspace)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">{{ $workspace->name }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $workspace->slug }}</p>
                        </div>
                        @if(auth()->user()->currentWorkspace()?->id === $workspace->id)
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Current</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-3 text-sm text-slate-600">
                        <p>Timezone: <span class="font-medium text-slate-900">{{ $workspace->default_timezone }}</span></p>
                        <p>Case label: <span class="font-medium text-slate-900">{{ $workspace->case_label }}</span></p>
                    </div>

                    <div class="mt-5 flex items-center gap-2">
                        <form method="POST" action="{{ route('app.workspaces.switch', $workspace) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                                Switch
                            </button>
                        </form>

                        <a href="{{ route('app.workspaces.settings', $workspace) }}"
                            class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Settings
                        </a>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-sm text-slate-500">
                    No workspaces yet. Create your first one to get started.
                </div>
            @endforelse
        </div>
    </div>
@endsection
