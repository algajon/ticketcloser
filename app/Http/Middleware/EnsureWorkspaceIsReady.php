<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceIsReady
{
    protected array $selectionExcept = [
        'app.workspaces.*',
        'logout',
    ];

    protected array $onboardingExcept = [
        'app.workspaces.*',
        'app.onboarding.*',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';

        if ($this->matchesAny($routeName, $this->selectionExcept)) {
            return $next($request);
        }

        $workspace = $request->user()->currentWorkspace();

        if (! $workspace) {
            $hasWorkspaces = $request->user()->workspaces()->exists();

            return redirect()
                ->route($hasWorkspaces ? 'app.workspaces.index' : 'app.workspaces.create')
                ->with('error', $hasWorkspaces
                    ? 'Choose a workspace to continue.'
                    : 'Create a workspace to continue.');
        }

        if ($this->matchesAny($routeName, $this->onboardingExcept)) {
            return $next($request);
        }

        if (($workspace->onboarding_step ?? 'company') !== 'done') {
            return redirect()
                ->route('app.onboarding.company')
                ->with('error', 'Finish setting up your workspace before continuing.');
        }

        return $next($request);
    }

    protected function matchesAny(string $routeName, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '*')) {
                if (str_starts_with($routeName, rtrim($pattern, '*'))) {
                    return true;
                }

                continue;
            }

            if ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }
}
