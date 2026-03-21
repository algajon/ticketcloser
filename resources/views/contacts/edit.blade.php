@extends('layouts.saas')

@section('title')
    ticketcloser • Edit Contact
@endsection

@section('header')
    Edit Contact
@endsection

@section('content')
    <div class="mb-6">
        <a href="{{ route('app.contacts.index', $workspace) }}"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors">
            ← Back to Contacts
        </a>
    </div>

    <div class="tc-card max-w-2xl">
        <form method="POST" action="{{ route('app.contacts.update', [$workspace, $contact]) }}" class="divide-y divide-slate-100">
            @csrf
            @method('PATCH')
            
            <div class="p-6">
                <h2 class="text-base font-bold text-slate-900 mb-4">Contact Details</h2>
                <p class="text-xs text-slate-500 mb-6">Modify the caller's details to help the AI Assistant recognize them instantly when they call.</p>
                
                <div class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="name" class="block text-sm font-medium text-slate-700">Full Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $contact->name ?? '') }}"
                                class="tc-input" placeholder="e.g. Rajesh Patel" />
                        </div>
                        
                        <div class="space-y-1.5">
                            <label for="phone_e164" class="block text-sm font-medium text-slate-700">Phone Number (E.164)</label>
                            <input id="phone_e164" type="text" name="phone_e164" value="{{ old('phone_e164', $contact->phone_e164 ?? '') }}"
                                class="tc-input" placeholder="+12345678900" required />
                        </div>
                    </div>
                    
                    <div class="space-y-1.5">
                        <label for="email" class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $contact->email ?? '') }}"
                            class="tc-input" placeholder="client@example.com" />
                    </div>
                </div>
            </div>

            <div class="p-6">
                <h2 class="text-base font-bold text-slate-900 mb-4">Property / Location Data</h2>
                
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="property_code" class="block text-sm font-medium text-slate-700">Property Code or Street</label>
                        <input id="property_code" type="text" name="property_code" value="{{ old('property_code', $contact->property_code ?? '') }}"
                            class="tc-input" placeholder="e.g. 123 Main St" />
                    </div>
                    
                    <div class="space-y-1.5">
                        <label for="unit" class="block text-sm font-medium text-slate-700">Unit / Apartment</label>
                        <input id="unit" type="text" name="unit" value="{{ old('unit', $contact->unit ?? '') }}"
                            class="tc-input" placeholder="e.g. 4B" />
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 flex items-center justify-between rounded-b-xl border-t border-slate-100">
                <div>
                    <button type="button" class="text-sm font-medium text-danger hover:text-danger-fg transition-colors" onclick="if(confirm('Are you sure you want to delete this contact?')) { document.getElementById('delete-form').submit(); }">Delete Contact</button>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('app.contacts.index', $workspace) }}" class="tc-btn-ghost">Cancel</a>
                    <button type="submit" class="tc-btn-primary">Save Changes</button>
                </div>
            </div>
        </form>
    </div>

    <form id="delete-form" action="{{ route('app.contacts.destroy', [$workspace, $contact]) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
@endsection
