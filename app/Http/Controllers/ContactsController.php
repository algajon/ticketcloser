<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ContactsController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $q = request('q');

        $contacts = Contact::where('workspace_id', $workspace->id)
            ->when($q, function ($query, $q) {
                $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%")
                          ->orWhere('phone_e164', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%")
                          ->orWhere('property_code', 'like', "%{$q}%")
                          ->orWhere('unit', 'like', "%{$q}%");
                });
            })
            ->withCount('cases')
            ->latest()
            ->paginate(30);

        return view('contacts.index', compact('workspace', 'contacts', 'q'));
    }

    public function edit(Request $request, Workspace $workspace, Contact $contact)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($contact->workspace_id !== $workspace->id, 404);

        return view('contacts.edit', compact('workspace', 'contact'));
    }

    public function update(Request $request, Workspace $workspace, Contact $contact)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($contact->workspace_id !== $workspace->id, 404);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone_e164' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'property_code' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:50'],
        ]);

        $contact->update($data);

        return redirect()->route('app.contacts.index', $workspace)->with('success', 'Contact updated.');
    }

    public function destroy(Request $request, Workspace $workspace, Contact $contact)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($contact->workspace_id !== $workspace->id, 404);

        $contact->delete();

        return redirect()->route('app.contacts.index', $workspace)->with('success', 'Contact removed.');
    }
}
