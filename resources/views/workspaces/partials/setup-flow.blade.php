@php
    $theme = $theme ?? 'light';
    $dark = $theme === 'dark';
    $submitAction = $submitAction ?? '#';
    $submitLabel = $submitLabel ?? 'Create workspace';
    $backUrl = $backUrl ?? null;
    $backLabel = $backLabel ?? 'Back';
    $slugCheckUrl = $slugCheckUrl ?? route('app.workspaces.check-slug');
    $workspaceId = data_get($workspace, 'id');
    $workspaceNameValue = old('name', data_get($workspace, 'name', ''));
    $workspaceSlugValue = old('slug', data_get($workspace, 'slug', ''));
    $primaryMarketValue = old('primary_market', $selectedMarket ?? data_get($workspace, 'primary_market', \App\Support\RegionalPilotStackCatalog::GLOBAL));
    $selectedUseCaseValue = old('use_case', $selectedUseCase ?? data_get($workspace, 'use_case', 'customer_support'));
    $selectedUseCaseDetailsValue = old('use_case_details', $selectedUseCaseDetails ?? data_get($workspace, 'use_case_details', ''));
    $assistantNameValue = old('assistant_name', data_get($workspace, 'default_assistant_name', $suggestedDefaults['assistant_name'] ?? ''));
    $ticketLabelValue = old('case_label', data_get($workspace, 'case_label', $suggestedDefaults['case_label'] ?? 'Ticket'));
    $presetKeyValue = old('preset_key', data_get($workspace, 'default_preset_key', $suggestedDefaults['preset_key'] ?? 'steady_operator'));
    $languageValue = old('language_code', data_get($workspace, 'default_language_code', $suggestedDefaults['language_code'] ?? 'en-US'));
    $timezoneValue = old('default_timezone', data_get($workspace, 'default_timezone', 'America/New_York'));
    $teamSizeValue = old('team_size', data_get($workspace, 'team_size', ''));
    $captureFieldsValue = old('capture_fields', $selectedCaptureFields ?? []);
    $initialStep = $initialStep ?? (
        $errors->hasAny(['name', 'slug', 'primary_market', 'team_size']) ? 1 :
        ($errors->hasAny(['use_case', 'use_case_details']) ? 2 :
            ($errors->hasAny(['assistant_name', 'preset_key', 'case_label', 'language_code']) ? 3 :
                ($errors->hasAny(['capture_fields', 'capture_fields.*']) ? 4 :
                    ((old('assistant_name') || old('capture_fields')) ? 5 :
                        ((old('use_case') || old('use_case_details')) ? 2 : 1)
                    )
                )
            )
        )
    );

    $cardClass = $dark
        ? 'rounded-[2rem] border border-white/10 bg-slate-950/45 shadow-[0_34px_90px_-42px_rgba(2,6,23,0.96)] backdrop-blur-xl'
        : 'rounded-[2rem] border border-slate-200 bg-white shadow-[0_28px_80px_-38px_rgba(15,23,42,0.16)]';
    $surfaceClass = $dark ? 'border border-white/10 bg-white/[0.04]' : 'border border-slate-200 bg-slate-50/70';
    $softSurfaceClass = $dark ? 'border border-white/10 bg-white/[0.03]' : 'border border-slate-200 bg-white';
    $inputClass = $dark ? 'tc-input-dark' : 'tc-input';
    $textareaClass = $dark ? 'tc-textarea-dark' : 'tc-textarea';
    $titleClass = $dark ? 'text-white' : 'text-slate-950';
    $copyClass = $dark ? 'text-slate-300' : 'text-slate-600';
    $mutedClass = $dark ? 'text-slate-400' : 'text-slate-500';
    $dividerClass = $dark ? 'border-white/10' : 'border-slate-200';
    $primaryButtonClass = $dark ? 'tc-btn-glow' : 'tc-btn-primary';
    $secondaryButtonClass = $dark ? 'tc-btn-glass' : 'tc-btn-secondary';
    $ghostButtonClass = $dark ? 'tc-btn-glass' : 'tc-btn-ghost';
@endphp

<div
    x-data="workspaceFlow({
        useCases: @js(collect($useCases)->mapWithKeys(fn ($case) => [$case['key'] => $case])->all()),
        presetChoices: @js($presetChoices),
        languageOptions: @js($languageOptions),
        initialStep: @js($initialStep),
        workspaceId: @js($workspaceId),
        workspaceName: @js($workspaceNameValue),
        slug: @js($workspaceSlugValue),
        primaryMarket: @js($primaryMarketValue),
        teamSize: @js($teamSizeValue),
        marketOptions: @js($marketOptions ?? \App\Support\RegionalPilotStackCatalog::marketOptions()),
        selectedUseCase: @js($selectedUseCaseValue),
        useCaseDetails: @js($selectedUseCaseDetailsValue),
        assistantName: @js($assistantNameValue),
        ticketLabel: @js($ticketLabelValue),
        presetKey: @js($presetKeyValue),
        languageCode: @js($languageValue),
        timezone: @js($timezoneValue),
        selectedCaptureFields: @js(array_values($captureFieldsValue)),
        slugCheckUrl: @js($slugCheckUrl),
    })"
    x-init="init()"
    class="mx-auto w-full max-w-3xl">
    <form method="POST" action="{{ $submitAction }}" class="{{ $cardClass }} overflow-hidden">
        @csrf

        <input type="hidden" name="default_timezone" x-model="timezone">
        <input type="hidden" name="primary_market" x-model="primaryMarket">
        <input type="hidden" name="use_case" x-model="selectedUseCase">
        <input type="hidden" name="preset_key" x-model="presetKey">
        <input type="hidden" name="language_code" x-model="languageCode">

        <div class="p-6 sm:p-8">
            <div class="flex flex-wrap items-center gap-2 text-[0.72rem] font-semibold uppercase tracking-[0.22em] {{ $mutedClass }}">
                <template x-for="(item, index) in steps" :key="item.key">
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-full px-2 py-1 transition"
                            :class="step === item.index ? '{{ $dark ? 'bg-white/10 text-white' : 'bg-slate-900 text-white' }}' : '{{ $dark ? 'bg-white/[0.04] text-slate-500' : 'bg-slate-100 text-slate-500' }}'">
                            <span x-text="item.label"></span>
                        </span>
                        <span x-show="index < steps.length - 1" class="{{ $mutedClass }}">•</span>
                    </div>
                </template>
            </div>

            <div class="relative mt-6 overflow-hidden">
                <div :style="{ minHeight: stepMinHeight }">
                    <section
                        x-show="step === 1"
                        x-transition:enter="transition ease-out duration-250"
                        x-transition:enter-start="opacity-0 translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-180"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-8"
                        class="space-y-6">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] {{ $mutedClass }}">Step 1</div>
                            <h1 class="mt-4 text-3xl font-semibold tracking-tight {{ $titleClass }}">Create your workspace</h1>
                            <p class="mt-3 text-sm leading-6 {{ $copyClass }}">Start with the basics. You can change everything later.</p>
                        </div>

                        <div class="space-y-5">
                            <div class="tc-field">
                                <label for="workspace_name" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Workspace name</label>
                                <input id="workspace_name" name="name" type="text" class="{{ $inputClass }}" x-model="workspaceName" @input="handleWorkspaceNameInput()" placeholder="Good Life Property Management" autocomplete="organization" />
                                @error('name') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                            </div>

                            <div class="tc-field">
                                <label for="workspace_slug" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Workspace URL</label>
                                <div class="flex overflow-hidden rounded-[1rem] {{ $dark ? 'border border-white/10 bg-slate-950/30' : 'border border-slate-200 bg-white' }}">
                                    <span class="flex items-center {{ $dark ? 'border-r border-white/10 bg-white/[0.03] text-slate-500' : 'border-r border-slate-200 bg-slate-50 text-slate-500' }} px-3 text-sm">tickIt/</span>
                                    <input id="workspace_slug" name="slug" type="text" class="w-full border-0 bg-transparent px-3.5 py-3 text-sm {{ $titleClass }} outline-none placeholder:text-slate-500 focus:ring-0" x-model="slug" @input="handleSlugInput()" placeholder="good-life-pm" autocapitalize="none" autocomplete="off" spellcheck="false" />
                                </div>
                                <div class="mt-2 flex items-center gap-2 text-sm">
                                    <span class="inline-flex h-2 w-2 rounded-full" :class="slugStateClass()"></span>
                                    <span :class="slugMessageClass()" x-text="slugMessage()"></span>
                                </div>
                                @error('slug') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                            </div>

                            <div class="tc-field">
                                <label for="team_size" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Team size <span class="{{ $mutedClass }}">Optional</span></label>
                                <select id="team_size" name="team_size" class="{{ $inputClass }}" x-model="teamSize">
                                    <option value="">Select later</option>
                                    @foreach($teamSizeOptions as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('team_size') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                            </div>

                            <div class="tc-field">
                                <label for="primary_market" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Primary market <span class="{{ $mutedClass }}">Optional</span></label>
                                <select id="primary_market" class="{{ $inputClass }}" x-model="primaryMarket" @change="handleMarketChange()">
                                    @foreach(($marketOptions ?? \App\Support\RegionalPilotStackCatalog::marketOptions()) as $option)
                                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-sm leading-6 {{ $copyClass }}" x-text="selectedMarketOption.description || 'We will keep the setup global and adapt the phone stack later if needed.'"></p>
                                @error('primary_market') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </section>

                    <section
                        x-show="step === 2"
                        x-transition:enter="transition ease-out duration-250"
                        x-transition:enter-start="opacity-0 translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-180"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-8"
                        class="space-y-6">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] {{ $mutedClass }}">Step 2</div>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight {{ $titleClass }}">What should your assistant handle?</h2>
                            <p class="mt-3 text-sm leading-6 {{ $copyClass }}">Choose the main type of calls for this workspace.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="(useCase, index) in useCaseList" :key="useCase.key">
                                <div :class="index === useCaseList.length - 1 && useCaseList.length % 2 === 1 ? 'sm:col-span-2 sm:flex sm:justify-center' : ''">
                                    <button
                                        type="button"
                                        class="w-full rounded-[1.2rem] border p-4 text-left transition"
                                        :class="[useCaseCardClass(useCase.key), index === useCaseList.length - 1 && useCaseList.length % 2 === 1 ? 'sm:max-w-[calc(50%-0.375rem)]' : '']"
                                        @click="selectUseCase(useCase.key)">
                                        <div class="text-sm font-semibold {{ $titleClass }}" x-text="useCase.label"></div>
                                        <p class="mt-2 text-sm leading-6 {{ $copyClass }}" x-text="useCase.short_description || useCase.description"></p>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div x-show="selectedUseCase === 'other'" x-transition class="tc-field">
                            <label for="use_case_details" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Custom workflow</label>
                            <textarea id="use_case_details" name="use_case_details" rows="4" class="{{ $textareaClass }}" x-model="useCaseDetails" placeholder="Describe the kinds of calls this workspace needs to handle."></textarea>
                            @error('use_case_details') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                        </div>
                    </section>

                    <section
                        x-show="step === 3"
                        x-transition:enter="transition ease-out duration-250"
                        x-transition:enter-start="opacity-0 translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-180"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-8"
                        class="space-y-6">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] {{ $mutedClass }}">Step 3</div>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight {{ $titleClass }}">Shape your assistant</h2>
                            <p class="mt-3 text-sm leading-6 {{ $copyClass }}">Pick a style and we will prefill the rest.</p>
                        </div>

                        <div class="space-y-5">
                            <div class="tc-field">
                                <label for="assistant_name" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Assistant name</label>
                                <input id="assistant_name" name="assistant_name" type="text" class="{{ $inputClass }}" x-model="assistantName" placeholder="Maintenance desk" />
                                @error('assistant_name') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                            </div>

                            <div class="tc-field">
                                <label class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Tone preset</label>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <template x-for="preset in presetChoices" :key="preset.key">
                                        <button
                                            type="button"
                                            class="rounded-[1rem] border px-4 py-3 text-left transition"
                                            :class="presetCardClass(preset.key)"
                                            @click="presetKey = preset.key">
                                            <div class="text-sm font-semibold {{ $titleClass }}" x-text="preset.label"></div>
                                            <p class="mt-1 text-sm leading-6 {{ $copyClass }}" x-text="preset.description"></p>
                                        </button>
                                    </template>
                                </div>
                                @error('preset_key') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="tc-field">
                                    <label for="case_label" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Ticket label</label>
                                    <input id="case_label" name="case_label" type="text" class="{{ $inputClass }}" x-model="ticketLabel" placeholder="Ticket" />
                                    @error('case_label') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                                </div>

                                <div class="tc-field">
                                    <label for="language_code" class="tc-field-label {{ $dark ? 'text-slate-200' : '' }}">Language <span class="{{ $mutedClass }}">Optional</span></label>
                                    <select id="language_code" class="{{ $inputClass }}" x-model="languageCode">
                                        @foreach($languageOptions as $option)
                                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('language_code') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section
                        x-show="step === 4"
                        x-transition:enter="transition ease-out duration-250"
                        x-transition:enter-start="opacity-0 translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-180"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-8"
                        class="space-y-6">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] {{ $mutedClass }}">Step 4</div>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight {{ $titleClass }}">What should every call capture?</h2>
                            <p class="mt-3 text-sm leading-6 {{ $copyClass }}">Select the details you want collected by default.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <template x-for="field in captureOptions" :key="field.key">
                                <label class="flex cursor-pointer items-start gap-3 rounded-[1rem] border px-4 py-3 transition" :class="captureCardClass(field.key)">
                                    <input type="checkbox" name="capture_fields[]" :value="field.key" class="mt-1 h-4 w-4 rounded border-slate-300 bg-transparent text-orange-500 focus:ring-orange-500 focus:ring-offset-0" x-model="selectedCaptureFields" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold {{ $titleClass }}" x-text="field.label"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                        @error('capture_fields') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                        @error('capture_fields.*') <p class="tc-error {{ $dark ? 'text-red-300' : '' }}">{{ $message }}</p> @enderror
                    </section>

                    <section
                        x-show="step === 5"
                        x-transition:enter="transition ease-out duration-250"
                        x-transition:enter-start="opacity-0 translate-x-8"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transition ease-in duration-180"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 -translate-x-8"
                        class="space-y-6">
                        <div>
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] {{ $mutedClass }}">Step 5</div>
                            <h2 class="mt-4 text-3xl font-semibold tracking-tight {{ $titleClass }}">Ready to launch</h2>
                            <p class="mt-3 text-sm leading-6 {{ $copyClass }}">You can fine-tune prompts, routing, and follow-ups inside the workspace.</p>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[1.25rem] {{ $surfaceClass }} px-4 py-4">
                                <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] {{ $mutedClass }}">Workspace</div>
                                <dl class="mt-3 space-y-3 text-sm leading-6">
                                    <div class="flex items-start justify-between gap-4 border-b {{ $dividerClass }} pb-3">
                                        <dt class="{{ $mutedClass }}">Name</dt>
                                        <dd class="text-right font-medium {{ $titleClass }}" x-text="workspaceName || 'Untitled workspace'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4 border-b {{ $dividerClass }} pb-3">
                                        <dt class="{{ $mutedClass }}">URL</dt>
                                        <dd class="text-right font-medium {{ $titleClass }}" x-text="slug ? 'tickIt/' + slug : 'Not set'"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="{{ $mutedClass }}">Use case</dt>
                                        <dd class="text-right font-medium {{ $titleClass }}" x-text="selectedDefinition.label"></dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="{{ $mutedClass }}">Market</dt>
                                        <dd class="text-right font-medium {{ $titleClass }}" x-text="selectedMarketOption.label"></dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-[1.25rem] {{ $softSurfaceClass }} px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] {{ $mutedClass }}">Assistant</div>
                                    <dl class="mt-3 space-y-3 text-sm leading-6">
                                        <div class="flex items-start justify-between gap-4 border-b {{ $dividerClass }} pb-3">
                                            <dt class="{{ $mutedClass }}">Name</dt>
                                            <dd class="text-right font-medium {{ $titleClass }}" x-text="assistantName"></dd>
                                        </div>
                                        <div class="flex items-start justify-between gap-4 border-b {{ $dividerClass }} pb-3">
                                            <dt class="{{ $mutedClass }}">Tone</dt>
                                            <dd class="text-right font-medium {{ $titleClass }}" x-text="selectedPreset.label"></dd>
                                        </div>
                                        <div class="flex items-start justify-between gap-4">
                                            <dt class="{{ $mutedClass }}">Ticket label</dt>
                                            <dd class="text-right font-medium {{ $titleClass }}" x-text="ticketLabel"></dd>
                                        </div>
                                    </dl>
                                </div>

                                <div class="rounded-[1.25rem] {{ $softSurfaceClass }} px-4 py-4">
                                    <div class="text-[0.68rem] font-semibold uppercase tracking-[0.18em] {{ $mutedClass }}">Capture fields</div>
                                    <ul class="mt-3 grid gap-2 text-sm leading-6 {{ $copyClass }}">
                                        <template x-for="field in selectedCaptureFieldLabels" :key="field">
                                            <li class="flex gap-3">
                                                <span class="mt-2 h-2 w-2 rounded-full {{ $dark ? 'bg-orange-300' : 'tc-accent-fill' }}"></span>
                                                <span x-text="field"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div class="border-t {{ $dividerClass }} px-6 py-4 sm:px-8 sm:py-5">
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <template x-if="step > 1">
                        <button type="button" class="{{ $ghostButtonClass }}" @click="goBack()">Back</button>
                    </template>
                    @if($backUrl)
                        <template x-if="step === 1">
                            <a href="{{ $backUrl }}" class="{{ $secondaryButtonClass }}">{{ $backLabel }}</a>
                        </template>
                    @endif
                </div>

                <div class="flex items-center gap-3 sm:justify-end">
                    <template x-if="step < 5">
                        <button type="button" class="{{ $primaryButtonClass }}" @click="goNext()" :disabled="!canContinue">
                            <span x-text="step === 4 ? 'Review' : 'Continue'"></span>
                        </button>
                    </template>

                    <template x-if="step === 5">
                        <button type="submit" class="{{ $primaryButtonClass }}"><span x-text="finalButtonLabel"></span></button>
                    </template>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function workspaceFlow(config) {
        return {
            step: Number(config.initialStep || 1),
            steps: [
                { index: 1, key: 'workspace', label: 'Workspace' },
                { index: 2, key: 'use_case', label: 'Use case' },
                { index: 3, key: 'style', label: 'Style' },
                { index: 4, key: 'capture', label: 'Capture' },
                { index: 5, key: 'review', label: 'Review' },
            ],
            useCases: config.useCases || {},
            presetChoices: config.presetChoices || [],
            languageOptions: config.languageOptions || [],
            workspaceId: config.workspaceId || null,
            workspaceName: config.workspaceName || '',
            slug: config.slug || '',
            primaryMarket: config.primaryMarket || 'global',
            teamSize: config.teamSize || '',
            marketOptions: config.marketOptions || [],
            selectedUseCase: config.selectedUseCase || 'customer_support',
            useCaseDetails: config.useCaseDetails || '',
            assistantName: config.assistantName || '',
            ticketLabel: config.ticketLabel || 'Ticket',
            presetKey: config.presetKey || 'steady_operator',
            languageCode: config.languageCode || 'en-US',
            timezone: config.timezone || 'America/New_York',
            selectedCaptureFields: Array.isArray(config.selectedCaptureFields) ? config.selectedCaptureFields : [],
            slugTouched: Boolean(config.slug),
            slugStatus: 'idle',
            slugStatusMessage: '',
            slugCheckUrl: config.slugCheckUrl || '',
            slugDebounceHandle: null,
            get useCaseList() {
                return Object.values(this.useCases);
            },
            get selectedDefinition() {
                return this.useCases[this.selectedUseCase] || this.useCases.customer_support || {};
            },
            get selectedMarketOption() {
                return this.marketOptions.find((option) => option.value === this.primaryMarket) || this.marketOptions[0] || { label: 'Global / United States', description: '' };
            },
            get captureOptions() {
                return this.selectedDefinition.capture_fields || [];
            },
            get selectedPreset() {
                return this.presetChoices.find((preset) => preset.key === this.presetKey) || this.presetChoices[0] || { label: 'Professional' };
            },
            get selectedCaptureFieldLabels() {
                const lookup = Object.fromEntries(this.captureOptions.map((field) => [field.key, field.label]));
                return this.selectedCaptureFields.map((field) => lookup[field]).filter(Boolean);
            },
            get canContinue() {
                if (this.step === 1) {
                    return this.workspaceName.trim().length > 1 && this.slug.trim().length > 1 && this.slugStatus === 'available';
                }

                if (this.step === 2) {
                    return this.selectedUseCase !== 'other' || this.useCaseDetails.trim().length >= 6;
                }

                if (this.step === 3) {
                    return this.assistantName.trim().length > 1 && this.ticketLabel.trim().length > 1 && this.presetKey !== '';
                }

                if (this.step === 4) {
                    return this.selectedCaptureFields.length >= 2;
                }

                return true;
            },
            get finalButtonLabel() {
                return '{{ $submitLabel }}';
            },
            get stepMinHeight() {
                const heights = {
                    1: '23rem',
                    2: '27rem',
                    3: '27rem',
                    4: '28rem',
                    5: '24rem',
                };

                return heights[this.step] || '24rem';
            },
            init() {
                this.applyUseCaseDefaults(false);
                this.queueSlugCheck();
            },
            handleMarketChange() {
                const preferredLanguage = this.primaryMarket === 'uae' ? 'ar-AE' : 'en-US';

                if (!this.languageCode || this.languageCode === 'en-US' || this.languageCode === 'ar-AE') {
                    this.languageCode = preferredLanguage;
                }
            },
            handleWorkspaceNameInput() {
                if (!this.slugTouched) {
                    this.slug = this.slugify(this.workspaceName);
                }

                this.queueSlugCheck();
            },
            handleSlugInput() {
                this.slugTouched = true;
                this.slug = this.slugify(this.slug);
                this.queueSlugCheck();
            },
            slugify(value) {
                return (value || '')
                    .toString()
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            },
            queueSlugCheck() {
                clearTimeout(this.slugDebounceHandle);
                this.slugDebounceHandle = setTimeout(() => this.checkSlug(), 220);
            },
            async checkSlug() {
                const normalized = this.slugify(this.slug);
                this.slug = normalized;

                if (!normalized) {
                    this.slugStatus = 'invalid';
                    this.slugStatusMessage = 'Use letters, numbers, or dashes.';
                    return;
                }

                this.slugStatus = 'checking';
                this.slugStatusMessage = 'Checking...';

                try {
                    const params = new URLSearchParams({ slug: normalized });
                    if (this.workspaceId) {
                        params.set('ignore', this.workspaceId);
                    }

                    const response = await fetch(`${this.slugCheckUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await response.json();

                    this.slugStatus = data.available ? 'available' : 'taken';
                    this.slugStatusMessage = data.message || (data.available ? 'Available' : 'Already taken');
                    this.slug = data.slug || normalized;
                } catch (error) {
                    this.slugStatus = 'idle';
                    this.slugStatusMessage = 'We will check this when you continue.';
                }
            },
            slugStateClass() {
                if (this.slugStatus === 'available') return 'bg-emerald-400';
                if (this.slugStatus === 'taken' || this.slugStatus === 'invalid') return 'bg-red-400';
                if (this.slugStatus === 'checking') return 'bg-amber-400';
                return '{{ $dark ? 'bg-slate-600' : 'bg-slate-300' }}';
            },
            slugMessageClass() {
                if (this.slugStatus === 'available') return 'text-emerald-400';
                if (this.slugStatus === 'taken' || this.slugStatus === 'invalid') return 'text-red-400';
                if (this.slugStatus === 'checking') return '{{ $dark ? 'text-amber-300' : 'text-amber-600' }}';
                return '{{ $mutedClass }}';
            },
            slugMessage() {
                return this.slugStatusMessage || 'This will be used in workspace links.';
            },
            selectUseCase(key) {
                if (this.selectedUseCase === key) {
                    return;
                }

                this.selectedUseCase = key;
                this.applyUseCaseDefaults(true);
            },
            applyUseCaseDefaults(shouldReset = true) {
                const definition = this.selectedDefinition;

                if (!definition || Object.keys(definition).length === 0) {
                    return;
                }

                const defaultCaptureFields = (definition.capture_fields || []).filter((field) => field.default).map((field) => field.key);

                if (shouldReset) {
                    this.assistantName = definition.assistant_name || this.assistantName;
                    this.ticketLabel = definition.case_label || this.ticketLabel;
                    this.presetKey = definition.preset_key || this.presetKey;
                    this.languageCode = definition.language_code || this.languageCode;
                    this.selectedCaptureFields = defaultCaptureFields;
                    return;
                }

                if (!this.assistantName) this.assistantName = definition.assistant_name || '';
                if (!this.ticketLabel) this.ticketLabel = definition.case_label || 'Ticket';
                if (!this.presetKey) this.presetKey = definition.preset_key || 'steady_operator';
                if (!this.languageCode) this.languageCode = definition.language_code || 'en-US';
                if (!this.selectedCaptureFields.length) this.selectedCaptureFields = defaultCaptureFields;
                this.handleMarketChange();
            },
            useCaseCardClass(key) {
                if (this.selectedUseCase === key) {
                    return '{{ $dark ? 'border-orange-300/50 bg-orange-400/10 text-white' : 'tc-accent-card-active' }}';
                }

                return '{{ $dark ? 'border-white/10 bg-white/[0.03] hover:border-white/20 hover:bg-white/[0.05]' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80' }}';
            },
            presetCardClass(key) {
                if (this.presetKey === key) {
                    return '{{ $dark ? 'border-orange-300/50 bg-orange-400/10 text-white' : 'tc-accent-card-active' }}';
                }

                return '{{ $dark ? 'border-white/10 bg-white/[0.03] hover:border-white/20 hover:bg-white/[0.05]' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80' }}';
            },
            captureCardClass(key) {
                if (this.selectedCaptureFields.includes(key)) {
                    return '{{ $dark ? 'border-orange-300/40 bg-orange-400/[0.08]' : 'tc-accent-card-active' }}';
                }

                return '{{ $dark ? 'border-white/10 bg-white/[0.03] hover:border-white/20 hover:bg-white/[0.05]' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80' }}';
            },
            goNext() {
                if (!this.canContinue || this.step >= 5) {
                    return;
                }

                this.step += 1;
            },
            goBack() {
                if (this.step <= 1) {
                    return;
                }

                this.step -= 1;
            },
        };
    }
</script>
