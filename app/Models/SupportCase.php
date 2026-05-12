<?php

namespace App\Models;

use App\Models\AssistantConfig;
use App\Jobs\ExtractSuggestedEvents;
use App\Support\RegionalPilotStackCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportCase extends Model
{
    protected $table = 'support_cases';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SupportCase $case) {
            if (filled($case->case_number)) {
                return;
            }

            do {
                $candidate = 'TC-' . Str::upper(Str::random(8));
            } while (self::query()->where('case_number', $candidate)->exists());

            $case->case_number = $candidate;
        });

        // Only extract scheduling hints when a case is first created or its text changes.
        static::saved(function (SupportCase $case) {
            $textChanged = $case->wasRecentlyCreated || $case->wasChanged(['title', 'description']);

            if (! $textChanged) {
                return;
            }

            ExtractSuggestedEvents::dispatch($case)->afterCommit()->onQueue('default');
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

    // Property-management workflow stages
    public const OPS_STAGE_NEW_INTAKE = 'new_intake';
    public const OPS_STAGE_URGENT_REVIEW = 'urgent_review';
    public const OPS_STAGE_DISPATCHED = 'dispatched';
    public const OPS_STAGE_SCHEDULED = 'scheduled';
    public const OPS_STAGE_WAITING_ON_RESIDENT = 'waiting_on_resident';
    public const OPS_STAGE_COMPLETED = 'completed';

    public const PROPERTY_MANAGEMENT_OPS_STAGES = [
        self::OPS_STAGE_NEW_INTAKE,
        self::OPS_STAGE_URGENT_REVIEW,
        self::OPS_STAGE_DISPATCHED,
        self::OPS_STAGE_SCHEDULED,
        self::OPS_STAGE_WAITING_ON_RESIDENT,
        self::OPS_STAGE_COMPLETED,
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
        'ops_stage',
        'requester_phone',
        'requester_email',
        'assignee_user_id',
        'queue_id',
        'contact_id',
        'transcript',
        'recording_url',
        'structured_payload',
        'access_notes',
        'preferred_visit_window',
        'vendor_name',
        'vendor_phone',
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

    public static function closedStatuses(): array
    {
        return [self::STATUS_RESOLVED, self::STATUS_CLOSED];
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_TRIAGED => 'primary',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_WAITING => 'warning',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'slate',
            default => 'info',
        };
    }

    public static function priorityTone(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_CRITICAL => 'danger',
            self::PRIORITY_HIGH => 'warning',
            default => 'slate',
        };
    }

    public static function opsStageOptionsFor(?Workspace $workspace): array
    {
        if (! $workspace || $workspace->use_case !== 'property_management') {
            return [];
        }

        return self::propertyManagementOpsStageLabels();
    }

    public static function propertyManagementOpsStageLabels(): array
    {
        return [
            self::OPS_STAGE_NEW_INTAKE => 'New intake',
            self::OPS_STAGE_URGENT_REVIEW => 'Urgent review',
            self::OPS_STAGE_DISPATCHED => 'Dispatched',
            self::OPS_STAGE_SCHEDULED => 'Scheduled',
            self::OPS_STAGE_WAITING_ON_RESIDENT => 'Waiting on resident',
            self::OPS_STAGE_COMPLETED => 'Completed',
        ];
    }

    public static function opsStageLabel(?string $stage): ?string
    {
        return $stage ? (self::propertyManagementOpsStageLabels()[$stage] ?? str($stage)->replace('_', ' ')->title()->toString()) : null;
    }

    public static function opsStageTone(?string $stage): string
    {
        return match ($stage) {
            self::OPS_STAGE_URGENT_REVIEW => 'danger',
            self::OPS_STAGE_DISPATCHED => 'primary',
            self::OPS_STAGE_SCHEDULED => 'success',
            self::OPS_STAGE_WAITING_ON_RESIDENT => 'warning',
            self::OPS_STAGE_COMPLETED => 'success',
            default => 'slate',
        };
    }

    public function usesPropertyManagementFlow(): bool
    {
        return $this->workspace?->use_case === 'property_management';
    }

    public function propertyDisplay(): ?string
    {
        return $this->structuredValue(['propertyCode', 'property_code', 'property', 'building', 'propertyAddress'])
            ?? $this->contact?->property_code;
    }

    public function unitDisplay(): ?string
    {
        return $this->structuredValue(['unit', 'unitNumber', 'unit_number'])
            ?? $this->contact?->unit;
    }

    public function accessDetailsDisplay(): ?string
    {
        return $this->access_notes
            ?: $this->structuredValue(['accessNotes', 'access_notes', 'accessDetails', 'access_details', 'entryNotes']);
    }

    public function preferredVisitWindowDisplay(): ?string
    {
        return $this->preferred_visit_window
            ?: $this->structuredValue(['preferredVisitWindow', 'preferred_visit_window', 'visitWindow', 'bestTimeForFollowUp']);
    }

    public function residentNameDisplay(): ?string
    {
        return $this->contact?->name ?: null;
    }

    public function transcriptLanguageLabel(): ?string
    {
        return RegionalPilotStackCatalog::languageLabel(
            data_get($this->structured_payload, 'voice_metadata.transcript.code'),
            data_get($this->structured_payload, 'voice_metadata.configured.code')
        );
    }

    public function transcriptLanguageSourceLabel(): ?string
    {
        return data_get($this->structured_payload, 'voice_metadata.transcript.source_label')
            ?: (filled(data_get($this->structured_payload, 'voice_metadata.transcript.code')) ? 'Detected from call' : null);
    }

    public function transcriberLabel(): ?string
    {
        return data_get($this->structured_payload, 'voice_metadata.transcriber.label');
    }

    protected function structuredValue(array $keys): mixed
    {
        foreach ($keys as $key) {
            $value = data_get($this->structured_payload, $key);
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }
}
