@extends('layouts.saas')

@section('title', 'ticketcloser • New Workspace')
@section('header', 'Create Workspace')

@section('content')
    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-semibold text-slate-900">Create a workspace</h2>
            <p class="mt-1 text-sm text-slate-500">A workspace keeps tickets, assistants, billing, and integrations grouped together.</p>
        </div>

        <form method="POST" action="{{ route('app.workspaces.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Workspace name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-200"
                    placeholder="Acme Support" />
                @if($errors->first('name'))
                    <p class="mt-1 text-sm text-red-600">{{ $errors->first('name') }}</p>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="inline-flex items-center rounded-lg bg-orange-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-600">
                    Create workspace
                </button>
                <a href="{{ route('app.workspaces.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
@endsection
