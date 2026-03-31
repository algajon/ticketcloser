<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        ];
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
    public function workspaceMemberships()
    {
        return $this->hasMany(\App\Models\WorkspaceMembership::class);
    }

    public function currentWorkspace()
    {
        $sessionId = session('current_workspace_id');

        if ($sessionId) {
            $workspace = $this->workspaces()->where('workspaces.id', $sessionId)->first();
            if ($workspace) {
                return $workspace;
            }

            if (app()->bound('session')) {
                session()->forget('current_workspace_id');
            }
        }

        $workspaces = $this->workspaces()
            ->orderBy('workspaces.id')
            ->limit(2)
            ->get();

        if ($workspaces->count() !== 1) {
            return null;
        }

        $workspace = $workspaces->first();

        if (app()->bound('session')) {
            session(['current_workspace_id' => $workspace->id]);
        }

        return $workspace;
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
