@extends('layouts.saas')

@section('title', 'tickIt - Phone Numbers')
@section('header_eyebrow', 'Call routing')
@section('header', 'Phone numbers')
@section('header_description', 'Connect a number so callers reach the right assistant.')

@section('content')
    @php
        $configs = $configs ?? collect();
        $phoneNumbersLockedForFreePlan = $phoneNumbersLockedForFreePlan ?? false;
        $existingNumberCountryOptions = collect($existingNumberCountryOptions ?? []);
        $activationCountdownIso = $activationCountdownEndsAt?->toIso8601String();
        $workspaceIsFree = $workspace->isFreePlan() && ! $workspace->bypassesPlanLimits();
        $showDeveloperPhoneIds = ! $workspaceIsFree;
        $provisioningMode = old('provisioning_mode', $phone?->provisioning_mode ?: $workspace->preferredPhoneSetupMode());
        $externalProvider = old('external_provider', $phone?->external_provider ?: $workspace->preferredExternalPhoneProvider());
        $vapiCredentialId = old('vapi_credential_id', $phone?->vapi_credential_id ?: $workspace->default_vapi_credential_id);
        $existingNumberCountry = old(
            'existing_number_country',
            $defaultExistingNumberCountry ?? \App\Support\RegionalPilotStackCatalog::inferExistingNumberCountry(
                $phone?->forwarding_number,
                $workspace->primaryMarket()
            )
        );
        $existingNumberCountryCatalog = $existingNumberCountryOptions
            ->mapWithKeys(fn (array $option) => [$option['value'] => $option])
            ->all();
        $workspaceHasDefaultVapiCredential = filled($workspace->default_vapi_credential_id);
        $autoForwardingTarget = old(
            'auto_forwarding_target',
            $workspace->primaryMarket() !== \App\Support\RegionalPilotStackCatalog::UAE && blank($phone?->e164) ? '1' : '0'
        );
    @endphp

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(340px,0.9fr)]">
        <x-ui.panel title="Phone number" description="This is the number callers reach.">
            @if(!$config?->vapi_assistant_id)
                <div class="mb-5 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm leading-6 text-amber-800">
                    Create and sync an assistant first.
                </div>
            @endif

            <div class="mb-5 rounded-[1.25rem] border border-slate-200 bg-slate-50/85 p-4">
                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $pilotStack['title'] }}</div>
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
            </div>

            <div
                @if($activationCountdownIso)
                    x-data="{
                        targetMs: Date.parse('{{ $activationCountdownIso }}'),
                        remainingMs: 0,
                        init() {
                            const tick = () => {
                                this.remainingMs = Math.max(0, this.targetMs - Date.now());
                                if (this.remainingMs > 0) {
                                    window.setTimeout(tick, 1000);
                                }
                            };
                            tick();
                        },
                        formatted() {
                            const totalSeconds = Math.max(0, Math.ceil(this.remainingMs / 1000));
                            const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                            const seconds = String(totalSeconds % 60).padStart(2, '0');
                            return minutes + ':' + seconds;
                        },
                    }"
                @endif
            >
                @if($phone?->e164)
                    <div class="rounded-[1.35rem] border border-slate-200 bg-slate-50/80 p-5">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Live number</div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ $phone->e164 }}</div>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            @if($activationCountdownIso)
                                <x-ui.badge tone="warning" x-show="remainingMs > 0">Activating</x-ui.badge>
                                <x-ui.badge tone="success" x-show="remainingMs <= 0" x-cloak>Active</x-ui.badge>
                            @else
                                <x-ui.badge tone="success">Active</x-ui.badge>
                            @endif
                        </div>

                        @if($showDeveloperPhoneIds && $phone->vapi_phone_number_id)
                            <div x-data="{ copied: false }" class="mt-5 rounded-[1.2rem] border border-slate-200 bg-white p-4">
                                <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Vapi phone ID</div>
                                <div class="mt-3 flex items-center gap-3">
                                    <code class="min-w-0 flex-1 truncate text-xs text-slate-700">{{ $phone->vapi_phone_number_id }}</code>
                                    <button type="button" class="tc-btn-ghost !px-3 !py-2 text-xs" @click="navigator.clipboard.writeText('{{ $phone->vapi_phone_number_id }}').then(() => { copied = true; setTimeout(() => copied = false, 1800) })">
                                        <span x-show="!copied">Copy</span>
                                        <span x-show="copied" x-cloak>Copied</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    @if($activationCountdownIso)
                        <div class="mt-4 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm leading-6 text-amber-800">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-amber-700">Activation countdown</div>
                            <div class="mt-2">
                            This number was just added. Give it up to
                            <span class="font-semibold tabular-nums" x-text="formatted()">03:00</span>
                            to finish activating before you test it.
                            </div>
                        </div>
                    @endif

                    @if($phone->forwarding_number)
                        <div class="mt-4 rounded-[1.25rem] border border-slate-200 bg-white px-4 py-4">
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Forwarding path</div>
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Current business line</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-950">{{ $phone->forwarding_number }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Forward to tickIt</div>
                                    <div class="mt-1 text-sm font-semibold text-slate-950">{{ $phone->e164 }}</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($phone->e164 && preg_match('/^\+?\d{10,}$/', $phone->e164))
                        <div class="mt-4 rounded-[1.25rem] border border-slate-200 bg-slate-50/80 px-4 py-4 text-sm leading-6 text-slate-700">
                            Want to keep your current number? Forward it to <span class="font-semibold text-slate-950">{{ $phone->e164 }}</span> once the assistant is live.
                        </div>
                    @endif
                @elseif($phone?->forwarding_number)
                    <div class="rounded-[1.35rem] border border-slate-200 bg-slate-50/80 p-5">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-slate-500">Saved line</div>
                        <div class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">{{ $phone->forwarding_number }}</div>
                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <x-ui.badge tone="warning">Needs import or forwarding</x-ui.badge>
                            @if($phone->external_provider)
                                <x-ui.badge tone="default">{{ strtoupper($phone->external_provider) }}</x-ui.badge>
                            @endif
                            @if($phone->vapi_credential_id)
                                <x-ui.badge tone="info">Credential saved</x-ui.badge>
                            @endif
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-600">We saved the number strategy. Connect this line through your carrier, then import or forward it so tickIt can answer live calls.</p>
                    </div>
                @else
                    <x-ui.empty-state title="No number yet" description="Add a number so you can test real calls." />
                @endif
            </div>
        </x-ui.panel>

        <x-ui.panel title="{{ $phone?->vapi_phone_number_id ? 'Update number' : 'Add a number' }}" description="Choose whether this assistant should use a new test number, import an existing line, or become the forwarding target for your live number.">
            @if($phoneNumbersLockedForFreePlan)
                <div class="mb-5 rounded-[1.25rem] border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm leading-6 text-amber-900">
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-amber-700">Upgrade required</div>
                    <div class="mt-2">
                        Free workspaces cannot assign a live phone number to an assistant. Upgrade first, then connect, import, or forward a number here.
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('app.billing.plans') }}" class="tc-btn-primary !px-4 !py-2 text-sm">Upgrade to connect a number</a>
                    </div>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('app.phone_numbers.store', $workspace) }}"
                class="space-y-5"
                x-data="{
                    loading: false,
                    phoneNumbersLocked: @js($phoneNumbersLockedForFreePlan),
                    provisioningMode: @js($provisioningMode),
                    externalProvider: @js($externalProvider),
                    vapiCredentialId: @js($vapiCredentialId),
                    existingNumberCountry: @js($existingNumberCountry),
                    workspaceHasDefaultVapiCredential: @js($workspaceHasDefaultVapiCredential),
                    autoForwardingTarget: @js((string) $autoForwardingTarget === '1'),
                    numberCatalog: @js($existingNumberCountryCatalog),
                    isInstantMode() {
                        return this.provisioningMode === 'vapi_instant';
                    },
                    isImportMode() {
                        return this.provisioningMode === 'external_provider';
                    },
                    isForwardMode() {
                        return this.provisioningMode === 'existing_business_number';
                    },
                    hasAnyCredential() {
                        return this.workspaceHasDefaultVapiCredential || String(this.vapiCredentialId || '').trim() !== '';
                    },
                    countryConfig() {
                        return this.numberCatalog[this.existingNumberCountry] || this.numberCatalog.us || Object.values(this.numberCatalog)[0] || {};
                    },
                    existingNumberLabel() {
                        if (this.isImportMode()) {
                            return 'Number to import';
                        }

                        if (this.isForwardMode()) {
                            return 'Current business number';
                        }

                        return 'Your existing phone number';
                    },
                    existingNumberHelp() {
                        if (this.isImportMode()) {
                            return this.countryConfig().import_help || 'Paste the exact number you want to import into Vapi.';
                        }

                        if (this.isForwardMode()) {
                            return this.countryConfig().forwarding_help || 'Save the number you want to keep so you can forward it later.';
                        }

                        return 'Optional. We will show you where to forward it.';
                    },
                    existingNumberPlaceholder() {
                        return this.countryConfig().placeholder || '+1 415 555 0123';
                    },
                    providerHelp() {
                        return this.countryConfig().provider_help || 'Use the carrier that already owns this number.';
                    },
                    credentialHelp() {
                        if (this.isImportMode()) {
                            return this.hasAnyCredential()
                                ? 'We will use the saved Vapi BYO credential to import this number and attach it to the selected assistant.'
                                : 'Paste a Vapi BYO credential here, or save one once in workspace settings for one-click imports.';
                        }

                        return 'Optional. Paste a Vapi BYO credential only if this line is already connected through Vapi and you want to attach it directly.';
                    },
                    selectSetup(mode) {
                        this.provisioningMode = mode;

                        if (mode === 'existing_business_number' && !this.autoForwardingTarget) {
                            this.autoForwardingTarget = true;
                        }
                    },
                    submitLabel() {
                        if (this.phoneNumbersLocked) {
                            return 'Upgrade to connect a number';
                        }

                        if (this.loading) {
                            return 'Saving...';
                        }

                        if (this.isImportMode()) {
                            return this.hasAnyCredential() ? 'Import number and attach assistant' : 'Save import plan';
                        }

                        if (this.isForwardMode()) {
                            return this.autoForwardingTarget ? 'Create forwarding target' : 'Save forwarding plan';
                        }

                        return @js($phone?->vapi_phone_number_id ? 'Save number' : 'Create test number');
                    },
                }"
                @submit="loading = true"
            >
                @csrf

                <div class="tc-field">
                    <label for="assistant_id" class="tc-field-label">Assistant</label>
                    <select id="assistant_id" name="assistant_id" class="tc-input"
                        onchange="window.location.href = '{{ route('app.phone_numbers.index', $workspace) }}?assistant_id=' + this.value"
                        @disabled($configs->isEmpty() || $phoneNumbersLockedForFreePlan)>
                        @forelse($configs as $assistantOption)
                            <option value="{{ $assistantOption->id }}" @selected((string) old('assistant_id', $config?->id) === (string) $assistantOption->id)>
                                {{ $assistantOption->name }}
                            </option>
                        @empty
                            <option value="">No assistants available</option>
                        @endforelse
                    </select>
                    <p class="tc-help">Choose which assistant should answer this number.</p>
                </div>

                <div class="tc-field">
                    <label class="tc-field-label">How should this assistant take calls?</label>
                    <input type="hidden" name="provisioning_mode" x-model="provisioningMode" />
                    <div class="mt-2 space-y-2">
                        @foreach($phoneSetupOptions as $option)
                            <button
                                type="button"
                                class="flex w-full items-start justify-between gap-3 rounded-[1rem] border px-4 py-4 text-left transition"
                                :class="provisioningMode === '{{ $option['value'] }}' ? 'tc-accent-card-active' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80'"
                                @click="if (!phoneNumbersLocked) selectSetup('{{ $option['value'] }}')"
                                @disabled($phoneNumbersLockedForFreePlan)>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <div class="text-sm font-semibold text-slate-950">{{ $option['label'] }}</div>
                                        @if($option['recommended'] ?? false)
                                            <span class="tc-badge tc-accent-badge">Recommended</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $option['description'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.14em]"
                                    :class="provisioningMode === '{{ $option['value'] }}' ? 'tc-accent-badge-strong' : 'border-slate-200 bg-white text-slate-500'">
                                    <span x-text="provisioningMode === '{{ $option['value'] }}' ? 'Selected' : 'Choose'"></span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[1.25rem] border border-emerald-200 bg-emerald-50/80 px-4 py-4 text-sm leading-6 text-emerald-900" x-show="isImportMode() && hasAnyCredential()" x-transition>
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-emerald-700">Ready to import</div>
                    <div class="mt-2">
                        This assistant can import a US, German, UAE, or other existing number directly into Vapi as soon as you save it.
                    </div>
                </div>

                <div class="rounded-[1.25rem] border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm leading-6 text-amber-900" x-show="isImportMode() && !hasAnyCredential()" x-transition>
                    <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-amber-700">One-click import tip</div>
                    <div class="mt-2">
                        Add a workspace Vapi BYO credential once in
                        <a href="{{ route('app.workspaces.settings', $workspace) }}" class="font-semibold underline decoration-amber-300 underline-offset-2">workspace settings</a>
                        and future German or US number imports become a single save here.
                    </div>
                </div>

                <div class="tc-field" x-show="provisioningMode === 'vapi_instant'" x-transition>
                    <label for="area-code" class="tc-field-label">Preferred area code</label>
                    <input id="area-code" name="area_code" placeholder="e.g. 415" maxlength="3" inputmode="numeric" pattern="[0-9]{3}"
                        value="{{ old('area_code') }}" class="tc-input" @disabled(!$config?->vapi_assistant_id || $phoneNumbersLockedForFreePlan) />
                    <p class="tc-help">Optional. Leave blank to use any available US number.</p>
                    @if($errors->first('area_code'))
                        <p class="tc-error">{{ $errors->first('area_code') }}</p>
                    @endif
                </div>

                <div class="tc-field" x-show="!isInstantMode()" x-transition>
                    <label class="tc-field-label">Existing number country</label>
                    <input type="hidden" name="existing_number_country" x-model="existingNumberCountry" />
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        @foreach($existingNumberCountryOptions as $countryOption)
                            <button
                                type="button"
                                class="rounded-[1rem] border px-4 py-3 text-left transition"
                                :class="existingNumberCountry === '{{ $countryOption['value'] }}' ? 'tc-accent-card-active' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80'"
                                @click="if (!phoneNumbersLocked) existingNumberCountry = '{{ $countryOption['value'] }}'"
                                @disabled($phoneNumbersLockedForFreePlan)>
                                <div class="text-sm font-semibold text-slate-950">{{ $countryOption['label'] }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ $countryOption['placeholder'] }}</div>
                            </button>
                        @endforeach
                    </div>
                    @if($errors->first('existing_number_country'))
                        <p class="tc-error">{{ $errors->first('existing_number_country') }}</p>
                    @endif
                </div>

                <div class="tc-field">
                    <label for="forwarding_number" class="tc-field-label" x-text="existingNumberLabel()">Your existing phone number</label>
                    <input id="forwarding_number" name="forwarding_number"
                        :placeholder="existingNumberPlaceholder()"
                        value="{{ old('forwarding_number', $phone?->forwarding_number) }}" class="tc-input" @disabled(!$config?->vapi_assistant_id || $phoneNumbersLockedForFreePlan) />
                    <p class="tc-help" x-text="existingNumberHelp()">Optional. We will show you where to forward it.</p>
                    @if($errors->first('forwarding_number'))
                        <p class="tc-error">{{ $errors->first('forwarding_number') }}</p>
                    @endif
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 px-4 py-4" x-show="provisioningMode === 'existing_business_number'" x-transition>
                    <input type="hidden" name="auto_forwarding_target" value="0" />
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="auto_forwarding_target" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" x-model="autoForwardingTarget" @disabled(!$config?->vapi_assistant_id || $phoneNumbersLockedForFreePlan)>
                        <span>
                            <span class="text-sm font-semibold text-slate-950">Create the forwarding destination now</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">
                                We will provision a tickIt number right away so your team can forward the existing business line without waiting for a separate import step.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="tc-field" x-show="provisioningMode === 'external_provider'" x-transition>
                    <label for="external_provider" class="tc-field-label">Current carrier / provider</label>
                    <select id="external_provider" name="external_provider" class="tc-input" x-model="externalProvider" @disabled(!$config?->vapi_assistant_id || $phoneNumbersLockedForFreePlan)>
                        @foreach($externalProviderOptions as $providerOption)
                            <option value="{{ $providerOption['value'] }}">{{ $providerOption['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="tc-help" x-text="providerHelp()">
                        Pick the carrier that owns the number you want to import.
                    </p>
                </div>

                <div class="tc-field" x-show="provisioningMode !== 'vapi_instant'" x-transition>
                    <label for="vapi_credential_id" class="tc-field-label">Vapi import credential <span class="text-slate-500">Optional</span></label>
                    <input id="vapi_credential_id" name="vapi_credential_id" type="text" class="tc-input" x-model="vapiCredentialId" placeholder="Paste a Vapi BYO credential ID if you want to override the workspace default" @disabled(!$config?->vapi_assistant_id || $phoneNumbersLockedForFreePlan) />
                    <p class="tc-help" x-text="credentialHelp()">Optional import help text.</p>
                    <p class="tc-help" x-show="workspaceHasDefaultVapiCredential && String(vapiCredentialId || '').trim() === ''" x-cloak>
                        A saved workspace credential will be used automatically if you leave this blank.
                    </p>
                    @if($errors->first('vapi_credential_id'))
                        <p class="tc-error">{{ $errors->first('vapi_credential_id') }}</p>
                    @endif
                </div>

                <button type="submit" class="tc-btn-primary w-full justify-center" x-bind:disabled="loading || phoneNumbersLocked || {{ $config?->vapi_assistant_id ? 'false' : 'true' }}">
                    <span x-text="submitLabel()">{{ $phone?->vapi_phone_number_id ? 'Save number' : 'Create test number' }}</span>
                </button>
            </form>
        </x-ui.panel>
    </div>
@endsection
