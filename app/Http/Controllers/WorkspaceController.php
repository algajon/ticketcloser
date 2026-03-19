<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    use Concerns\AuthorizesWorkspace;


    public function index(Request $request)
    {
        $workspaces = $request->user()->workspaces;
        return view('workspaces.index', compact('workspaces'));
    }

    public function create(Request $request)
    {
        return view('workspaces.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slugBase = Str::slug($data['name']);
        $slug = $slugBase;
        $i = 2;
        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $slugBase . '-' . $i++;
        }

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => $slug,
            'default_timezone' => 'America/New_York',
            'case_label' => 'Ticket',
            'credits_balance' => 0,
            'onboarding_step' => 'company',
            'integration_token' => Str::random(48),
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        return redirect()->route('app.onboarding.company');
    }

    public function switch(Request $request, Workspace $workspace)
    {
        abort_unless(
            $request->user()->workspaces()->where('workspaces.id', $workspace->id)->exists(),
            403
        );
        $request->session()->put('current_workspace_id', $workspace->id);
        return redirect()->route('app.dashboard');
    }

    public function settings(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        return view('workspaces.settings', compact('workspace'));
    }

    public function updateSettings(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'default_timezone' => 'required|string|max:80',
            'case_label' => 'required|string|max:40',
        ]);

        $workspace->update($data);

        return back()->with('success', 'Settings saved.');
    }
}
