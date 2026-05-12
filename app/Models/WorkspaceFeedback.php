<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceFeedback extends Model
{
    protected $table = 'workspace_feedback';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'assistant_config_id',
        'category',
        'rating',
        'feedback_text',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(AssistantConfig::class, 'assistant_config_id');
    }
}
