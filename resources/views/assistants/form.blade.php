@extends('layouts.saas')

@section('title')
    ticketcloser • {{ isset($config) ? 'Edit' : 'New' }} Assistant
@endsection

@section('header')
    {{ isset($config) ? 'Edit Assistant' : 'New Assistant' }}
@endsection

@section('content')
    @php
        $ws = $workspace;
        $editing = isset($config);
        $formAction = $editing
            ? route('app.assistant.update', [$ws, $config])
            : route('app.assistant.store', $ws);

        $voicesJson = collect($voices ?? [])->map(function ($v) {
            return [
                'id' => $v['voiceId'] ?? $v['id'] ?? '',
                'name' => $v['name'] ?? $v['voiceId'] ?? 'Unknown',
                'provider' => $v['provider'] ?? 'unknown',
                'language' => $v['language'] ?? $v['accent'] ?? '',
            ];
        })->values()->all();
    @endphp

    {{-- Back link --}}
    <div class="mb-6">
        <a href="{{ route('app.assistant.edit', $ws) }}"
            class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-800 transition-colors">
            ← Back to assistants
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Form (left, 2 cols) --}}
        <div class="lg:col-span-2">
            <form method="POST" action="{{ $formAction }}" class="space-y-6" x-data="{ saving: false }"
                @submit="saving = true">
                @csrf
                @if($editing)
                    <input type="hidden" name="assistant_id" value="{{ $config->id }}">
                @endif

                {{-- Name --}}
                <div class="tc-card p-4 sm:p-6">
                    <h2 class="text-base font-bold text-slate-900 mb-4">General</h2>
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label for="name" class="block text-sm font-medium text-slate-700">Assistant name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $config->name ?? '') }}"
                                class="tc-input" placeholder="e.g. Support Bot, Intake Agent" required />
                            <p class="text-xs text-slate-400">A friendly name to identify this assistant in your dashboard.
                            </p>
                        </div>
                        
                        <div class="space-y-1.5">
                            <label for="fallback_phone" class="block text-sm font-medium text-slate-700">Live Human Handoff (Transfer Phone Number)</label>
                            <input id="fallback_phone" type="text" name="fallback_phone" value="{{ old('fallback_phone', $config->fallback_phone ?? '') }}"
                                class="tc-input" placeholder="+12345678900" />
                            <p class="text-xs text-slate-400">Optional. The AI will transfer the call to this number if the caller strictly demands a human. Must be E.164 format.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Voice configuration --}}
                <div class="tc-card p-6" x-data="voicePicker()">
                    <h2 class="text-base font-bold text-slate-900 mb-1">Voice</h2>
                    <p class="text-xs text-slate-500 mb-4">Choose a Vapi voice for your assistant's phone calls.</p>

                    <input type="hidden" name="voice_provider" value="vapi">

                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label for="voice_id" class="block text-sm font-medium text-slate-700">Voice</label>
                            <select id="voice_id" name="voice_id" class="tc-input" x-model="selectedVoiceId">
                                <option value="">Select a voice…</option>
                                <template x-for="v in voices" :key="v.id">
                                    <option :value="v.id" :selected="v.id === selectedVoiceId" x-text="v.name"></option>
                                </template>
                            </select>
                        </div>

                        {{-- Selected voice preview --}}
                        <div x-show="selectedVoiceId" x-transition
                            class="flex items-center gap-3 bg-orange-50 rounded-xl px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-orange-900 truncate" x-text="selectedVoiceName"></p>
                                <p class="text-xs text-orange-500">Vapi voice</p>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    function voicePicker() {
                        const allVoices = @json($voicesJson);

                        return {
                            voices: allVoices,
                            selectedVoiceId: '{{ old("voice_id", $config->voice_id ?? "") }}',
                            get selectedVoiceName() {
                                const v = this.voices.find(v => v.id === this.selectedVoiceId);
                                return v ? v.name : '';
                            },
                            get selectedLanguage() {
                                const v = this.voices.find(v => v.id === this.selectedVoiceId);
                                return v ? v.language : '';
                            }
                        };
                    }
                </script>

                {{-- Presets --}}
                <div class="tc-card p-6"
                    x-data="{ preset: '{{ old('preset_key', $config->preset_key ?? 'customer_support') }}', showAdvanced: false }">
                    <h2 class="text-base font-bold text-slate-900 mb-1">Behavior Preset</h2>
                    <p class="text-xs text-slate-500 mb-4">Select a timing profile that dictates how the assistant interacts
                        with callers.</p>

                    <div class="grid sm:grid-cols-3 gap-3 mb-4">
                        @foreach($presets as $p)
                            <label
                                class="relative flex cursor-pointer rounded-lg border bg-white p-4 items-center justify-between shadow-sm focus-within:ring-2 focus-within:ring-slate-600"
                                :class="{ 'border-slate-800 ring-1 ring-slate-800': preset === '{{ $p->key }}', 'border-slate-200': preset !== '{{ $p->key }}' }">
                                <input type="radio" name="preset_key" value="{{ $p->key }}" class="sr-only" x-model="preset">
                                <span class="flex flex-col">
                                    <span class="block text-sm font-medium text-slate-900">{{ $p->name }}</span>
                                    <span class="mt-1 flex items-center text-xs text-slate-500">{{ $p->notes }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{-- Advanced Timing Sliders --}}
                    <div>
                        <button type="button" @click="showAdvanced = !showAdvanced"
                            class="text-sm font-medium text-slate-600 hover:text-slate-900 flex items-center gap-1">
                            <svg class="w-4 h-4 transition-transform" :class="showAdvanced ? 'rotate-180' : ''" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                            Advanced Timing Settings
                        </button>

                        <div x-show="showAdvanced" x-transition class="mt-4 space-y-5 pt-4 border-t border-slate-100">
                            @php
                                $overrides = old('override_params', $config->override_params ?? []);
                            @endphp

                            <div class="space-y-1.5" x-data="{ val: '{{ $overrides['waitSeconds'] ?? 0.5 }}' }">
                                <div class="flex justify-between items-center">
                                    <label for="waitSeconds" class="block text-sm font-medium text-slate-700">Wait Seconds
                                        (Start Speaking)</label>
                                    <span class="text-xs text-slate-500 font-mono" x-text="val + 's'"></span>
                                </div>
                                <input type="range" name="override_params[waitSeconds]" id="waitSeconds" min="0.1" max="3.0"
                                    step="0.1" x-model="val" class="w-full accent-slate-800 cursor-pointer">
                                <p class="text-xs text-slate-400">Delay before the assistant starts speaking after the user
                                    pauses.</p>
                            </div>

                            <div class="space-y-1.5" x-data="{ val: '{{ $overrides['numWords'] ?? 2 }}' }">
                                <div class="flex justify-between items-center">
                                    <label for="numWords" class="block text-sm font-medium text-slate-700">Interruption
                                        Words</label>
                                    <span class="text-xs text-slate-500 font-mono" x-text="val + ' words'"></span>
                                </div>
                                <input type="range" name="override_params[numWords]" id="numWords" min="1" max="5" step="1"
                                    x-model="val" class="w-full accent-slate-800 cursor-pointer">
                                <p class="text-xs text-slate-400">Number of words the user must speak to interrupt the
                                    assistant.</p>
                            </div>

                            <div class="space-y-1.5" x-data="{ val: '{{ $overrides['backoffSeconds'] ?? 1.0 }}' }">
                                <div class="flex justify-between items-center">
                                    <label for="backoffSeconds" class="block text-sm font-medium text-slate-700">Backoff
                                        Seconds</label>
                                    <span class="text-xs text-slate-500 font-mono" x-text="val + 's'"></span>
                                </div>
                                <input type="range" name="override_params[backoffSeconds]" id="backoffSeconds" min="0.5"
                                    max="3.0" step="0.1" x-model="val" class="w-full accent-slate-800 cursor-pointer">
                                <p class="text-xs text-slate-400">Time to wait after an interruption before resuming.</p>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- System prompt --}}
                <div class="tc-card p-6">
                    <h2 class="text-base font-bold text-slate-900 mb-1">System prompt</h2>
                    <p class="text-xs text-slate-500 mb-4">Instructions for how the AI assistant behaves during calls.</p>
                    <textarea id="system_prompt" name="system_prompt" rows="8" class="tc-input"
                        style="resize:vertical;min-height:120px"
                        placeholder="You are a friendly support agent for [company]. Your job is to collect details about the caller's issue and create a support ticket...">{{ old('system_prompt', $config->system_prompt ?? '') }}</textarea>
                </div>

                {{-- Submit --}}
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('app.assistant.edit', $ws) }}" class="tc-btn-ghost">Cancel</a>
                    <button type="submit" class="tc-btn-primary" :disabled="saving">
                        <template x-if="saving">
                            <span class="tc-spinner" style="width:14px;height:14px"></span>
                        </template>
                        <span
                            x-text="saving ? 'Saving & syncing…' : '{{ $editing ? 'Save & sync' : 'Create & sync' }}'"></span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Preview card (right, 1 col) --}}
        <div class="hidden lg:block">
            <div class="sticky top-24">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Preview</p>
                <div class="tc-assistant-card" x-data="{ name: '{{ $config->name ?? '' }}' }">
                    <div class="flex items-center gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900"
                                x-text="document.getElementById('name')?.value || 'New Assistant'"
                                x-init="$watch('$el', () => {}); document.getElementById('name')?.addEventListener('input', e => $el.textContent = e.target.value || 'New Assistant')">
                                {{ $config->name ?? 'New Assistant' }}
                            </h3>
                            @if($editing && !empty($config->vapi_assistant_id))
                                <span class="tc-badge-synced mt-1">
                                    <span
                                        style="width:5px;height:5px;border-radius:50%;background:#10b981;display:inline-block"></span>
                                    Synced
                                </span>
                            @else
                                <span class="tc-badge-pending mt-1">
                                    <span
                                        style="width:5px;height:5px;border-radius:50%;background:#f59e0b;display:inline-block"></span>
                                    Will sync on save
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2 text-sm text-slate-500">
                        <div class="flex items-center gap-2" x-show="selectedVoiceId">
                            <span x-text="selectedVoiceName + ' · ' + selectedVoiceId"></span>
                        </div>
                    </div>

                    @if($editing && $config->vapi_assistant_id)
                        <div class="mt-4 pt-3 border-t border-slate-100">
                            <p class="text-xs text-slate-400">Vapi ID:
                                <code
                                    class="font-mono text-xs bg-slate-50 px-1.5 py-0.5 rounded">{{ Str::limit($config->vapi_assistant_id, 20) }}</code>
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection