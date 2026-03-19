<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyWorkspaceToken
{
    public function handle(Request $request, Closure $next)
    {
        $workspace = $request->attributes->get('workspace');
        if (!$workspace) {
            return response()->json(['error' => 'Workspace not resolved'], 500);
        }

        $expected = (string) ($workspace->integration_token ?? '');
        if ($expected === '') {
            return response()->json(['error' => 'Workspace token not configured'], 403);
        }

        $auth = (string) $request->header('Authorization', '');
        $token = str_starts_with($auth, 'Bearer ') ? trim(substr($auth, 7)) : trim($auth);

        if ($token === '' || !hash_equals($expected, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
