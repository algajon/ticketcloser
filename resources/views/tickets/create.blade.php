@extends('layouts.saas')

@section('title', 'New ' . ($workspace->case_label ?? 'Ticket'))

@section('header')
    New {{ $workspace->case_label ?? 'Ticket' }}
@endsection

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="tc-page-header">
            <h1>Create {{ $workspace->case_label ?? 'Ticket' }}</h1>
            <p>Fill in the details below to create a new support case.</p>
        </div>

        <div class="tc-card p-6">
            <form method="POST" action="{{ route('app.cases.store', $workspace->slug) }}" class="space-y-5"
                x-data="{ loading: false }" @submit="loading = true">
                @csrf

                {{-- Title --}}
                <div class="space-y-1.5">
                    <label for="title" class="block text-sm font-medium text-slate-800">Title</label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" class="tc-input" required
                        maxlength="200" placeholder="Brief summary of the issue">
                    @error('title')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="space-y-1.5">
                    <label for="description" class="block text-sm font-medium text-slate-800">Description</label>
                    <textarea name="description" id="description" rows="5" class="tc-input"
                        placeholder="Full details…">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Priority --}}
                <div class="space-y-1.5">
                    <label for="priority" class="block text-sm font-medium text-slate-800">Priority</label>
                    <select name="priority" id="priority" class="tc-input">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                    @error('priority')
                        <p class="text-xs text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="flex items-center gap-4 pt-2">
                    <button type="submit" class="tc-btn-primary" x-bind:disabled="loading">
                        <span x-text="loading ? 'Creating…' : 'Create {{ $workspace->case_label ?? 'Ticket' }}'">Create
                            {{ $workspace->case_label ?? 'Ticket' }}</span>
                    </button>
                    <a href="{{ route('app.cases.index', $workspace->slug) }}"
                        class="text-sm text-slate-500 hover:text-slate-700 hover:underline transition-colors">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection