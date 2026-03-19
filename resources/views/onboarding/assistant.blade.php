@extends('layouts.saas')

@section('title')
    ticketcloser • Assistant
@endsection

@section('header')
    Assistant Setup
@endsection

@section('content')
    @php
        $configs = $configs ?? collect();
        $config = $config ?? $configs->first();
        $phone = $config ? $workspace->phoneNumbers()->where('assistant_id', $config->id)->first() : $workspace->phoneNumbers()->first();
    @endphp

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="tc-card p-6">
                <div class="tc-page-header">
                    <h1>Voice assistant</h1>
                    <p>Configure your assistant then click <strong>Save &amp; Sync</strong> to push to Vapi.</p>
                </div>

                <form class="space-y-5" method="POST" action="{{ route('app.assistant.store', $workspace) }}"
                    x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Select assistant</label>
                        <select name="assistant_id" class="tc-input mt-2">
                            <option value="">Create new assistant</option>
                            @foreach($configs as $c)
                                <option value="{{ $c->id }}" @selected((int) old('assistant_id', $config?->id) === $c->id)>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="asst-name" class="block text-sm font-medium text-slate-800">Assistant name</label>
                        <input id="asst-name" name="name" value="{{ old('name', $config?->name) }}"
                            placeholder="e.g. Support Agent" required class="tc-input" />
                        @if($errors->first('name'))
                        <p class="text-xs text-danger">{{ $errors->first('name') }}</p>@endif
                    </div>

                    <div class="space-y-1.5">
                        <label for="asst-prompt" class="block text-sm font-medium text-slate-800">System prompt</label>
                        <p class="text-xs text-slate-500 mt-1">Instructions for how the assistant handles calls.</p>
                        <textarea id="asst-prompt" name="system_prompt" rows="14" class="tc-input mt-2"
                            placeholder="You are a support agent for…">{{ old('system_prompt', $config?->system_prompt) }}</textarea>
                        @if($errors->first('system_prompt'))
                        <p class="text-xs text-danger">{{ $errors->first('system_prompt') }}</p>@endif
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label for="voice_provider" class="block text-sm font-medium text-slate-800">Voice
                                provider</label>
                            <input id="voice_provider" name="voice_provider"
                                value="{{ old('voice_provider', $config?->voice_provider) }}" placeholder="e.g. vapi"
                                class="tc-input mt-2" />
                        </div>

                        <div class="space-y-1.5">
                            <label for="voice_id" class="block text-sm font-medium text-slate-800">Voice</label>
                            @if(!empty($voices))
                                <select id="voice_id" name="voice_id" class="tc-input mt-2">
                                    <option value="">Choose a voice</option>
                                    @foreach($voices as $v)
                                        <option value="{{ $v['id'] }}" @selected(old('voice_id', $config?->voice_id) === $v['id'])>
                                            {{ $v['name'] }} ({{ $v['language'] ?? $v['locale'] ?? 'unknown' }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input id="voice_id" name="voice_id" value="{{ old('voice_id', $config?->voice_id) }}"
                                    placeholder="voice id" class="tc-input mt-2" />
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit" class="tc-btn-primary">Save &amp; Sync to Vapi</button>
                        <a href="{{ route('app.phone_numbers.index', $workspace) }}" class="tc-btn-secondary">Next: Phone
                            Numbers →</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-4">
            <div class="tc-card p-6">
                <h3 class="tc-h3">Setup status</h3>
                <div class="mt-3">
                    <p class="text-sm">Assistant:
                        {{ $config?->vapi_assistant_id ? 'Synced' : ($config?->name ? 'Configured' : 'Not configured') }}
                    </p>
                    <p class="text-sm">Phone: {{ $phone?->e164 ? $phone->e164 : 'Not provisioned' }}</p>
                </div>
            </div>

            @if($config?->vapi_assistant_id)
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">Your assistant is live in
                    Vapi. Any prompt changes take effect on the next sync.</div>
            @endif
        </div>
    </div>

@endsection