<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use App\Models\Workspace;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'otp_code',
        'otp_expires_at',
        'terms_accepted_at',
        'terms_version',
        'marketing_opted_in_at',
        'welcome_email_sent_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected ?Collection $resolvedWorkspaces = null;

    protected bool $resolvedCurrentWorkspaceLoaded = false;

    protected ?\App\Models\Workspace $resolvedCurrentWorkspace = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'otp_expires_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'marketing_opted_in_at' => 'datetime',
            'welcome_email_sent_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function bypassesPlanLimits(): bool
    {
        return $this->isAdmin();
    }
    public function workspaceMemberships()
    {
        return $this->hasMany(\App\Models\WorkspaceMembership::class);
    }

    public function availableWorkspaces(): Collection
    {
        if ($this->resolvedWorkspaces instanceof Collection) {
            return $this->resolvedWorkspaces;
        }

        if ($this->relationLoaded('workspaces')) {
            return $this->resolvedWorkspaces = $this->getRelation('workspaces');
        }

        $workspaces = $this->workspaces()
            ->with([
                'subscription' => fn ($query) => $query->select(
                    'subscriptions.id',
                    'subscriptions.workspace_id',
                    'subscriptions.status'
                ),
            ])
            ->orderBy('workspaces.id')
            ->get();

        $this->setRelation('workspaces', $workspaces);

        return $this->resolvedWorkspaces = $workspaces;
    }

    public function hasWorkspace(int $workspaceId): bool
    {
        return $this->availableWorkspaces()->contains('id', $workspaceId);
    }

    public function workspaceRole(int|Workspace $workspace): ?string
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;
        $memberWorkspace = $this->availableWorkspaces()->firstWhere('id', $workspaceId);

        return $memberWorkspace?->pivot?->role;
    }

    public function hasWorkspaceRole(int|Workspace $workspace, array|string $roles): bool
    {
        $role = $this->workspaceRole($workspace);

        if ($role === null) {
            return false;
        }

        $allowedRoles = is_array($roles) ? $roles : [$roles];

        return in_array($role, $allowedRoles, true);
    }

    public function canManageWorkspace(int|Workspace $workspace): bool
    {
        return $this->hasWorkspaceRole($workspace, [
            WorkspaceMembership::ROLE_OWNER,
            WorkspaceMembership::ROLE_ADMIN,
        ]);
    }

    public function canManageAssistants(int|Workspace $workspace): bool
    {
        return $this->hasWorkspaceRole($workspace, [
            WorkspaceMembership::ROLE_OWNER,
            WorkspaceMembership::ROLE_ADMIN,
            WorkspaceMembership::ROLE_MANAGER,
        ]);
    }

    public function canManageIntegrations(int|Workspace $workspace): bool
    {
        return $this->hasWorkspaceRole($workspace, [
            WorkspaceMembership::ROLE_OWNER,
            WorkspaceMembership::ROLE_ADMIN,
        ]);
    }

    public function canManageBilling(int|Workspace $workspace): bool
    {
        return $this->hasWorkspaceRole($workspace, [
            WorkspaceMembership::ROLE_OWNER,
            WorkspaceMembership::ROLE_ADMIN,
        ]);
    }

    public function maxWorkspaceCount(): ?int
    {
        if ($this->bypassesPlanLimits()) {
            return null;
        }

        $workspaces = $this->availableWorkspaces();

        $limits = $workspaces->isEmpty()
            ? collect([(int) (config('plans.free.max_workspaces') ?? 1)])
            : $workspaces->map(fn (Workspace $workspace) => (int) ($workspace->activePlan()['max_workspaces'] ?? -1));

        if ($limits->contains(-1)) {
            return null;
        }

        $limit = (int) $limits->max();

        return $limit > 0 ? $limit : null;
    }

    public function hasReachedWorkspaceLimit(): bool
    {
        $limit = $this->maxWorkspaceCount();

        return $limit !== null && $this->availableWorkspaces()->count() >= $limit;
    }

    public function hasOnlyFreeWorkspaces(): bool
    {
        $workspaces = $this->availableWorkspaces();

        return $workspaces->isEmpty()
            || $workspaces->every(fn (Workspace $workspace) => $workspace->isFreePlan());
    }

    public function currentWorkspace()
    {
        if ($this->resolvedCurrentWorkspaceLoaded) {
            return $this->resolvedCurrentWorkspace;
        }

        $sessionId = session('current_workspace_id');
        $workspaces = $this->availableWorkspaces();

        if ($sessionId) {
            $workspace = $workspaces->firstWhere('id', (int) $sessionId);
            if ($workspace) {
                $this->resolvedCurrentWorkspaceLoaded = true;
                return $this->resolvedCurrentWorkspace = $workspace;
            }

            if (app()->bound('session')) {
                session()->forget('current_workspace_id');
            }
        }

        if ($workspaces->count() !== 1) {
            $this->resolvedCurrentWorkspaceLoaded = true;
            return $this->resolvedCurrentWorkspace = null;
        }

        $workspace = $workspaces->first();

        if (app()->bound('session')) {
            session(['current_workspace_id' => $workspace->id]);
        }

        $this->resolvedCurrentWorkspaceLoaded = true;

        return $this->resolvedCurrentWorkspace = $workspace;
    }

    public function workspaces()
    {
        return $this->belongsToMany(\App\Models\Workspace::class, 'workspace_memberships')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(\App\Models\SupportCase::class, 'assignee_user_id');
    }
}
