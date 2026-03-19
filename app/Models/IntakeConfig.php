<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntakeConfig extends Model
{
    protected $fillable = [
        'workspace_id',
        'system_prompt',
        'required_fields',
        'category_options',
        'priority_rules'
    ];

    protected $casts = [
        'workspace_id' => 'integer',
        'required_fields' => 'array',
        'category_options' => 'array',
        'priority_rules' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
