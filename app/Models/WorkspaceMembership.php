<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceMembership extends Model
{
    // Roles (MVP)
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_AGENT = 'agent';
    public const ROLE_AUDITOR = 'auditor';

    public const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_AGENT,
        self::ROLE_AUDITOR,
    ];

    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'workspace_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
