@extends('layouts.saas')

@section('title', 'Admin • Presets')

@section('header', 'Manage Assistant Presets')

@section('content')
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-slate-900">Vapi Presets</h1>
    </div>

    <div class="bg-white rounded border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-semibold text-slate-500">
                <tr>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Key</th>
                    <th class="px-6 py-3">Notes</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($presets as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 font-medium text-slate-900">{{ $p->name }}</td>
                        <td class="px-6 py-4"><code class="bg-slate-100 text-slate-600 px-1 py-0.5 rounded">{{ $p->key }}</code>
                        </td>
                        <td class="px-6 py-4 text-xs">{{ $p->notes }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.presets.edit', $p) }}"
                                class="text-blue-600 hover:text-blue-800 font-medium text-sm">Edit JSON</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">No presets found. Run seeders.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection