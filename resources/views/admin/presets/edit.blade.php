@extends('layouts.saas')

@section('title', 'Admin • Edit Preset')

@section('header', 'Edit Preset: ' . $preset->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('admin.presets.index') }}"
            class="text-sm font-medium text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
            ← Back to presets
        </a>
    </div>

    <div class="max-w-4xl">
        <form method="POST" action="{{ route('admin.presets.update', $preset) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="tc-card p-6">
                <h2 class="text-base font-bold text-slate-900 mb-4">Preset Detail</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Preset Name</label>
                        <input type="text" name="name" value="{{ old('name', $preset->name) }}" class="tc-input" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes', $preset->notes) }}" class="tc-input">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Vapi Payload (JSON)</label>
                        <p class="text-xs text-slate-500 mb-2">Edit the raw JSON template for this preset. It must be valid
                            JSON.</p>
                        <textarea name="vapi_payload_json" rows="20" class="tc-input font-mono text-xs p-3"
                            style="min-height:300px">{{ old('vapi_payload_json', json_encode($preset->vapi_payload_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        @error('vapi_payload_json')
                            <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end items-center gap-3">
                <a href="{{ route('admin.presets.index') }}" class="tc-btn-ghost">Cancel</a>
                <button type="submit" class="tc-btn-primary">Save Template</button>
            </div>
        </form>
    </div>
@endsection