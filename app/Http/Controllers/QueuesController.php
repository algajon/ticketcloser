<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Workspace;
use Illuminate\Http\Request;

class QueuesController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        $queues = Queue::where('workspace_id', $workspace->id)->get();
        return view('queues.index', compact('workspace', 'queues'));
    }

}
