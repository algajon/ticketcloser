@extends('layouts.saas')

@section('title', 'tickIt - Edit contact')
@section('header_eyebrow', 'Contacts')
@section('header', 'Edit contact')
@section('header_description', 'Update the details your assistants use when this person calls again.')

@section('header_actions')
    <a href="{{ route('app.contacts.index', $workspace) }}" class="tc-btn-secondary">Back to contacts</a>
@endsection

@section('content')
    <div class="tc-card max-w-2xl min-w-0">
        <form method="POST" action="{{ route('app.contacts.update', [$workspace, $contact]) }}" class="divide-y divide-slate-100">
            @csrf
            @method('PATCH')

            <div class="p-6">
                <h2 class="mb-4 text-base font-bold text-slate-900">Contact details</h2>
                <p class="mb-6 text-xs text-slate-500">Update the caller details your assistant should remember for future calls.</p>

                <div class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $contact->name ?? '') }}"
                                class="tc-input" placeholder="e.g. Rajesh Patel" />
                        </div>

                        <div class="space-y-1.5">
                            <label for="phone_e164" class="block text-sm font-medium text-slate-700">Phone number (E.164)</label>
                            <input id="phone_e164" type="text" name="phone_e164" value="{{ old('phone_e164', $contact->phone_e164 ?? '') }}"
                                class="tc-input" placeholder="+12345678900" required />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $contact->email ?? '') }}"
                            class="tc-input" placeholder="client@example.com" />
                    </div>
                </div>
            </div>

            <div class="p-6">
                <h2 class="mb-4 text-base font-bold text-slate-900">Property / location data</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="property_code" class="block text-sm font-medium text-slate-700">Property code or street</label>
                        <input id="property_code" type="text" name="property_code" value="{{ old('property_code', $contact->property_code ?? '') }}"
                            class="tc-input" placeholder="e.g. 123 Main St" />
                    </div>

                    <div class="space-y-1.5">
                        <label for="unit" class="block text-sm font-medium text-slate-700">Unit / apartment</label>
                        <input id="unit" type="text" name="unit" value="{{ old('unit', $contact->unit ?? '') }}"
                            class="tc-input" placeholder="e.g. 4B" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-b-xl border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="order-2 sm:order-1">
                    <button type="button" class="text-sm font-medium text-danger transition-colors hover:text-danger-fg" onclick="if(confirm('Are you sure you want to delete this contact?')) { document.getElementById('delete-form').submit(); }">Delete contact</button>
                </div>
                <div class="order-1 min-w-0 flex w-full flex-col gap-2 sm:order-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end sm:gap-3">
                    <a href="{{ route('app.contacts.index', $workspace) }}" class="tc-btn-ghost w-full justify-center sm:w-auto">Cancel</a>
                    <button type="submit" class="tc-btn-primary w-full justify-center sm:w-auto">Save changes</button>
                </div>
            </div>
        </form>
    </div>

    <form id="delete-form" action="{{ route('app.contacts.destroy', [$workspace, $contact]) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endsection
