@extends('layouts.saas')

@section('title', 'tickIt - Phone Numbers')
@section('header_eyebrow', 'Call routing')
@section('header', 'Phone numbers')
@section('header_description', 'Connect a number so callers reach the right assistant.')

@section('content')
    @php
        $configs = $configs ?? collect();
        $activationCountdownIso = $activationCountdownEndsAt?->toIso8601String();
        $workspaceIsFree = $workspace->isFreePlan() && ! $workspace->bypassesPlanLimits();
        $showDeveloperPhoneIds = ! $workspaceIsFree;
        $provisioningMode = old('provisioning_mode', $phone?->provisioning_mode ?: $workspace->preferredPhoneSetupMode());
        $externalProvider = old('external_provider', $phone?->external_provider ?: $workspace->preferredExternalPhoneProvider());
        $vapiCredentialId = old('vapi_credential_id', $phone?->vapi_credential_id ?: $workspace->default_vapi_credential_id);
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

        <x-ui.panel title="{{ $phone?->vapi_phone_number_id ? 'Update number' : 'Add a number' }}" description="Pick how this assistant should receive live calls, whether that is an instant number or your own local line.">
            <form method="POST" action="{{ route('app.phone_numbers.store', $workspace) }}" class="space-y-5" x-data="{ loading: false, provisioningMode: @js($provisioningMode), externalProvider: @js($externalProvider), vapiCredentialId: @js($vapiCredentialId), autoForwardingTarget: @js((string) $autoForwardingTarget === '1') }" @submit="loading = true">
                @csrf

                <div class="tc-field">
                    <label for="assistant_id" class="tc-field-label">Assistant</label>
                    <select id="assistant_id" name="assistant_id" class="tc-input"
                        onchange="window.location.href = '{{ route('app.phone_numbers.index', $workspace) }}?assistant_id=' + this.value"
                        @disabled($configs->isEmpty())>
                        @forelse($configs as $assistantOption)
                            <option value="{{ $assistantOption->id }}" @selected((string) old('assistant_id', $config?->id) === (string) $assistantOption->id)>
                                {{ $assistantOption->name }}
                            </option>
                        @empty
                            <option value="">No assistants available</option>
                        @endforelse
                    </select>
                    <p class="tc-help">Workspace defaults for language, market, and number setup carry through here automatically.</p>
                </div>

                <div class="tc-field">
                    <label class="tc-field-label">Phone setup</label>
                    <input type="hidden" name="provisioning_mode" x-model="provisioningMode" />
                    <div class="mt-2 space-y-2">
                        @foreach($phoneSetupOptions as $option)
                            <button
                                type="button"
                                class="flex w-full items-start justify-between gap-3 rounded-[1rem] border px-4 py-4 text-left transition"
                                :class="provisioningMode === '{{ $option['value'] }}' ? 'tc-accent-card-active' : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50/80'"
                                @click="provisioningMode = '{{ $option['value'] }}'">
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

                <div class="tc-field" x-show="provisioningMode === 'vapi_instant'" x-transition>
                    <label for="area-code" class="tc-field-label">Preferred area code</label>
                    <input id="area-code" name="area_code" placeholder="e.g. 415" maxlength="3" inputmode="numeric" pattern="[0-9]{3}"
                        value="{{ old('area_code') }}" class="tc-input" @if(!$config?->vapi_assistant_id) disabled @endif />
                    <p class="tc-help">Optional. Leave blank to use any available US number.</p>
                    @if($errors->first('area_code'))
                        <p class="tc-error">{{ $errors->first('area_code') }}</p>
                    @endif
                </div>

                <div class="tc-field">
                    <label for="forwarding_number" class="tc-field-label" x-text="provisioningMode === 'vapi_instant' ? 'Your existing phone number' : 'Business phone number'">Your existing phone number</label>
                    <input id="forwarding_number" name="forwarding_number" placeholder="e.g. +1 555-0199"
                        value="{{ old('forwarding_number', $phone?->forwarding_number) }}" class="tc-input" @if(!$config?->vapi_assistant_id) disabled @endif />
                    <p class="tc-help" x-text="provisioningMode === 'vapi_instant' ? 'Optional. We will show you where to forward it.' : 'Save the line you want to keep. We will use it for your import or forwarding setup.'">Optional. We will show you where to forward it.</p>
                    @if($errors->first('forwarding_number'))
                        <p class="tc-error">{{ $errors->first('forwarding_number') }}</p>
                    @endif
                </div>

                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50/80 px-4 py-4" x-show="provisioningMode === 'existing_business_number'" x-transition>
                    <input type="hidden" name="auto_forwarding_target" value="0" />
                    <label class="flex items-start gap-3">
                        <input type="checkbox" name="auto_forwarding_target" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" x-model="autoForwardingTarget" @if(!$config?->vapi_assistant_id) disabled @endif>
                        <span>
                            <span class="text-sm font-semibold text-slate-950">Create the forwarding destination now</span>
                            <span class="mt-1 block text-sm leading-6 text-slate-600">
                                We will provision a tickIt number right away so your team can forward the existing business line without waiting for a separate import step.
                            </span>
                        </span>
                    </label>
                </div>

                <div class="tc-field" x-show="provisioningMode === 'external_provider'" x-transition>
                    <label for="external_provider" class="tc-field-label">Carrier / provider</label>
                    <select id="external_provider" name="external_provider" class="tc-input" x-model="externalProvider" @if(!$config?->vapi_assistant_id) disabled @endif>
                        @foreach($externalProviderOptions as $providerOption)
                            <option value="{{ $providerOption['value'] }}">{{ $providerOption['label'] }}</option>
                        @endforeach
                    </select>
                    <p class="tc-help">
                        @if($workspace->primaryMarket() === \App\Support\RegionalPilotStackCatalog::UAE)
                            For UAE pilots, Telnyx is the cleanest path for a local number you can later import into Vapi.
                        @else
                            Pick the carrier you want this workspace to use when you are not creating an instant tickIt number.
                        @endif
                    </p>
                </div>

                <div class="tc-field" x-show="provisioningMode !== 'vapi_instant'" x-transition>
                    <label for="vapi_credential_id" class="tc-field-label">Vapi credential ID <span class="text-slate-500">Optional</span></label>
                    <input id="vapi_credential_id" name="vapi_credential_id" type="text" class="tc-input" x-model="vapiCredentialId" placeholder="Paste the Vapi BYO credential ID" @if(!$config?->vapi_assistant_id) disabled @endif />
                    <p class="tc-help" x-text="provisioningMode === 'external_provider'
                        ? 'If you already created a BYO phone credential in Vapi, paste it here and we will try to import the number automatically.'
                        : 'If your existing number is already connected through a Vapi BYO credential, paste it here so we can attach it to this assistant.'">Optional import help text.</p>
                    @if($errors->first('vapi_credential_id'))
                        <p class="tc-error">{{ $errors->first('vapi_credential_id') }}</p>
                    @endif
                </div>

                <button type="submit" class="tc-btn-primary w-full justify-center" x-bind:disabled="loading || {{ $config?->vapi_assistant_id ? 'false' : 'true' }}">
                    <span x-text="loading ? 'Saving...' : (provisioningMode === 'vapi_instant' ? '{{ $phone?->vapi_phone_number_id ? 'Save number' : 'Add number' }}' : (provisioningMode === 'existing_business_number' && autoForwardingTarget ? 'Create forwarding target' : 'Save setup'))">{{ $phone?->vapi_phone_number_id ? 'Save number' : 'Add number' }}</span>
                </button>
            </form>
        </x-ui.panel>
    </div>
@endsection
