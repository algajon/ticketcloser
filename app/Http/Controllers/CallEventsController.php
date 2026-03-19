<?php

namespace App\Http\Controllers;

use App\Models\CallEvent;
use App\Models\Workspace;
use Illuminate\Http\Request;

class CallEventsController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $events = CallEvent::where('workspace_id', $workspace->id)->latest()->limit(200)->get();
        return view('calls.index', compact('workspace', 'events'));
    }
}
