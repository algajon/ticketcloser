<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceConfig extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider',
        'assistant_id',
        'phone_number_id',
        'phone_number_e164',
        'voice_id',
        'recording_enabled',
        'transcript_enabled'
    ];

    protected $casts = [
        'workspace_id' => 'integer',
        'recording_enabled' => 'boolean',
        'transcript_enabled' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
