<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseEvent extends Model
{
    protected $fillable = [
        'workspace_id',
        'support_case_id',
        'actor_user_id',
        'type',
        'data',
    ];

    protected $casts = [
        'workspace_id' => 'integer',
        'support_case_id' => 'integer',
        'actor_user_id' => 'integer',
        'data' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function supportCase(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'support_case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
