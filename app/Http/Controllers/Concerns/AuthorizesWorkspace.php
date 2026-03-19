<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Workspace;
use Illuminate\Http\Request;

trait AuthorizesWorkspace
{
    /**
     * Abort 403 unless the authenticated user is a member of the workspace.
     */
    protected function authorizeWorkspaceAccess(Request $request, Workspace $workspace): Workspace
    {
        abort_unless(
            $request->user()->workspaces()->where('workspaces.id', $workspace->id)->exists(),
            403
        );

        return $workspace;
    }
}
