@extends('layouts.saas')

@section('title', 'tickIt - Workspace Settings')
@section('header_eyebrow', 'Workspace')
@section('header', 'Workspace settings')
@section('header_description', 'Update the workspace basics and the regional call setup your assistants should inherit.')

@section('header_actions')
    <a href="{{ route('app.workspaces.index') }}" class="tc-btn-secondary">Back to workspaces</a>
@endsection

@section('content')
    @php
        $marketCatalog = collect(\App\Support\RegionalPilotStackCatalog::marketOptions())
            ->mapWithKeys(fn (array $option) => [
                $option['value'] => [
                    'languages' => \App\Support\RegionalPilotStackCatalog::languageOptions($option['value']),
                    'phoneSetups' => \App\Support\RegionalPilotStackCatalog::phoneSetupOptions($option['value']),
                    'providers' => \App\Support\RegionalPilotStackCatalog::externalProviderOptions($option['value']),
                ],
            ])
            ->all();
        $pilotPlaybook = \App\Support\RegionalPilotStackCatalog::forWorkspacePlaybook(
            $workspace,
            old('default_language_code', $workspace->preferredLanguageCode())
        );
    @endphp

    <div
        class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]"
        x-data="{
            marketCatalog: @js($marketCatalog),
            primaryMarket: @js(old('primary_market', $workspace->primaryMarket())),
            defaultLanguageCode: @js(old('default_language_code', $workspace->preferredLanguageCode())),
            defaultPhoneProvisioningMode: @js(old('default_phone_provisioning_mode', $workspace->preferredPhoneSetupMode())),
            defaultExternalPhoneProvider: @js(old('default_external_phone_provider', $workspace->preferredExternalPhoneProvider())),
            get currentMarket() {
                return this.marketCatalog[this.primaryMarket] ?? this.marketCatalog.global;
            },
            get currentLanguageOptions() {
                return this.currentMarket.languages ?? [];
            },
            get currentPhoneSetupOptions() {
                return this.currentMarket.phoneSetups ?? [];
            },
            get currentProviderOptions() {
                return this.currentMarket.providers ?? [];
            },
            syncMarketDefaults() {
                const firstLanguage = this.currentLanguageOptions[0]?.value;
                const firstPhoneSetup = this.currentPhoneSetupOptions[0]?.value;
                const firstProvider = this.currentProviderOptions[0]?.value;

                if (!this.currentLanguageOptions.some((option) => option.value === this.defaultLanguageCode) && firstLanguage) {
                    this.defaultLanguageCode = firstLanguage;
                }

                if (!this.currentPhoneSetupOptions.some((option) => option.value === this.defaultPhoneProvisioningMode) && firstPhoneSetup) {
                    this.defaultPhoneProvisioningMode = firstPhoneSetup;
                }

                if (!this.currentProviderOptions.some((option) => option.value === this.defaultExternalPhoneProvider) && firstProvider) {
                    this.defaultExternalPhoneProvider = firstProvider;
                }
            },
        }"
        x-init="syncMarketDefaults()"
    >
        <x-ui.panel title="{{ $workspace->name }}" description="Keep the workspace identity clean, then set the regional defaults that new assistants and phone numbers should inherit.">
            <form method="POST" action="{{ route('app.workspaces.settings.update', $workspace) }}" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <section class="space-y-5">
                    <div>
                        <div class="tc-label-eyebrow">Basics</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">These details show up across the workspace and on the ticket side of the product.</p>
                    </div>

                    <div class="tc-field">
                        <label for="workspace-name" class="tc-field-label">Workspace name</label>
                        <input id="workspace-name" name="name" type="text" value="{{ old('name', $workspace->name) }}" required class="tc-input" />
                        @if($errors->first('name'))
                            <p class="tc-error">{{ $errors->first('name') }}</p>
                        @endif
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="tc-field">
                            <label for="default_timezone" class="tc-field-label">Default timezone</label>
                            <input id="default_timezone" name="default_timezone" type="text" value="{{ old('default_timezone', $workspace->default_timezone) }}" required class="tc-input" />
                            @if($errors->first('default_timezone'))
                                <p class="tc-error">{{ $errors->first('default_timezone') }}</p>
                            @endif
                        </div>

                        <div class="tc-field">
                            <label for="case_label" class="tc-field-label">Ticket label</label>
                            <input id="case_label" name="case_label" type="text" value="{{ old('case_label', $workspace->case_label) }}" required class="tc-input" />
                            @if($errors->first('case_label'))
                                <p class="tc-error">{{ $errors->first('case_label') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="tc-field">
                        <label for="workspace-logo" class="tc-field-label">Company logo <span class="text-slate-500">Optional</span></label>
                        <div class="tc-meta-card">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
                                        @if($workspace->logoUrl())
                                            <img src="{{ $workspace->logoUrl() }}" alt="{{ $workspace->name }} logo" class="h-full w-full object-contain">
                                        @else
                                            <span class="text-lg font-semibold uppercase tracking-[0.14em] text-slate-400">
                                                {{ \Illuminate\Support\Str::of($workspace->name)->substr(0, 2)->upper() }}
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">Shown at the top of the sidebar</div>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">PNG, JPG, WebP, GIF, or SVG up to 2MB.</p>
                                    </div>
                                </div>

                                @if($workspace->logoUrl())
                                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                        <span>Remove current logo</span>
                                    </label>
                                @endif
                            </div>

                            <div class="mt-4">
                                <input id="workspace-logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800" />
                            </div>
                        </div>
                        @if($errors->first('logo'))
                            <p class="tc-error">{{ $errors->first('logo') }}</p>
                        @endif
                    </div>
                </section>

                <section class="space-y-5 border-t border-slate-200 pt-6">
                    <div>
                        <div class="tc-label-eyebrow">Regional call setup</div>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Set the market, language, and number strategy once here. New assistants and phone setups will start from these defaults.</p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="tc-field">
                            <label for="primary_market" class="tc-field-label">Primary market</label>
                            <select id="primary_market" name="primary_market" class="tc-input" x-model="primaryMarket" @change="syncMarketDefaults()">
                                @foreach($marketOptions as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @if($errors->first('primary_market'))
                                <p class="tc-error">{{ $errors->first('primary_market') }}</p>
                            @endif
                        </div>

                        <div class="tc-field">
                            <label for="default_language_code" class="tc-field-label">Default assistant language</label>
                            <select id="default_language_code" name="default_language_code" class="tc-input" x-model="defaultLanguageCode">
                                <template x-for="option in currentLanguageOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            @if($errors->first('default_language_code'))
                                <p class="tc-error">{{ $errors->first('default_language_code') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="tc-field">
                            <label for="default_phone_provisioning_mode" class="tc-field-label">Default phone setup</label>
                            <select id="default_phone_provisioning_mode" name="default_phone_provisioning_mode" class="tc-input" x-model="defaultPhoneProvisioningMode">
                                <template x-for="option in currentPhoneSetupOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            @if($errors->first('default_phone_provisioning_mode'))
                                <p class="tc-error">{{ $errors->first('default_phone_provisioning_mode') }}</p>
                            @endif
                        </div>

                        <div class="tc-field" x-show="defaultPhoneProvisioningMode !== 'vapi_instant'" x-transition>
                            <label for="default_external_phone_provider" class="tc-field-label">Preferred carrier</label>
                            <select id="default_external_phone_provider" name="default_external_phone_provider" class="tc-input" x-model="defaultExternalPhoneProvider">
                                <template x-for="option in currentProviderOptions" :key="option.value">
                                    <option :value="option.value" x-text="option.label"></option>
                                </template>
                            </select>
                            @if($errors->first('default_external_phone_provider'))
                                <p class="tc-error">{{ $errors->first('default_external_phone_provider') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="tc-field" x-show="defaultPhoneProvisioningMode !== 'vapi_instant'" x-transition>
                        <label for="default_vapi_credential_id" class="tc-field-label">Default Vapi credential ID <span class="text-slate-500">Optional</span></label>
                        <input id="default_vapi_credential_id" name="default_vapi_credential_id" type="text" value="{{ old('default_vapi_credential_id', $workspace->default_vapi_credential_id) }}" class="tc-input" placeholder="Use one shared BYO phone credential for this workspace" />
                        <p class="tc-help">If this workspace will keep importing numbers through the same BYO credential, save it here once and we will prefill future number setups.</p>
                        @if($errors->first('default_vapi_credential_id'))
                            <p class="tc-error">{{ $errors->first('default_vapi_credential_id') }}</p>
                        @endif
                    </div>
                </section>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
                    <button type="submit" class="tc-btn-primary">Save settings</button>
                    <a href="{{ route('app.workspaces.index') }}" class="tc-btn-ghost">Cancel</a>
                </div>
            </form>
        </x-ui.panel>

        <x-ui.panel title="Current pilot stack" description="This is the strategy the workspace is currently optimized around.">
            <div class="space-y-4">
                <div class="tc-meta-card">
                    <div class="tc-label-eyebrow">{{ $pilotStack['title'] }}</div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $pilotStack['note'] }}</p>
                </div>

                <div class="tc-meta-card-white">
                    <div class="tc-label-eyebrow-tight">Telephony</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotStack['telephony'] }}</p>
                </div>

                <div class="tc-meta-card-white">
                    <div class="tc-label-eyebrow-tight">Speech stack</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotStack['transcriber'] }} + {{ $pilotStack['voice'] }}</p>
                </div>

                <div class="tc-meta-card-white">
                    <div class="tc-label-eyebrow-tight">Model layer</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotStack['llm'] }}</p>
                </div>

                <div class="tc-meta-card-white">
                    <div class="tc-label-eyebrow-tight">{{ $pilotPlaybook['title'] }}</div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotPlaybook['summary'] }}</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <div class="tc-label-eyebrow-tight">Recommended preset</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotPlaybook['recommended_preset'] }}</p>
                        </div>
                        <div>
                            <div class="tc-label-eyebrow-tight">Voice path</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $pilotPlaybook['recommended_voice_path'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="tc-meta-card-white">
                    <div class="tc-label-eyebrow-tight">Best demo calls</div>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                        @foreach($pilotPlaybook['demo_calls'] as $scenario)
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full tc-accent-fill"></span>
                                <span>{{ $scenario }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="tc-meta-card-white">
                    <div class="tc-label-eyebrow-tight">Rollout path</div>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                        @foreach($pilotPlaybook['rollout_steps'] as $step)
                            <li class="flex gap-3">
                                <span class="mt-2 h-2 w-2 rounded-full tc-accent-fill"></span>
                                <span>{{ $step }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="tc-meta-card">
                    <div class="tc-label-eyebrow-tight">What inherits these defaults</div>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                        <li>New assistants start from the workspace language and regional voice path.</li>
                        <li>Phone setup screens default to the workspace number strategy and carrier.</li>
                        <li>International pilots stay optional. Domestic workspaces still keep the faster instant-number path.</li>
                    </ul>
                </div>
            </div>
        </x-ui.panel>
    </div>
@endsection
