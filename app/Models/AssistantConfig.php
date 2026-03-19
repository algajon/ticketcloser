<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantConfig extends Model
{
    protected $fillable = [
        'workspace_id',
        'name',
        'system_prompt',
        'voice_provider',
        'voice_id',
        'vapi_tool_id',
        'vapi_booking_tool_id',
        'vapi_assistant_id',
        'is_active',
        'intake_params',
        'preset_key',
        'override_params',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'intake_params' => 'array',
        'override_params' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}