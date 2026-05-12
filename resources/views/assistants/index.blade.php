@extends('layouts.saas')

@section('title', 'tickIt - Assistants')
@section('header_eyebrow', 'Assistant configuration')
@section('header', 'Assistants')
@section('header_description', 'Manage the assistants that answer calls and create tickets for '.$workspace->name.'.')

@section('header_actions')
    <a href="{{ route('app.assistant.create', $workspace) }}" class="tc-btn-primary">New assistant</a>
@endsection

@section('content')
    @php
        $ws = $workspace;
        $assistants = $configs ?? collect();
        $phones = $phonesByAssistant ?? collect();
    @endphp

    @if($assistants->isEmpty())
        <x-ui.panel>
            <x-ui.empty-state title="No assistants yet" description="Create your first assistant to answer calls and create tickets." actionText="Create assistant" :actionHref="route('app.assistant.create', $ws)" />
        </x-ui.panel>
    @else
        <div class="grid gap-5 lg:grid-cols-3">
            @foreach($assistants as $assistant)
                @php
                    $synced = !empty($assistant->vapi_assistant_id);
                    $phone = $phones->get($assistant->id);
                    $modelDefinition = collect(\App\Models\AssistantConfig::modelOptions())
                        ->firstWhere('value', \App\Models\AssistantConfig::normalizedModelName($assistant->model_name));
                @endphp

                <div class="tc-assistant-card">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Assistant</div>
                            <h2 class="mt-2 truncate text-lg font-semibold text-slate-950">{{ $assistant->name }}</h2>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button type="button" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 transition hover:border-slate-300 hover:text-slate-700" @click="open = !open">
                                Actions
                            </button>

                            <div x-show="open" x-transition.origin.top.right @click.away="open = false" class="absolute right-0 top-[calc(100%+0.5rem)] z-20 w-40 rounded-[1rem] border border-slate-200 bg-white p-2 shadow-[0_22px_60px_-32px_rgba(15,23,42,0.38)]">
                                <form method="POST" action="{{ route('app.assistant.duplicate', [$ws, $assistant]) }}">
                                    @csrf
                                    <button type="submit" class="tc-shell-nav-link !w-full !rounded-[0.9rem] !px-3 !py-2.5 text-left">Duplicate</button>
                                </form>
                                <a href="{{ route('app.assistant.show', [$ws, $assistant]) }}" class="tc-shell-nav-link !rounded-[0.9rem] !px-3 !py-2.5">Edit</a>
                                <form method="POST" action="{{ route('app.assistant.destroy', [$ws, $assistant]) }}" onsubmit="return confirm('Delete this assistant?')" class="mt-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tc-shell-nav-link !w-full !rounded-[0.9rem] !px-3 !py-2.5 text-red-600 hover:!bg-red-50 hover:!text-red-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <x-ui.badge tone="{{ $synced ? 'success' : 'warning' }}">{{ $synced ? 'Synced to Vapi' : 'Pending sync' }}</x-ui.badge>
                        @if($phone?->e164)
                            <x-ui.badge tone="info">{{ $phone->e164 }}</x-ui.badge>
                        @else
                            <x-ui.badge tone="slate">No phone linked</x-ui.badge>
                        @endif
                    </div>

                    <div class="mt-5 space-y-4 text-sm text-slate-600">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">AI engine</div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="whitespace-nowrap font-medium text-slate-900">{{ $modelDefinition['label'] ?? 'Standard' }}</span>
                                <span class="whitespace-nowrap text-slate-400">/</span>
                                <span class="whitespace-nowrap">{{ $modelDefinition['headline'] ?? \App\Models\AssistantConfig::DEFAULT_MODEL }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $modelDefinition['qualityLabel'] ?? 'Good quality' }} / {{ $modelDefinition['costLabel'] ?? 'Balanced cost' }}</p>
                        </div>

                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Voice</div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="whitespace-nowrap">{{ $assistant->voice_provider ?? 'Default' }}</span>
                                <span class="whitespace-nowrap text-slate-400">/</span>
                                <span class="whitespace-nowrap">{{ $assistant->voice_id ?? 'Automatic' }}</span>
                                @if($assistant->language_code)
                                    <span class="whitespace-nowrap text-slate-400">/</span>
                                    <span class="whitespace-nowrap">{{ strtoupper($assistant->language_code) }}</span>
                                @endif
                            </div>
                        </div>

                        @if($assistant->fallback_phone)
                            <div>
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Human handoff</div>
                                <div class="mt-2 font-medium text-slate-900">{{ $assistant->fallback_phone }}</div>
                            </div>
                        @endif

                        <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50/80 p-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">System prompt</div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($assistant->system_prompt, 140) }}</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-2 border-t border-slate-200 pt-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                        <span class="text-slate-500">Updated {{ $assistant->updated_at->diffForHumans() }}</span>
                        <a href="{{ route('app.assistant.show', [$ws, $assistant]) }}" class="tc-accent-link font-semibold">Open</a>
                    </div>
                </div>
            @endforeach

            <a href="{{ route('app.assistant.create', $ws) }}" class="tc-accent-card-hover flex min-h-[280px] items-center justify-center rounded-[1.6rem] border border-dashed border-slate-300 bg-white/70 p-6 text-center transition">
                <div>
                    <div class="mt-4 text-base font-semibold text-slate-900">Add another assistant</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">Create another assistant for a different team, line, or tone.</p>
                </div>
            </a>
        </div>
    @endif
@endsection
