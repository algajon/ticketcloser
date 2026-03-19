<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptVersion extends Model
{
    protected $fillable = [
        'workspace_id',
        'created_by',
        'assistant_id',
        'name',
        'assistant_type',
        'tone',
        'strictness',
        'tools_enabled',
        'input_summary',
        'output_markdown',
    ];

    protected $casts = [
        'tools_enabled' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AssistantConfig::class, 'assistant_id');
    }
}
