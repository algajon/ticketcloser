<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuggestedEvent extends Model
{
    protected $fillable = [
        'workspace_id',
        'case_id',
        'contact_id',
        'starts_at',
        'ends_at',
        'timezone',
        'confidence',
        'raw_text_span',
        'status',
    ];

    protected $casts = [
        'contact_id' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function supportCase(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'suggested_event_id');
    }
}
