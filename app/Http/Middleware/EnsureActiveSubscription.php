<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Routes that should bypass subscription checks.
     */
    protected array $except = [
        'app.billing.*',
        'app.onboarding.*',
        'app.profile.*',
        'app.workspaces.index',
        'app.workspaces.create',
        'app.workspaces.store',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for non-auth or excluded routes
        if (!$request->user() || $this->shouldSkip($request)) {
            return $next($request);
        }

        $workspace = $request->user()->currentWorkspace();

        // No workspace yet → let them create one
        if (!$workspace) {
            return $next($request);
        }

        // Free plan users can still access the app (limited features enforced at controller level)
        if ($workspace->plan_key === 'free') {
            return $next($request);
        }

        // Paid plan users must have an active subscription
        if ($workspace->hasActiveSubscription()) {
            return $next($request);
        }

        // Subscription lapsed — send them to plan picker
        return redirect()->route('app.billing.plans')
            ->with('error', 'Your subscription has expired. Please choose a plan to continue.');
    }

    protected function shouldSkip(Request $request): bool
    {
        $routeName = $request->route()?->getName() ?? '';

        foreach ($this->except as $pattern) {
            if (str_ends_with($pattern, '*')) {
                if (str_starts_with($routeName, rtrim($pattern, '*'))) {
                    return true;
                }
            } elseif ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }
}
