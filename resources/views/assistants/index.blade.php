@extends('layouts.saas')

@section('title')
    ticketcloser • Assistants
@endsection

@section('header')
    Assistants
@endsection

@section('content')
    @php
        $ws = $workspace;
        $assistants = \App\Models\AssistantConfig::where('workspace_id', $ws->id)->get();
        $phones = \App\Models\WorkspacePhoneNumber::where('workspace_id', $ws->id)->get()->keyBy('assistant_id');
    @endphp

    {{-- Page header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-8">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Assistants</h1>
            <p class="text-sm text-slate-500 mt-1">Configure AI voice assistants that handle incoming calls and create
                tickets.</p>
        </div>
        <a href="{{ route('app.assistant.create', $ws) }}" class="tc-btn-primary">
            + New assistant
        </a>
    </div>

    @if($assistants->isEmpty())
        {{-- Empty state --}}
        <div class="tc-card flex flex-col items-center justify-center py-20 text-center">
            <h3 class="text-lg font-bold text-slate-900 mb-1">No assistants yet</h3>
            <p class="text-sm text-slate-500 max-w-sm mb-6">Create your first AI voice assistant to start receiving support
                calls and generating tickets automatically.</p>
            <a href="{{ route('app.assistant.create', $ws) }}" class="tc-btn-primary">
                + Create assistant
            </a>
        </div>
    @else
        {{-- Assistant card grid --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($assistants as $assistant)
                @php
                    $synced = !empty($assistant->vapi_assistant_id);
                    $phone = $phones->get($assistant->id);
                @endphp
                <div class="tc-assistant-card">
                    {{-- Header --}}
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 truncate max-w-[160px]">{{ $assistant->name }}</h3>
                                @if($synced)
                                    <span class="tc-badge-synced mt-1">
                                        <span
                                            style="width:5px;height:5px;border-radius:50%;background:#10b981;display:inline-block"></span>
                                        Synced
                                    </span>
                                @else
                                    <span class="tc-badge-pending mt-1">
                                        <span
                                            style="width:5px;height:5px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                                        Not synced
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Actions dropdown --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors text-sm">⋮</button>
                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute right-0 mt-1 w-36 rounded-xl bg-white shadow-lg border border-slate-200 py-1 z-30 text-sm">
                                <a href="{{ route('app.assistant.show', [$ws, $assistant]) }}"
                                    class="block px-3 py-2 hover:bg-slate-50 text-slate-700">
                                    <span class="flex items-center gap-2">
                                        <span class="w-3.5 h-3.5 rounded bg-slate-200 shrink-0"></span>
                                        Edit
                                    </span>
                                </a>
                                <form method="POST" action="{{ route('app.assistant.destroy', [$ws, $assistant]) }}"
                                    onsubmit="return confirm('Delete this assistant?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-full text-left px-3 py-2 hover:bg-red-50 text-red-600">
                                        <span class="flex items-center gap-2">
                                            <span class="w-3.5 h-3.5 rounded bg-red-200 shrink-0"></span>
                                            Delete
                                        </span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Info --}}
                    <div class="space-y-2.5 text-sm">
                        @if($assistant->voice_provider || $assistant->voice_id)
                            <div class="flex items-center gap-2 text-slate-600">
                                <span class="truncate">{{ $assistant->voice_provider ?? 'Default' }} ·
                                    {{ $assistant->voice_id ?? 'auto' }}</span>
                            </div>
                        @endif

                        @if($phone)
                            <div class="flex items-center gap-2 text-slate-600">
                                <span class="font-mono text-xs">{{ $phone->e164 ?? 'Not provisioned' }}</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-slate-400">
                                <span class="text-xs italic">No phone linked</span>
                            </div>
                        @endif

                        @if($assistant->system_prompt)
                            <div class="pt-2 border-t border-slate-100">
                                <p class="text-xs text-slate-500 line-clamp-2">{{ Str::limit($assistant->system_prompt, 100) }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400">Updated {{ $assistant->updated_at->diffForHumans() }}</span>
                        <a href="{{ route('app.assistant.show', [$ws, $assistant]) }}"
                            class="text-xs font-medium text-orange-600 hover:text-orange-800 transition-colors">
                            Edit →
                        </a>
                    </div>
                </div>
            @endforeach

            {{-- Add new card --}}
            <a href="{{ route('app.assistant.create', $ws) }}"
                class="flex flex-col items-center justify-center min-h-[200px] rounded-2xl border-2 border-dashed border-slate-200 hover:border-orange-300 hover:bg-orange-50/30 transition-all duration-200 group">
                <div
                    class="w-12 h-12 rounded-full bg-slate-100 group-hover:bg-orange-100 flex items-center justify-center transition-colors mb-3 text-xl text-slate-400 group-hover:text-orange-500">
                    +</div>
                <span class="text-sm font-medium text-slate-500 group-hover:text-orange-600 transition-colors">Add
                    assistant</span>
            </a>
        </div>
    @endif
@endsection