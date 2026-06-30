<?php

namespace App\Services;

use App\Mail\WelcomeToTickItMail;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use Illuminate\Support\Facades\Mail;

class WelcomeEmailService
{
    public function sendIfReady(User $user, ?Workspace $workspace = null): bool
    {
        $freshUser = $user->fresh();

        if (! $freshUser || $freshUser->welcome_email_sent_at || ! $freshUser->hasVerifiedEmail()) {
            return false;
        }

        $readyWorkspace = $this->readyWorkspaceFor($freshUser, $workspace);

        if (! $readyWorkspace) {
            return false;
        }

        $sentAt = now();
        $claimed = User::query()
            ->whereKey($freshUser->id)
            ->whereNull('welcome_email_sent_at')
            ->update(['welcome_email_sent_at' => $sentAt]);

        if ($claimed === 0) {
            return false;
        }

        $freshUser->forceFill(['welcome_email_sent_at' => $sentAt]);

        Mail::to($freshUser->email)->send(new WelcomeToTickItMail($freshUser, $readyWorkspace));

        return true;
    }

    private function readyWorkspaceFor(User $user, ?Workspace $workspace): ?Workspace
    {
        if ($workspace && $this->isReadyWorkspaceForUser($user, $workspace)) {
            return $workspace->fresh() ?? $workspace;
        }

        return Workspace::query()
            ->where('onboarding_step', 'done')
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->orderBy('workspaces.id')
            ->first();
    }

    private function isReadyWorkspaceForUser(User $user, Workspace $workspace): bool
    {
        if (($workspace->onboarding_step ?? 'company') !== 'done') {
            return false;
        }

        return WorkspaceMembership::query()
            ->where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
