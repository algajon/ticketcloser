@extends('layouts.saas')

@section('title', 'tickIt - '.(isset($config) ? 'Edit Assistant' : 'New Assistant'))
@section('header_eyebrow', 'Assistant configuration')
@section('header', isset($config) ? 'Edit assistant' : 'New assistant')
@section('header_description', 'Choose how your assistant sounds, responds, and handles calls.')

@section('header_actions')
    <a href="{{ route('app.assistant.edit', $workspace) }}" class="tc-btn-secondary">Back to assistants</a>
@endsection

@section('content')
    @php
        $ws = $workspace;
        $editing = isset($config);
        $defaultAssistantDraft = ! $editing ? ($defaultAssistantDraft ?? null) : null;
        $formAction = $editing ? route('app.assistant.update', [$ws, $config]) : route('app.assistant.store', $ws);
        $workspaceIsFree = $workspace->isFreePlan() && ! $workspace->bypassesPlanLimits();
        $showDeveloperAssistantIds = ! $workspaceIsFree;
        $overrides = old('override_params', $config->override_params ?? []);
        $defaultAssistantName = (string) data_get($defaultAssistantDraft, 'assistant_name', '');
        $defaultPromptText = (string) data_get($defaultAssistantDraft, 'prompt', '');
        $defaultPresetKey = \App\Models\AssistantPreset::normalizeKey(data_get($defaultAssistantDraft, 'preset_key', 'bright_guide'));
        $selectedPresetKey = old('preset_key', \App\Models\AssistantPreset::normalizeKey($config->preset_key ?? $defaultPresetKey));
        $presetMeta = collect($presets)->map(function ($preset) {
            $waitSeconds = (float) data_get($preset->vapi_payload_json, 'startSpeakingPlan.waitSeconds', 0.5);
            $interruptWords = (int) data_get($preset->vapi_payload_json, 'stopSpeakingPlan.numWords', 2);
            $backoffSeconds = (float) data_get($preset->vapi_payload_json, 'stopSpeakingPlan.backoffSeconds', 1.0);
            $voiceSpeed = (float) data_get($preset->vapi_payload_json, 'voiceSpeed', 1.0);
            $simpleNotes = match ($preset->key) {
                'bright_guide' => 'Friendly and upbeat. Keeps the call easy and warm.',
                'steady_operator' => 'Calm and steady. Good when callers need a clear guide.',
                'confident_closer' => 'Direct and focused. Great when you want quick next steps.',
                'premium_concierge' => 'Polished and thoughtful. Best for a high-touch call experience.',
                default => 'Use your own style and timing.',
            };

            return [
                'key' => $preset->key,
                'name' => $preset->name,
                'notes' => $preset->notes,
                'simpleNotes' => $simpleNotes,
                'assistantType' => (string) data_get($preset->vapi_payload_json, 'assistantType', 'bright_guide'),
                'fit' => (string) data_get($preset->vapi_payload_json, 'fitLabel', 'General'),
                'toneLabel' => (string) data_get($preset->vapi_payload_json, 'toneLabel', 'Balanced'),
                'paceLabel' => (string) data_get($preset->vapi_payload_json, 'paceLabel', 'Balanced'),
                'voiceProfileLabel' => (string) data_get($preset->vapi_payload_json, 'voiceProfileLabel', 'Clear'),
                'responseStyleLabel' => (string) data_get($preset->vapi_payload_json, 'responseStyleLabel', 'General'),
                'recommendedFor' => (string) data_get($preset->vapi_payload_json, 'recommendedFor', 'Businesses that want a smooth, natural phone agent with strong follow-through.'),
                'waitSeconds' => $waitSeconds,
                'numWords' => $interruptWords,
                'backoffSeconds' => $backoffSeconds,
                'voiceSpeed' => $voiceSpeed,
                'waitLabel' => $waitSeconds <= 0.3 ? 'Very fast' : ($waitSeconds <= 0.6 ? 'Quick' : 'Calm'),
                'interruptLabel' => $interruptWords >= 3 ? 'Lets people finish' : ($interruptWords === 2 ? 'Balanced' : 'Cuts in fast'),
                'speedLabel' => $voiceSpeed >= 1.08 ? 'Bright' : ($voiceSpeed >= 1.0 ? 'Natural' : 'Slow'),
            ];
        })->values();
        $selectedPresetMeta = $presetMeta->firstWhere('key', $selectedPresetKey) ?? $presetMeta->first();
        $defaultWaitSeconds = (string) ($overrides['waitSeconds'] ?? ($selectedPresetMeta['waitSeconds'] ?? 0.5));
        $defaultNumWords = (string) ($overrides['numWords'] ?? ($selectedPresetMeta['numWords'] ?? 2));
        $defaultBackoffSeconds = (string) ($overrides['backoffSeconds'] ?? ($selectedPresetMeta['backoffSeconds'] ?? 1.0));
        $aiWriterAvailable = filled(config('services.openai.api_key'));
        $modelOptions = collect(\App\Models\AssistantConfig::modelOptions())
            ->map(function (array $option) use ($workspaceIsFree) {
                $option['locked'] = $workspaceIsFree && $option['value'] !== \App\Models\AssistantConfig::DEFAULT_MODEL;

                return $option;
            })
            ->values()
            ->all();
        $selectedModelName = $workspaceIsFree
            ? \App\Models\AssistantConfig::DEFAULT_MODEL
            : \App\Models\AssistantConfig::normalizedModelName(old('model_name', $config->model_name ?? null));
        $presetMapJson = $presetMeta->keyBy('key')->all();
        $editingAssistantId = $editing ? $config->id : null;
        $workflowDraftJson = $defaultAssistantDraft ? [
            'label' => $defaultAssistantDraft['use_case_label'],
            'description' => $defaultAssistantDraft['description'],
            'summary' => $defaultAssistantDraft['workflow_summary'] ?? $defaultAssistantDraft['description'],
            'requiredFields' => $defaultAssistantDraft['required_fields'],
            'callFlow' => $defaultAssistantDraft['call_flow'],
            'commonCalls' => $defaultAssistantDraft['common_calls'] ?? [],
            'opsOutcomes' => $defaultAssistantDraft['ops_outcomes'] ?? [],
            'emergencyExamples' => $defaultAssistantDraft['emergency_examples'] ?? [],
        ] : null;
        $assistantNameValue = old('name', $config->name ?? $defaultAssistantName);
        $firstMessageValue = old('first_message', $config->first_message ?? data_get($defaultAssistantDraft, 'first_message', ''));
        $fallbackPhoneValue = old('fallback_phone', $config->fallback_phone ?? '');
        $promptTextValue = old('system_prompt', $config->system_prompt ?? $defaultPromptText);
        $selectedLanguageValue = old('language_code', $config->language_code ?? data_get($defaultAssistantDraft, 'language_code', 'en-US'));
        $freeWorkspaceDefaultVoice = \App\Support\RegionalPilotStackCatalog::standardVoiceProfile(
            $selectedLanguageValue,
            $selectedPresetKey,
            $workspace->primaryMarket()
        );
        $selectedProviderValue = old(
            'voice_provider',
            $config->voice_provider ?? ($freeWorkspaceDefaultVoice['provider'] ?? 'vapi')
        );
        $selectedVoiceValue = old('voice_id', $config->voice_id ?? '');
        $languageOptionsJson = \App\Support\RegionalPilotStackCatalog::languageOptions($workspace->primaryMarket());
        $pilotStack = \App\Support\RegionalPilotStackCatalog::forWorkspace($workspace, $selectedLanguageValue);
        $pilotPlaybook = data_get($defaultAssistantDraft, 'regional_playbook')
            ?? \App\Support\RegionalPilotStackCatalog::forWorkspacePlaybook($workspace, $selectedLanguageValue);
        $showAdvancedTiming = !empty($overrides);
        $promptWriterMode = $aiWriterAvailable ? 'ai' : 'template';
        $selectedAssistantType = $selectedPresetMeta['assistantType'] ?? 'bright_guide';
        $csrfTokenValue = csrf_token();
        $promptWriterUrlValue = route('app.prompt-writer.generate');
        $billingPlansUrlValue = route('app.billing.plans');

        $voicesJson = collect($voices ?? [])->map(function ($v) {
            return [
                'id' => $v['voiceId'] ?? $v['id'] ?? '',
                'name' => $v['name'] ?? $v['voiceId'] ?? 'Unknown',
                'provider' => $v['provider'] ?? 'unknown',
                'language' => $v['language'] ?? $v['accent'] ?? '',
                'role' => $v['role'] ?? 'default',
            ];
        })->values()->all();
    @endphp

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)]" x-data="assistantForm()">
        <form method="POST" action="{{ $formAction }}" class="space-y-6" x-data="{ saving: false }" @submit="saving = true">
            @csrf
            @if($editing)
                <input type="hidden" name="assistant_id" value="{{ $config->id }}">
            @endif

            <x-ui.panel title="General" description="Name the assistant and choose whether calls can hand off to a person.">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="tc-field md:col-span-2">
                        <label for="name" class="tc-field-label">Assistant name</label>
                        <input id="name" type="text" name="name" value="{{ $assistantNameValue }}" class="tc-input" placeholder="Front Desk, Call Assistant, Scheduling Line" x-model="name" required />
                        <p class="tc-help">{{ $defaultAssistantDraft ? 'We prefilled this from your selected workflow. You can rename it before saving.' : 'This is the name you will see across the app.' }}</p>
                    </div>

                    <div class="tc-field md:col-span-2">
                        <label for="first_message" class="tc-field-label">First thing the assistant says</label>
                        <textarea id="first_message" name="first_message" rows="3" class="tc-textarea" x-model="firstMessage" placeholder="Hi, thanks for calling. How can I help today?">{{ $firstMessageValue }}</textarea>
                        <p class="mt-2 tc-help">This is the opening line callers hear when the call starts.</p>
                    </div>

                    <div class="tc-field md:col-span-2">
                        <label for="fallback_phone" class="tc-field-label">Live human handoff</label>
                        <input id="fallback_phone" type="text" name="fallback_phone" value="{{ $fallbackPhoneValue }}"
                            x-model="fallbackPhone"
                            class="tc-input" placeholder="+12345678900" />
                        <p class="tc-help">Optional. If the caller asks for a person, transfer the call here.</p>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Voice" description="Choose the provider, language, and voice callers will hear.">
                <div class="mb-5 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 p-4">
                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">AI engine</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        @if($workspaceIsFree)
                            Free workspaces use the Standard engine. You can still preview the premium engines below, then upgrade when you are ready.
                        @else
                            Higher tiers cost more, but they can sound more polished and handle complex calls more smoothly.
                        @endif
                    </p>

                    <input type="hidden" name="model_name" x-model="selectedModelName">

                    <div class="mt-4 grid items-start gap-3 lg:grid-cols-2">
                        <template x-for="option in modelOptions" :key="option.value">
                            <button
                                type="button"
                                class="flex flex-col rounded-[1.1rem] border p-4 text-left transition"
                                :class="modelCardClass(option)"
                                @click="chooseModel(option)"
                                @keydown.enter.prevent="chooseModel(option)"
                                @keydown.space.prevent="chooseModel(option)">
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-sm font-semibold text-slate-950" x-text="option.label"></div>
                                    <span class="rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em]"
                                        :class="modelCostBadgeClass(option)"
                                        x-text="option.costLabel"></span>
                                    <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500"
                                        x-text="option.qualityLabel"></span>
                                    <span x-show="option.locked" class="tc-badge tc-accent-badge">Upgrade</span>
                                </div>
                                <div class="mt-2 text-base font-semibold text-slate-950" x-text="option.headline"></div>
                                <div class="mt-1 text-sm font-medium text-slate-700" x-text="option.value"></div>
                                <p class="mt-3 text-sm leading-6 text-slate-600" x-text="option.description"></p>
                                <p x-show="option.locked" class="mt-3 text-xs font-medium uppercase tracking-[0.12em] tc-accent-text-strong">Unlock on a paid plan</p>
                            </button>
                        </template>
                    </div>
                </div>

                <input type="hidden" name="voice_provider" x-model="selectedProvider">

                <div class="grid gap-5 md:grid-cols-3">
                    <div class="tc-field">
                        <label class="tc-field-label">Provider</label>
                        <div class="mt-2 space-y-2">
                            <template x-for="provider in providerOptions" :key="provider.value">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-[1rem] border px-3 py-3 text-left transition"
                                    :class="providerCardClass(provider)"
                                    :disabled="provider.locked"
                                    @click="chooseProvider(provider)">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-950" x-text="provider.label"></div>
                                        <p class="mt-1 text-xs leading-5 text-slate-500" x-text="provider.help"></p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em]"
                                        :class="provider.locked ? 'border-slate-200 bg-slate-100 text-slate-500' : (selectedProvider === provider.value ? 'tc-accent-badge' : 'border-slate-200 bg-white text-slate-500')"
                                        x-text="provider.locked ? 'Locked' : (selectedProvider === provider.value ? 'Selected' : 'Available')"></span>
                                </button>
                            </template>
                        </div>
                        <p class="mt-2 tc-help" x-show="providerOptions.some((provider) => provider.locked)">Upgrade to unlock the locked voice providers.</p>
                    </div>

                    <div class="tc-field">
                        <label for="language_code" class="tc-field-label">Language</label>
                        <select id="language_code" name="language_code" class="tc-input" x-model="selectedLanguageCode" @change="handleLanguageChange()">
                            <template x-for="language in languageOptions" :key="language.value">
                                <option :value="language.value" x-text="language.label"></option>
                            </template>
                        </select>
                    </div>

                    <div class="tc-field">
                        <label for="voice_id" class="tc-field-label">Voice</label>
                        <select id="voice_id" name="voice_id" class="tc-input" x-model="selectedVoiceId">
                            <option value="">Select a voice</option>
                            <template x-for="voice in filteredVoices" :key="voice.id">
                                <option :value="voice.id" x-text="voice.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div x-show="selectedVoiceId" x-transition class="tc-accent-surface mt-5 rounded-[1.25rem] border p-4">
                    <div class="tc-accent-text-strong text-[0.72rem] font-semibold uppercase tracking-[0.18em]">Selected voice</div>
                    <div class="tc-accent-text-strong mt-2 text-sm font-semibold" x-text="selectedVoiceName"></div>
                    <div class="tc-accent-text-strong mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                        <span class="whitespace-nowrap" x-text="providerLabel(selectedProvider)"></span>
                        <span class="tc-accent-text-soft whitespace-nowrap">/</span>
                        <span class="whitespace-nowrap" x-text="languageLabel(selectedLanguageCode)"></span>
                    </div>
                    <p class="tc-accent-text-strong mt-2 text-sm leading-6" x-show="selectedModel.voiceMode === 'realtime'">Premium voice is tuned to sound quicker and brighter, with faster turn-taking and less drag between sentences.</p>
                </div>

                <div class="mt-5 rounded-[1.25rem] border border-slate-200 bg-slate-50/85 p-4">
                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $pilotStack['title'] }}</div>
                    <div class="mt-3 grid gap-3 md:grid-cols-3">
                        <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Telephony</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotStack['telephony'] }}</p>
                        </div>
                        <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Speech stack</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotStack['transcriber'] }} + {{ $pilotStack['voice'] }}</p>
                        </div>
                        <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Model layer</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotStack['llm'] }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-500">{{ $pilotStack['note'] }}</p>

                    <div class="mt-4 rounded-[1rem] border border-slate-200 bg-white px-4 py-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $pilotPlaybook['title'] }}</div>
                            <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $pilotPlaybook['recommended_preset'] }}</span>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotPlaybook['summary'] }}</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Voice path</div>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotPlaybook['recommended_voice_path'] }}</p>
                            </div>
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Best demo calls</div>
                                <ul class="mt-2 space-y-1.5 text-sm leading-6 text-slate-600">
                                    @foreach(array_slice($pilotPlaybook['demo_calls'], 0, 3) as $scenario)
                                        <li class="flex gap-2">
                                            <span class="mt-2 h-1.5 w-1.5 rounded-full tc-accent-fill"></span>
                                            <span>{{ $scenario }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="Behavior" description="Pick how the assistant should feel on the phone.">
                <div class="space-y-5">
                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/85 p-4">
                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Pick one</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Choose the phone personality that feels right. You can still fine-tune the timing after that.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach($presets as $p)
                            @php
                                $meta = $presetMeta->firstWhere('key', $p->key);
                            @endphp
                            <label class="block cursor-pointer rounded-[1.25rem] border p-4 transition"
                                :class="selectedPresetKey === '{{ $p->key }}' ? 'tc-accent-card-active' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80'">
                                <input type="radio" name="preset_key" value="{{ $p->key }}" class="sr-only" x-model="selectedPresetKey" @change="handlePresetChange()">
                                <div class="flex flex-col gap-4 lg:grid lg:grid-cols-[minmax(0,1.5fr)_repeat(4,minmax(0,0.7fr))] lg:items-start">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition"
                                                :class="selectedPresetKey === '{{ $p->key }}' ? 'tc-accent-radio-active' : 'border-slate-300 bg-white'">
                                                <span class="h-2 w-2 rounded-full bg-white" x-show="selectedPresetKey === '{{ $p->key }}'"></span>
                                            </span>
                                            <div class="text-base font-semibold text-slate-950">{{ $p->name }}</div>
                                            <span class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $meta['fit'] ?? 'General' }}</span>
                                        </div>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $meta['simpleNotes'] ?? $p->notes }}</p>
                                    </div>

                                    <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Sounds like</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $meta['toneLabel'] ?? 'Balanced' }}</div>
                                    </div>

                                    <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Talks at</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $meta['waitLabel'] ?? 'Quick' }}</div>
                                    </div>

                                    <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Interrupts</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $meta['interruptLabel'] ?? 'Balanced' }}</div>
                                    </div>

                                    <div class="rounded-[1rem] border border-slate-200 bg-white px-3 py-3">
                                        <div class="text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500">Voice feel</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $meta['voiceProfileLabel'] ?? 'Clear' }}</div>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/85 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="max-w-2xl">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-slate-500">Chosen behavior</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <div class="text-lg font-semibold text-slate-950" x-text="selectedPreset.name"></div>
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-emerald-700" x-text="selectedPreset.fit"></span>
                                    <span x-show="hasCustomTiming" x-transition class="tc-badge tc-accent-badge">Custom timing active</span>
                                </div>
                                <p class="mt-2 text-sm leading-6 text-slate-600" x-text="selectedPreset.simpleNotes || selectedPreset.notes"></p>
                            </div>
                            <button type="button" class="tc-btn-secondary !px-3 !py-2 text-xs" @click="applyPresetTiming()">Use chosen timing</button>
                        </div>

                        <div class="mt-5 grid items-start gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-[1rem] border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Good for</div>
                                <p class="mt-2 text-sm leading-6 text-slate-600" x-text="selectedPreset.recommendedFor"></p>
                            </div>
                            <div class="rounded-[1rem] border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Talks like</div>
                                <div class="mt-2 text-base font-semibold text-slate-950" x-text="selectedPreset.toneLabel"></div>
                            </div>
                            <div class="rounded-[1rem] border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Waits before talking</div>
                                <div class="mt-2 text-base font-semibold text-slate-950" x-text="selectedPreset.waitLabel"></div>
                                <p class="mt-1 text-sm leading-6 text-slate-600" x-text="selectedPreset.waitSeconds + 's before it starts'"></p>
                            </div>
                            <div class="rounded-[1rem] border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">When callers jump in</div>
                                <div class="mt-2 text-base font-semibold text-slate-950" x-text="selectedPreset.interruptLabel"></div>
                                <p class="mt-1 text-sm leading-6 text-slate-600" x-text="selectedPreset.numWords + ' words to count as an interruption'"></p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                            <button type="button" class="tc-btn-ghost !px-0 text-sm" @click="showAdvancedTiming = !showAdvancedTiming" x-text="showAdvancedTiming ? 'Hide timing sliders' : 'Fine-tune timing'"></button>
                            <p class="text-sm text-slate-500">Most people can leave this alone.</p>
                        </div>
                    </div>

                    <div x-show="showAdvancedTiming" x-transition class="rounded-[1.25rem] border border-slate-200 bg-white p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Advanced timing</div>
                                <p class="mt-1 text-sm leading-6 text-slate-600">Only touch these if the chosen behavior feels almost right but still needs a tiny adjustment.</p>
                            </div>
                            <button type="button" class="tc-btn-ghost !px-0 text-xs" @click="applyPresetTiming()">Reset to preset</button>
                        </div>

                        <div class="mt-5 grid gap-5 md:grid-cols-3">
                            <div class="tc-field">
                                <div class="flex items-center justify-between gap-3">
                                    <label for="waitSeconds" class="tc-field-label">Wait seconds</label>
                                    <span class="text-xs font-medium text-slate-500" x-text="waitSecondsOverride + 's'"></span>
                                </div>
                        <input type="range" name="override_params[waitSeconds]" id="waitSeconds" min="0.1" max="3.0" step="0.1" x-model="waitSecondsOverride" class="tc-accent-range w-full">
                                <p class="tc-help">How long the assistant waits before speaking.</p>
                            </div>

                            <div class="tc-field">
                                <div class="flex items-center justify-between gap-3">
                                    <label for="numWords" class="tc-field-label">Interruption words</label>
                                    <span class="text-xs font-medium text-slate-500" x-text="interruptionWordsOverride + ' words'"></span>
                                </div>
                        <input type="range" name="override_params[numWords]" id="numWords" min="1" max="5" step="1" x-model="interruptionWordsOverride" class="tc-accent-range w-full">
                                <p class="tc-help">How many caller words count as an interruption.</p>
                            </div>

                            <div class="tc-field">
                                <div class="flex items-center justify-between gap-3">
                                    <label for="backoffSeconds" class="tc-field-label">Backoff seconds</label>
                                    <span class="text-xs font-medium text-slate-500" x-text="backoffSecondsOverride + 's'"></span>
                                </div>
                        <input type="range" name="override_params[backoffSeconds]" id="backoffSeconds" min="0.5" max="3.0" step="0.1" x-model="backoffSecondsOverride" class="tc-accent-range w-full">
                                <p class="tc-help">How long the assistant waits before speaking again.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.panel>

            <x-ui.panel title="System prompt" description="Tell the assistant its job in plain words.">
                <x-slot:actions>
                    <button type="button" class="tc-btn-secondary !gap-2 !px-3 !py-2 text-xs" @click="togglePromptWriter()" :aria-expanded="promptWriterOpen.toString()">
                            <svg class="tc-accent-icon h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 3l1.05 3.95L17 8l-3.95 1.05L12 13l-1.05-3.95L7 8l3.95-1.05L12 3z" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M18.5 14.5l.6 2.4 2.4.6-2.4.6-.6 2.4-.6-2.4-2.4-.6 2.4-.6.6-2.4z" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M5.5 14.5l.45 1.8 1.8.45-1.8.45-.45 1.8-.45-1.8-1.8-.45 1.8-.45.45-1.8z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>Help from AI</span>
                    </button>
                </x-slot:actions>

                <div class="tc-field">
                    <label for="system_prompt" class="tc-field-label">Prompt</label>
                    <textarea id="system_prompt" name="system_prompt" rows="10" class="tc-textarea" placeholder="You are the voice assistant for [company]. Sound natural, do not interrupt callers, create the ticket before booking any follow-up, and keep the call moving in short spoken sentences..." x-model="promptText">{{ $promptTextValue }}</textarea>
                    <p class="tc-help">{{ $defaultAssistantDraft ? 'This was drafted from your setup path. Keep it short, clear, and easy to say out loud.' : 'Write the job, what details to collect, and what should happen next. Keep it easy to say on the phone.' }}</p>
                </div>

                <div x-show="promptWriterOpen" x-transition class="mt-5 rounded-[1.35rem] border border-slate-200 bg-slate-50/70 p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <div class="text-[0.68rem] font-semibold uppercase tracking-[0.22em] text-slate-500">Prompt writer</div>
                            <div class="mt-2 text-sm font-semibold text-slate-950">Tell us what you want. We will turn it into a cleaner phone prompt.</div>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                It uses the assistant name, selected behavior, language, and handoff settings you already picked.
                                @if(! $aiWriterAvailable)
                                    AI is not configured in this environment, so this will generate a best-practice draft from local templates.
                                @endif
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em]"
                                :class="promptWriter.mode === 'ai' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-500'"
                                x-text="promptWriter.mode === 'ai' ? 'AI mode' : 'Template mode'">
                                {{ $aiWriterAvailable ? 'AI mode' : 'Template mode' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div class="rounded-[1rem] border border-slate-200 bg-white px-4 py-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Using your current setup</div>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">The writer pulls from the assistant name, behavior, language, and handoff settings above.</p>
                                </div>
                                <button type="button" class="tc-btn-ghost !px-0 text-xs" @click="seedPromptWriterContext(true)">Refresh from form</button>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600" x-text="name || 'New assistant'"></span>
                                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600" x-text="selectedPreset.name"></span>
                                <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600" x-text="languageLabel(selectedLanguageCode)"></span>
                                <template x-if="fallbackPhone">
                                    <span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600">Human handoff on</span>
                                </template>
                            </div>
                        </div>

                        <div class="tc-field">
                            <label for="prompt_writer_description" class="tc-field-label">What should the assistant do?</label>
                            <textarea id="prompt_writer_description" rows="4" class="tc-textarea" x-model="promptWriter.description" placeholder="Describe the business, what the caller usually needs, what the assistant should collect, and how the assistant should sound on the phone."></textarea>
                            <p class="tc-help">Example: greet the caller warmly, capture the issue, confirm the summary, create the ticket first, then book a follow-up if the caller asks for one.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="tc-field">
                                <label class="tc-field-label">Tone</label>
                                <select class="tc-input" x-model="promptWriter.tone">
                                    <option value="friendly">Friendly</option>
                                    <option value="professional">Professional</option>
                                    <option value="strict">Strict</option>
                                </select>
                            </div>

                            <div class="tc-field">
                                <label class="tc-field-label">How closely should it follow rules?</label>
                                <select class="tc-input" x-model="promptWriter.strictness">
                                    <option value="low">Relaxed</option>
                                    <option value="medium">Balanced</option>
                                    <option value="high">Strict</option>
                                </select>
                            </div>
                        </div>

                        <div class="tc-field">
                            <div class="tc-field-label">Tools</div>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="tool in promptToolOptions" :key="tool.value">
                                    <button type="button"
                                        class="rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.12em] transition"
                                    :class="promptWriter.toolsEnabled.includes(tool.value) ? 'tc-accent-badge' : 'border-slate-200 bg-white text-slate-500'"
                                        @click="toggleTool(tool.value)"
                                        x-text="tool.label"></button>
                                </template>
                            </div>
                        </div>

                        <div x-show="promptWriter.error" class="rounded-[1rem] border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="promptWriter.error"></div>
                        <div x-show="promptWriter.applied" x-transition class="rounded-[1rem] border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                            Prompt applied to the editor. Review it, make any final edits, then save and sync.
                        </div>

                        <div class="rounded-[1.2rem] border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Draft preview</div>
                                <span x-show="promptWriter.output" class="rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em]"
                                    :class="promptWriter.mode === 'ai' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-500'"
                                    x-text="promptWriter.mode === 'ai' ? 'Generated with AI' : 'Generated from template'"></span>
                            </div>
                            <p class="mt-2 max-h-40 overflow-y-auto whitespace-pre-wrap text-sm leading-6 text-slate-700" x-text="promptWriter.output || 'Generate a draft and it will show up here.'"></p>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <button type="button" class="tc-btn-secondary !px-3 !py-2 text-xs" @click="generatePrompt()" :disabled="promptWriter.loading">
                                <span x-text="promptWriter.loading ? 'Generating...' : 'Generate draft'"></span>
                            </button>
                            <button type="button" class="tc-btn-primary !px-3 !py-2 text-xs" @click="applyGeneratedPrompt()" :disabled="!promptWriter.output">
                                Use in prompt
                            </button>
                        </div>
                    </div>
                </div>
            </x-ui.panel>

            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('app.assistant.edit', $ws) }}" class="tc-btn-ghost">Cancel</a>
                <button type="submit" class="tc-btn-primary" :disabled="saving">
                    <span x-show="saving" class="tc-spinner" aria-hidden="true"></span>
                    <span x-text="saving ? 'Saving and syncing...' : '{{ $editing ? 'Save and sync' : 'Create and sync' }}'">{{ $editing ? 'Save and sync' : 'Create and sync' }}</span>
                </button>
            </div>
        </form>

        <div class="space-y-6">
            <x-ui.panel title="Assistant preview" description="What will be saved and synced.">
                <div class="tc-assistant-card border border-slate-200 bg-slate-50/60 shadow-none">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Preview</div>
                    <div class="mt-4 text-lg font-semibold text-slate-950" x-text="name || 'New Assistant'">
                        {{ $config->name ?? 'New Assistant' }}
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if($editing && !empty($config->vapi_assistant_id))
                            <x-ui.badge tone="success">Synced</x-ui.badge>
                        @else
                            <x-ui.badge tone="warning">Will sync on save</x-ui.badge>
                        @endif
                    </div>

                    <div class="mt-5 space-y-4 text-sm text-slate-600">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">AI engine</div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="whitespace-nowrap font-medium text-slate-900" x-text="selectedModel.label"></span>
                                <span class="whitespace-nowrap text-slate-400">/</span>
                                <span class="whitespace-nowrap text-slate-600" x-text="selectedModel.headline"></span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600" x-text="selectedModel.description"></p>
                        </div>
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Voice</div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="whitespace-nowrap" x-text="selectedVoiceName"></span>
                                <span class="whitespace-nowrap text-slate-400">/</span>
                                <span class="whitespace-nowrap" x-text="providerLabel(selectedProvider)"></span>
                                <span class="whitespace-nowrap text-slate-400">/</span>
                                <span class="whitespace-nowrap" x-text="languageLabel(selectedLanguageCode)"></span>
                            </div>
                        </div>
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Fallback handoff</div>
                            <div class="mt-2" x-text="fallbackPhone || 'Not configured'">{{ old('fallback_phone', $config->fallback_phone ?? 'Not configured') }}</div>
                        </div>
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Opening line</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600" x-text="firstMessage || 'Not set yet'"></p>
                        </div>
                    </div>

                    @if($showDeveloperAssistantIds && $editing && $config->vapi_assistant_id)
                        <div class="mt-5 border-t border-slate-200 pt-4 text-xs text-slate-500">
                            Vapi assistant ID: <code class="font-mono text-slate-700">{{ \Illuminate\Support\Str::limit($config->vapi_assistant_id, 28) }}</code>
                        </div>
                    @endif
                </div>
            </x-ui.panel>

        </div>

    </div>

    <script>
        function assistantForm() {
            const allVoices = @json($voicesJson);
            const presetMap = @json($presetMapJson);
            const csrfToken = @js($csrfTokenValue);
            const promptWriterUrl = @js($promptWriterUrlValue);
            const billingPlansUrl = @js($billingPlansUrlValue);
            const editingAssistantId = @js($editingAssistantId);
            const aiWriterAvailable = @js($aiWriterAvailable);
            const languageOptionsCatalog = @json($languageOptionsJson);
            const languageLabels = Object.fromEntries(languageOptionsCatalog.map((option) => [option.value, option.label]));
            const modelOptions = @json($modelOptions);
            const providerLabels = {
                vapi: 'Vapi curated',
                openai: 'OpenAI voice',
                azure: 'Azure neural',
            };
            const providerHelp = {
                vapi: 'Best for most assistants.',
                openai: 'Best for premium voice quality.',
                azure: 'Best for Arabic and wider language coverage.',
            };
            const freeWorkspace = @js($workspaceIsFree);

            return {
                voices: allVoices,
                allLanguageOptions: languageOptionsCatalog,
                name: @js($assistantNameValue),
                workflowDraft: @js($workflowDraftJson),
                fallbackPhone: @js($fallbackPhoneValue),
                firstMessage: @js($firstMessageValue),
                promptText: @js($promptTextValue),
                selectedModelName: @js($selectedModelName),
                selectedProvider: @js($selectedProviderValue),
                selectedLanguageCode: @js($selectedLanguageValue),
                selectedVoiceId: @js($selectedVoiceValue),
                promptWriterOpen: false,
                selectedPresetKey: @js($selectedPresetKey),
                presetMap,
                modelOptions,
                showAdvancedTiming: @js($showAdvancedTiming),
                waitSecondsOverride: @js($defaultWaitSeconds),
                interruptionWordsOverride: @js($defaultNumWords),
                backoffSecondsOverride: @js($defaultBackoffSeconds),
                aiWriterAvailable,
                promptWriter: {
                    description: '',
                    assistantType: @js($selectedAssistantType),
                    tone: 'professional',
                    strictness: 'medium',
                    language: @js($selectedLanguageValue),
                    toolsEnabled: ['create_ticket', 'book_meeting'],
                    loading: false,
                    error: '',
                    output: '',
                    applied: false,
                    mode: @js($promptWriterMode),
                },
                promptToolOptions: [
                    { value: 'create_ticket', label: 'Create ticket' },
                    { value: 'book_meeting', label: 'Book meeting' },
                    { value: 'handoff_human', label: 'Handoff human' },
                    { value: 'lookup_contact', label: 'Lookup contact' },
                ],
                init() {
                    this.handleModelChange(false);
                    this.handleProviderChange();
                    this.ensureVoiceSelection();
                },
                get selectedModel() {
                    return this.modelOptions.find((option) => option.value === this.selectedModelName) || this.modelOptions[0];
                },
                get selectedPreset() {
                    return this.presetMap[this.selectedPresetKey] || Object.values(this.presetMap)[0] || {
                        key: '',
                        name: 'Custom',
                        notes: 'Bring your own prompt and smooth timing decisions.',
                        fit: 'General',
                        toneLabel: 'Balanced',
                        paceLabel: 'Balanced',
                        voiceProfileLabel: 'Clear',
                        responseStyleLabel: 'General',
                        recommendedFor: 'Businesses that want a smooth, natural phone agent with strong follow-through.',
                        waitSeconds: 0.65,
                        numWords: 2,
                        backoffSeconds: 1.1,
                        voiceSpeed: 1.0,
                        assistantType: 'bright_guide',
                    };
                },
                get hasCustomTiming() {
                    return Number(this.waitSecondsOverride) !== Number(this.selectedPreset.waitSeconds ?? 0.5)
                        || Number(this.interruptionWordsOverride) !== Number(this.selectedPreset.numWords ?? 2)
                        || Number(this.backoffSecondsOverride) !== Number(this.selectedPreset.backoffSeconds ?? 1.0);
                },
                providerLabel(provider) {
                    return providerLabels[provider] || provider;
                },
                languageLabel(language) {
                    return languageLabels[language] || language;
                },
                standardLanguageVoice() {
                    return this.voices.find((voice) =>
                        voice.provider === 'azure'
                        && voice.language === this.selectedLanguageCode
                        && (voice.role === 'default' || voice.role === 'operator')
                    ) || null;
                },
                providerAllowedOnFree(provider) {
                    if (!freeWorkspace) {
                        return true;
                    }

                    if (provider === 'vapi') {
                        return true;
                    }

                    if (provider !== 'azure') {
                        return false;
                    }

                    return Boolean(this.standardLanguageVoice());
                },
                get providerOptions() {
                    return Array.from(new Set(this.voices.map((voice) => voice.provider))).map((provider) => ({
                        value: provider,
                        label: this.providerLabel(provider),
                        help: providerHelp[provider] || 'Voice provider',
                        locked: !this.providerAllowedOnFree(provider),
                    }));
                },
                get compatibleVoices() {
                    if (this.selectedModel.voiceMode !== 'realtime') {
                        return this.voices;
                    }

                    return this.voices.filter((voice) => voice.provider === 'openai' && ['alloy', 'echo', 'shimmer', 'marin', 'cedar'].includes(voice.id));
                },
                get languageOptions() {
                    return this.allLanguageOptions;
                },
                get filteredVoices() {
                    return this.compatibleVoices.filter((voice) => {
                        const providerMatches = !this.selectedProvider || voice.provider === this.selectedProvider;
                        const languageMatches = !this.selectedLanguageCode
                            || voice.language === this.selectedLanguageCode
                            || voice.language === 'multi';
                        return providerMatches && languageMatches;
                    });
                },
                handleModelChange(shouldAdjustVoice = true) {
                    if (this.selectedModel.voiceMode === 'realtime') {
                        this.selectedProvider = 'openai';
                    } else if (!this.providerAllowedOnFree(this.selectedProvider)) {
                        this.selectedProvider = this.recommendedVoiceForCurrentState().provider;
                    }

                    if (shouldAdjustVoice) {
                        this.ensureVoiceSelection(true);
                    }
                },
                handleProviderChange() {
                    const providerHasLanguage = this.compatibleVoices.some((voice) =>
                        voice.provider === this.selectedProvider
                        && (voice.language === this.selectedLanguageCode || voice.language === 'multi')
                    );

                    if (!providerHasLanguage) {
                        this.selectedProvider = this.recommendedVoiceForCurrentState().provider;
                    }

                    this.promptWriter.language = this.selectedLanguageCode || this.promptWriter.language;
                    this.ensureVoiceSelection();
                },
                goToBilling() {
                    window.location.href = billingPlansUrl;
                },
                isModelLocked(option) {
                    return Boolean(option && option.locked);
                },
                modelCardClass(option) {
                    if (this.isModelLocked(option)) {
                        return 'cursor-pointer border-slate-200 bg-slate-50/90 tc-accent-card-hover';
                    }

                    if (this.selectedModelName === option.value) {
                        return 'tc-accent-card-active';
                    }

                    return 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80';
                },
                modelCostBadgeClass(option) {
                    if (this.isModelLocked(option)) {
                        return 'tc-accent-badge';
                    }

                    if (this.selectedModelName === option.value) {
                        return 'tc-accent-badge-strong';
                    }

                    return 'border-slate-200 bg-slate-50 text-slate-500';
                },
                chooseModel(option) {
                    if (this.isModelLocked(option)) {
                        this.goToBilling();
                        return;
                    }

                    if (this.selectedModelName === option.value) {
                        return;
                    }

                    this.selectedModelName = option.value;
                    this.handleModelChange();
                },
                providerCardClass(provider) {
                    if (provider.locked) {
                        return 'cursor-not-allowed border-slate-200 bg-slate-50/80 opacity-70';
                    }

                    if (this.selectedProvider === provider.value) {
                        return 'tc-accent-card-active';
                    }

                    return 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80';
                },
                chooseProvider(provider) {
                    if (provider.locked || this.selectedProvider === provider.value) {
                        return;
                    }

                    this.selectedProvider = provider.value;
                    this.handleProviderChange();
                },
                handleLanguageChange() {
                    this.promptWriter.language = this.selectedLanguageCode;
                    const recommended = this.recommendedVoiceForCurrentState();
                    const providerHasLanguage = this.compatibleVoices.some((voice) =>
                        voice.provider === this.selectedProvider
                        && (voice.language === this.selectedLanguageCode || voice.language === 'multi')
                    );

                    if (!providerHasLanguage && this.providerAllowedOnFree(recommended.provider)) {
                        this.selectedProvider = recommended.provider;
                    }

                    this.ensureVoiceSelection();
                },
                handlePresetChange() {
                    this.promptWriter.assistantType = this.selectedPreset.assistantType || this.promptWriter.assistantType;

                    if (!this.hasCustomTiming) {
                        this.applyPresetTiming(false);
                    }
                },
                applyPresetTiming(openPanel = false) {
                    this.waitSecondsOverride = String(this.selectedPreset.waitSeconds ?? 0.5);
                    this.interruptionWordsOverride = String(this.selectedPreset.numWords ?? 2);
                    this.backoffSecondsOverride = String(this.selectedPreset.backoffSeconds ?? 1.0);
                    if (openPanel) {
                        this.showAdvancedTiming = true;
                    }
                },
                recommendedVoiceForCurrentState() {
                    if (this.selectedModel.voiceMode === 'realtime') {
                        return ['steady_operator', 'confident_closer'].includes(this.selectedPresetKey)
                            ? { provider: 'openai', id: 'alloy' }
                            : { provider: 'openai', id: 'shimmer' };
                    }

                    const prefersOperatorVoice = ['steady_operator', 'confident_closer'].includes(this.selectedPresetKey);
                    const localizedStandardVoices = this.voices.filter((voice) =>
                        voice.provider === 'azure' && voice.language === this.selectedLanguageCode
                    );

                    if (localizedStandardVoices.length) {
                        const preferredLocalizedVoice = localizedStandardVoices.find((voice) =>
                            voice.role === (prefersOperatorVoice ? 'operator' : 'default')
                        );

                        return {
                            provider: 'azure',
                            id: preferredLocalizedVoice?.id || localizedStandardVoices[0].id,
                        };
                    }

                    if (this.selectedPresetKey === 'bright_guide') {
                        return { provider: 'vapi', id: 'Emma' };
                    }

                    if (this.selectedPresetKey === 'steady_operator') {
                        return { provider: 'vapi', id: 'Savannah' };
                    }

                    if (this.selectedPresetKey === 'confident_closer') {
                        return { provider: 'vapi', id: 'Elliot' };
                    }

                    return { provider: 'vapi', id: 'Clara' };
                },
                ensureVoiceSelection(forceRecommended = false) {
                    const recommended = this.recommendedVoiceForCurrentState();
                    const currentStillValid = this.filteredVoices.some((voice) => voice.id === this.selectedVoiceId);

                    if (forceRecommended) {
                        this.selectedProvider = recommended.provider;
                    }

                    const preferredVoice = this.filteredVoices.find((voice) => voice.id === recommended.id);

                    if (forceRecommended && preferredVoice) {
                        this.selectedVoiceId = preferredVoice.id;
                        return;
                    }

                    if (!currentStillValid) {
                        this.selectedVoiceId = preferredVoice?.id || this.filteredVoices[0]?.id || '';
                    }
                },
                get selectedVoiceName() {
                    const voice = this.voices.find((item) => item.id === this.selectedVoiceId);
                    return voice ? voice.name : 'No voice selected';
                },
                toggleTool(tool) {
                    if (this.promptWriter.toolsEnabled.includes(tool)) {
                        this.promptWriter.toolsEnabled = this.promptWriter.toolsEnabled.filter((item) => item !== tool);
                        return;
                    }

                    this.promptWriter.toolsEnabled = [...this.promptWriter.toolsEnabled, tool];
                },
                togglePromptWriter() {
                    this.promptWriterOpen = !this.promptWriterOpen;

                    if (this.promptWriterOpen) {
                        this.seedPromptWriterContext();
                    }
                },
                seedPromptWriterContext(force = false) {
                    if (this.promptWriter.description.trim() && !force) {
                        return;
                    }

                    const parts = [];

                    if (this.name.trim()) {
                        parts.push(`Assistant name: ${this.name.trim()}.`);
                    }

                    if (this.workflowDraft) {
                        parts.push(`Primary workflow: ${this.workflowDraft.label}. ${this.workflowDraft.description}`);
                        parts.push(`Workflow summary: ${this.workflowDraft.summary}.`);
                        parts.push(`Important details to collect: ${this.workflowDraft.requiredFields.join(', ')}.`);
                        parts.push(`Preferred flow: ${this.workflowDraft.callFlow.join(' ')}`);
                        if (this.workflowDraft.commonCalls.length) {
                            parts.push(`Common incoming calls: ${this.workflowDraft.commonCalls.join(', ')}.`);
                        }
                        if (this.workflowDraft.opsOutcomes.length) {
                            parts.push(`Team outcome: ${this.workflowDraft.opsOutcomes.join(' ')}`);
                        }
                        if (this.workflowDraft.emergencyExamples.length) {
                            parts.push(`Urgent examples to flag faster: ${this.workflowDraft.emergencyExamples.join(', ')}.`);
                        }
                    }

                    parts.push(`Workflow preset: ${this.selectedPreset.name}. ${this.selectedPreset.notes}`);
                    parts.push(`Primary language: ${this.languageLabel(this.selectedLanguageCode)}.`);
                    parts.push(`AI engine: ${this.selectedModel.label} using ${this.selectedModel.headline}. ${this.selectedModel.description}`);
                    const selectedVoice = this.selectedVoiceId ? this.selectedVoiceName : '';
                    parts.push(`Voice setup: ${this.providerLabel(this.selectedProvider)}${selectedVoice ? ` using ${selectedVoice}` : ''}.`);

                    if (this.fallbackPhone.trim()) {
                        parts.push(`Escalate to a human at ${this.fallbackPhone.trim()} when needed.`);
                    }

                    if (this.firstMessage.trim()) {
                        parts.push(`Preferred opening line: ${this.firstMessage.trim()}`);
                    }

                    if (this.promptText.trim()) {
                        const excerpt = this.promptText.trim().replace(/\s+/g, ' ').slice(0, 220);
                        parts.push(`Current prompt direction: ${excerpt}${this.promptText.trim().length > 220 ? '...' : ''}`);
                    } else {
                        parts.push('Create a production-ready spoken system prompt for this assistant.');
                    }

                    const generatedDescription = parts.join(' ').replace(/\s+/g, ' ').trim();
                    this.promptWriter.description = generatedDescription.length > 2800
                        ? `${generatedDescription.slice(0, 2797).trim()}...`
                        : generatedDescription;
                    this.promptWriter.assistantType = this.selectedPreset.assistantType || this.promptWriter.assistantType;
                    this.promptWriter.language = this.selectedLanguageCode || this.promptWriter.language;
                    this.promptWriter.toolsEnabled = this.defaultPromptTools();
                },
                defaultPromptTools() {
                    const tools = ['create_ticket', 'book_meeting'];

                    if (this.fallbackPhone.trim()) {
                        tools.push('handoff_human');
                    }

                    return [...new Set(tools)];
                },
                async generatePrompt() {
                    this.promptWriter.error = '';
                    this.promptWriter.applied = false;

                    if (!this.promptWriter.description.trim()) {
                        this.promptWriter.error = 'Add some context before generating a draft.';
                        return;
                    }

                    this.promptWriter.loading = true;

                    try {
                        const formData = new FormData();
                        formData.append('description', this.promptWriter.description);
                        formData.append('assistant_name', this.name || '');
                        formData.append('first_message', this.firstMessage || '');
                        formData.append('current_prompt', this.promptText || '');
                        formData.append('assistant_type', this.promptWriter.assistantType);
                        formData.append('tone', this.promptWriter.tone);
                        formData.append('strictness', this.promptWriter.strictness);
                        formData.append('language', this.promptWriter.language || this.selectedLanguageCode || 'en-US');
                        if (editingAssistantId) {
                            formData.append('assistant_id', editingAssistantId);
                        }

                        this.promptWriter.toolsEnabled.forEach((tool) => {
                            formData.append('tools_enabled[]', tool);
                        });

                        const response = await fetch(promptWriterUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            this.promptWriter.error = data.error || Object.values(data.errors || {}).flat().join(' ') || 'The prompt could not be generated.';
                            return;
                        }

                        this.promptWriter.output = data.markdown || '';
                        this.promptWriter.mode = data.mode || (data.ai_available ? 'ai' : 'template');
                    } catch (error) {
                        this.promptWriter.error = 'The prompt could not be generated right now. Please try again.';
                    } finally {
                        this.promptWriter.loading = false;
                    }
                },
                applyGeneratedPrompt() {
                    if (!this.promptWriter.output) {
                        return;
                    }

                    this.promptText = this.promptWriter.output;
                    this.promptWriter.applied = true;
                },
            };
        }
    </script>
@endsection

