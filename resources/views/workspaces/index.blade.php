@extends('layouts.saas')

@section('title', 'tickIt - Workspaces')
@section('header_eyebrow', 'Workspace')
@section('header', 'Workspaces')
@section('header_description', 'Switch between workspaces and open settings for each one.')

@section('header_actions')
    <a href="{{ route('app.workspaces.create') }}" class="tc-btn-primary">New workspace</a>
@endsection

@section('content')
    @if($workspaces->isEmpty())
        <x-ui.panel>
            <x-ui.empty-state title="No workspaces yet" description="Create your first workspace to get started." actionText="Create workspace" :actionHref="route('app.workspaces.create')" />
        </x-ui.panel>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach($workspaces as $workspace)
                <div class="tc-card-hover p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Workspace</div>
                            <h2 class="mt-2 truncate text-lg font-semibold text-slate-950">{{ $workspace->name }}</h2>
                            <p class="mt-2 text-sm text-slate-500">{{ $workspace->slug }}</p>
                        </div>
                        @if(auth()->user()->currentWorkspace()?->id === $workspace->id)
                            <x-ui.badge tone="success">Current</x-ui.badge>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-3 text-sm text-slate-600">
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Timezone</div>
                            <div class="mt-2 font-medium text-slate-900">{{ $workspace->default_timezone }}</div>
                        </div>
                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Ticket label</div>
                            <div class="mt-2 font-medium text-slate-900">{{ $workspace->case_label }}</div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                        <form method="POST" action="{{ route('app.workspaces.switch', $workspace) }}" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" class="tc-btn-primary w-full justify-center sm:w-auto">Switch</button>
                        </form>

                        <a href="{{ route('app.workspaces.settings', $workspace) }}" class="tc-btn-secondary w-full justify-center sm:w-auto">Settings</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
