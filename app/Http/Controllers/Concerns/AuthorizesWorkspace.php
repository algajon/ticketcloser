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
            $request->user()->hasWorkspace($workspace->id),
            403
        );

        return $workspace;
    }

    /**
     * Abort 403 unless the authenticated user has one of the required roles.
     */
    protected function authorizeWorkspaceRole(
        Request $request,
        Workspace $workspace,
        array|string $roles,
        ?string $message = null
    ): Workspace {
        $this->authorizeWorkspaceAccess($request, $workspace);

        abort_unless(
            $request->user()->hasWorkspaceRole($workspace->id, $roles),
            403,
            $message ?? 'You do not have permission to manage this workspace.'
        );

        return $workspace;
    }
}
