<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class CalendarConnection extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider',
        'tokens_encrypted',
        'expires_at',
        'calendly_scheduling_link',
        'metadata',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** Store tokens as encrypted JSON */
    public function setTokensAttribute(array $tokens): void
    {
        $this->attributes['tokens_encrypted'] = Crypt::encryptString(json_encode($tokens));
    }

    /** Decrypt and parse tokens */
    public function getTokensAttribute(): array
    {
        try {
            return json_decode(Crypt::decryptString($this->tokens_encrypted), true) ?? [];
        } catch (\Exception) {
            return [];
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
