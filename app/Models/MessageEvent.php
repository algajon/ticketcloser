<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageEvent extends Model
{
    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_INBOUND = 'inbound';

    public const STATUS_DRAFTED = 'drafted';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_CLICKED = 'clicked';
    public const STATUS_OPENED = 'opened';

    protected $fillable = [
        'workspace_id',
        'assistant_config_id',
        'contact_id',
        'support_case_id',
        'calendar_event_id',
        'channel',
        'direction',
        'status',
        'provider',
        'external_message_id',
        'from_phone',
        'to_phone',
        'body',
        'response_body',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'replied_at',
        'failed_at',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'replied_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assistantConfig(): BelongsTo
    {
        return $this->belongsTo(AssistantConfig::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function supportCase(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_DELIVERED, self::STATUS_REPLIED, self::STATUS_CLICKED, self::STATUS_OPENED => 'success',
            self::STATUS_SENT, self::STATUS_QUEUED => 'info',
            self::STATUS_FAILED => 'danger',
            default => 'slate',
        };
    }
}
