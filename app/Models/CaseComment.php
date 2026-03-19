<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseComment extends Model
{
    protected $fillable = [
        'workspace_id',
        'support_case_id',
        'author_user_id',
        'is_internal',
        'body',
    ];

    protected $casts = [
        'workspace_id' => 'integer',
        'support_case_id' => 'integer',
        'author_user_id' => 'integer',
        'is_internal' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function supportCase(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'support_case_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
