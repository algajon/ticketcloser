@extends('layouts.saas')

@section('title')
    ticketcloser • Phone Numbers
@endsection

@section('header')
    Phone Numbers
@endsection

@section('content')
    @php
        $configs = $configs ?? collect();
    @endphp

    @if(!$config?->vapi_assistant_id)
        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800 mb-6">
            <div class="font-medium mb-1">Create the assistant first</div>
            <div>You need to sync an assistant to Vapi before you can provision a phone number.</div>
            <div class="mt-2"><a href="{{ route('app.assistant.edit', $workspace) }}"
                    class="inline-block font-semibold underline">Set up assistant →</a></div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">

        <div class="tc-card p-6">
            <p class="tc-small uppercase tracking-wide font-medium mb-3">Connected number</p>

            @if($phone?->e164)
                <p class="text-3xl font-semibold tracking-tight tabular-nums text-slate-900">{{ $phone->e164 }}</p>
                <div class="mt-3"><span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success-light text-success-fg">Active</span>
                </div>
                @if($phone->vapi_phone_number_id)
                    <div class="mt-4">
                        <div x-data="{ copied: false }"
                            class="group flex items-center gap-2 rounded-xl bg-slate-50 border border-slate-200 px-3 py-2 text-sm font-mono">
                            <span class="flex-1 truncate text-slate-700 select-all">{{ $phone->vapi_phone_number_id }}</span>
                            <button
                                @click="navigator.clipboard.writeText('{{ $phone->vapi_phone_number_id }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                class="flex-shrink-0 text-xs text-muted hover:text-slate-800 transition-colors">Copy</button>
                        </div>
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                    <h3 class="tc-h3 text-slate-700">No number yet</h3>
                    <p class="mt-1.5 text-sm text-muted max-w-sm">Once provisioned, your Vapi US number appears here and callers
                        are routed to your assistant.</p>
                </div>
            @endif
        </div>

        <div class="tc-card p-6">
            <div class="tc-page-header">
                <h1>{{ $phone?->vapi_phone_number_id ? 'Re-sync configuration' : 'Provision a US number' }}</h1>
                <p>Vapi free numbers are US only (+1). Provisioning may take a few seconds.</p>
            </div>

            <form method="POST" action="{{ route('app.phone_numbers.store', $workspace) }}" class="space-y-4"
                x-data="{ loading: false }" @submit="loading = true">
                @csrf

                <div class="space-y-1.5">
                    <label for="assistant_id" class="block text-sm font-medium text-slate-800">Assistant</label>
                    <select id="assistant_id" name="assistant_id" class="tc-input mt-2"
                        onchange="window.location.href = '{{ route('app.phone_numbers.index', $workspace) }}?assistant_id=' + this.value">
                        @foreach($configs as $c)
                            <option value="{{ $c->id }}" @selected(old('assistant_id') == $c->id || ($config && $config->id === $c->id))>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="area-code" class="block text-sm font-medium text-slate-800">Area code</label>
                    <div class="text-xs text-muted">3-digit US area code, e.g. 415 or 212. Optional, leave blank for any
                        area.</div>
                    <input id="area-code" name="area_code" placeholder="e.g. 415" maxlength="3" inputmode="numeric"
                        pattern="[0-9]{3}" value="{{ old('area_code') }}" class="tc-input mt-2"
                        @if(!$config?->vapi_assistant_id) disabled @endif />
                    @if($errors->first('area_code'))
                    <p class="text-xs text-danger" role="alert">{{ $errors->first('area_code') }}</p>@endif
                </div>

                <button type="submit" class="tc-btn-primary w-full"
                    x-bind:disabled="loading || {{ $config?->vapi_assistant_id ? 'false' : 'true' }}">
                    <span
                        x-text="loading ? 'Provisioning…' : '{{ $phone?->vapi_phone_number_id ? 'Sync configuration' : 'Provision number' }}'">{{ $phone?->vapi_phone_number_id ? 'Sync configuration' : 'Provision number' }}</span>
                </button>
            </form>
        </div>

    </div>

@endsection