<?php

namespace App\Models;

use App\Models\AssistantConfig;
use App\Jobs\ExtractSuggestedEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportCase extends Model
{
    protected $table = 'support_cases';

    protected static function boot(): void
    {
        parent::boot();

        // Dispatch date/time extraction job whenever a case is saved
        static::saved(function (SupportCase $case) {
            ExtractSuggestedEvents::dispatch($case)->onQueue('default');
        });
    }

    // Priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
        self::PRIORITY_CRITICAL,
    ];

    // Statuses (MVP)
    public const STATUS_NEW = 'new';
    public const STATUS_TRIAGED = 'triaged';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING = 'waiting';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_TRIAGED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_WAITING,
        self::STATUS_RESOLVED,
        self::STATUS_CLOSED,
    ];

    // Sources
    public const SOURCE_VOICE = 'voice';
    public const SOURCE_WEB = 'web';
    public const SOURCE_API = 'api';
    public const SOURCE_EMAIL = 'email';

    protected $fillable = [
        'workspace_id',
        'assistant_config_id',
        'case_number',
        'title',
        'description',
        'category',
        'priority',
        'status',
        'requester_phone',
        'requester_email',
        'assignee_user_id',
        'queue_id',
        'contact_id',
        'transcript',
        'recording_url',
        'structured_payload',
        'source',
        'external_call_id',
    ];

    protected $attributes = [
        'priority' => self::PRIORITY_NORMAL,
        'status' => self::STATUS_NEW,
        'source' => self::SOURCE_VOICE,
    ];

    protected $casts = [
        'workspace_id' => 'integer',
        'assignee_user_id' => 'integer',
        'queue_id' => 'integer',
        'contact_id' => 'integer',
        'structured_payload' => 'array',
    ];

    // Relationships
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assistantConfig(): BelongsTo
    {
        return $this->belongsTo(AssistantConfig::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class, 'support_case_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class, 'support_case_id');
    }

    // Useful scopes
    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function suggestedEvents(): HasMany
    {
        return $this->hasMany(SuggestedEvent::class, 'case_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(\App\Models\CalendarEvent::class, 'case_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
