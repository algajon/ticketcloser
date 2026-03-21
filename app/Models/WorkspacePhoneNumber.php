<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspacePhoneNumber extends Model
{
    protected $fillable = [
        'workspace_id',
        'assistant_id',
        'e164',
        'forwarding_number',
        'vapi_phone_number_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(AssistantConfig::class, 'assistant_id');
    }
}