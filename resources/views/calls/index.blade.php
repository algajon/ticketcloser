@extends('layouts.saas')

@section('title')
Call Log
@endsection

@section('header')
Call Log
@endsection

@section('content')
    <div class="space-y-4 max-w-6xl">
        <div class="bg-surface border border-slate-200 rounded-card p-6">
            <h2 class="tc-h3 mb-3">Recent calls</h2>
            <div class="divide-y divide-slate-100">
                @foreach($events as $e)
                    <div class="py-3 flex items-start justify-between gap-6">
                        <div class="flex-1">
                            <div class="text-sm font-medium">From: {{ $e->from_number ?? '-' }} → {{ $e->to_number ?? '-' }}</div>
                            <div class="text-xs text-muted">{{ $e->created_at->diffForHumans() }} • {{ $e->duration_seconds ?? '-' }}s</div>
                            @if($e->transcript)
                                <div class="mt-2 text-sm text-slate-700 whitespace-pre-wrap max-h-40 overflow-auto">{{ Str::limit($e->transcript, 800) }}</div>
                            @endif
                        </div>
                        <div class="w-36 text-sm text-slate-600 text-right">
                            <div>Queue: {{ $e->queue_id ?? '-' }}</div>
                            @if($e->recording_url)
                                <a href="{{ $e->recording_url }}" class="text-primary">Recording</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
