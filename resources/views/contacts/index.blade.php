@extends('layouts.saas')

@section('title')
Contacts
@endsection

@section('header')
Contacts
@endsection

@section('content')
    <div class="space-y-4 max-w-4xl">
        <div class="bg-surface border border-slate-200 rounded-card p-6">
            <h2 class="tc-h3 mb-3">Contacts</h2>
            <ul class="divide-y divide-slate-100">
                @foreach($contacts as $c)
                    <li class="py-3 flex items-center justify-between">
                        <div>
                            <div class="font-semibold">{{ $c->name ?? 'N/A' }}</div>
                            <div class="text-sm text-muted">{{ $c->phone_e164 ?? 'N/A' }} • {{ $c->email ?? 'N/A' }}</div>
                        </div>
                        <div class="text-sm text-slate-600 text-right">{{ $c->property_code ?? '' }} {{ $c->unit ? '#'.$c->unit : '' }}</div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
