<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'workspace_id',
        'case_id',
        'suggested_event_id',
        'provider',
        'provider_event_id',
        'starts_at',
        'ends_at',
        'timezone',
        'status',
        'url',
        'payload',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'payload' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function supportCase(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function suggestedEvent(): BelongsTo
    {
        return $this->belongsTo(SuggestedEvent::class);
    }
}
