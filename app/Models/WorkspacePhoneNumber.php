<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WorkspacePhoneNumber extends Model
{
    protected $fillable = [
        'workspace_id',
        'assistant_id',
        'e164',
        'forwarding_number',
        'provisioning_mode',
        'external_provider',
        'vapi_credential_id',
        'vapi_phone_number_id',
        'activation_started_at',
        'is_active',
    ];

    protected $casts = [
        'activation_started_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public function activationCountdownEndsAt(int $seconds = 180): ?Carbon
    {
        if (! $this->activation_started_at || ! filled($this->vapi_phone_number_id) || ! filled($this->e164)) {
            return null;
        }

        $endsAt = $this->activation_started_at->copy()->addSeconds($seconds);

        return $endsAt->isFuture() ? $endsAt : null;
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(AssistantConfig::class, 'assistant_id');
    }
}
