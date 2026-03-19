<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyServerToken
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('services.server_api_token', '');

        if ($expected === '') {
            return response()->json(['error' => 'SERVER_API_TOKEN not set'], 500);
        }

        $auth = (string) $request->header('Authorization', '');
        $token = str_starts_with($auth, 'Bearer ') ? trim(substr($auth, 7)) : trim($auth);

        if ($token === '' || !hash_equals($expected, $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
