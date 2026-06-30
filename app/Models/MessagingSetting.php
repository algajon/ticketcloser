<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingSetting extends Model
{
    public const BRAND_VOICES = [
        'warm' => 'Warm and clear',
        'professional' => 'Professional',
        'brief' => 'Brief',
        'friendly' => 'Friendly',
    ];

    protected $fillable = [
        'workspace_id',
        'booking_confirmation_enabled',
        'booking_confirmation_template',
        'signature',
        'brand_voice',
        'include_ticket_number',
        'include_issue_label',
        'reply_capture_enabled',
        'metadata',
    ];

    protected $casts = [
        'booking_confirmation_enabled' => 'boolean',
        'include_ticket_number' => 'boolean',
        'include_issue_label' => 'boolean',
        'reply_capture_enabled' => 'boolean',
        'metadata' => 'array',
    ];

    public static function defaultTemplate(): string
    {
        return 'Hi {{customer_name}}, your follow-up with {{workspace_name}} is booked for {{appointment_time}}. {{ticket_number}} {{issue_label}} {{signature}}';
    }

    public static function defaultSignature(Workspace $workspace): string
    {
        return '- '.$workspace->name;
    }

    public static function defaultsFor(Workspace $workspace): array
    {
        return [
            'booking_confirmation_enabled' => true,
            'booking_confirmation_template' => self::defaultTemplate(),
            'signature' => self::defaultSignature($workspace),
            'brand_voice' => 'warm',
            'include_ticket_number' => true,
            'include_issue_label' => true,
            'reply_capture_enabled' => true,
        ];
    }

    public static function forWorkspace(Workspace $workspace): self
    {
        return self::firstOrCreate(
            ['workspace_id' => $workspace->id],
            self::defaultsFor($workspace)
        );
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function renderPreview(array $overrides = []): string
    {
        $sample = array_merge([
            'customer_name' => 'Maria',
            'workspace_name' => $this->workspace?->name ?? 'your team',
            'appointment_time' => 'Tue, Jul 7 at 2:30 PM',
            'ticket_number' => $this->include_ticket_number ? 'Ticket TC-1042.' : '',
            'issue_label' => $this->include_issue_label ? 'Kitchen leak.' : '',
            'signature' => $this->signature ?: '',
        ], $overrides);

        $message = (string) $this->booking_confirmation_template;

        foreach ($sample as $key => $value) {
            $message = str_replace('{{'.$key.'}}', trim((string) $value), $message);
        }

        return trim((string) preg_replace('/\s+/', ' ', $message));
    }
}
