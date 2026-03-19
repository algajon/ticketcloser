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

        $contacts = Contact::where('workspace_id', $workspace->id)->latest()->limit(200)->get();
        return view('contacts.index', compact('workspace', 'contacts'));
    }
}
