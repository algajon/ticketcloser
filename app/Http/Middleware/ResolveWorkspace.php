<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;

class ResolveWorkspace
{
    public function handle(Request $request, Closure $next)
    {
        $slug = (string) $request->header('X-Workspace-Slug', '');

        if ($slug === '') {
            return response()->json(['error' => 'Missing X-Workspace-Slug'], 400);
        }

        $workspace = Workspace::where('slug', $slug)->first();

        if (!$workspace) {
            return response()->json(['error' => 'Workspace not found'], 404);
        }

        $request->attributes->set('workspace', $workspace);

        return $next($request);
    }
}
