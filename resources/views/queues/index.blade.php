@extends('layouts.saas')

@section('title')
Queues
@endsection

@section('header')
Queues
@endsection

@section('content')
    <div class="space-y-4 max-w-3xl">
        <div class="bg-surface border border-slate-200 rounded-card p-6">
            <div class="flex items-center justify-between">
                <h2 class="tc-h3 mb-3">Queues</h2>
                <a href="#" class="text-sm text-primary">New queue</a>
            </div>
            <ul class="space-y-2 mt-3">
                @foreach($queues as $q)
                    <li class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $q->is_active ? 'bg-info-light text-info-fg' : 'bg-slate-100 text-slate-700' }}">{{ ucfirst($q->name) }}</span>
                            <div class="text-sm text-slate-700">Default: <span class="font-medium">{{ $q->default_priority ?? 'normal' }}</span></div>
                        </div>
                        <div>
                            <a href="#" class="text-sm text-primary">Edit</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
