<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'workspace_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'plan_key',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'canceled_at',
        'metadata',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'canceled_at' => 'datetime',
        'cancel_at_period_end' => 'boolean',
        'metadata' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }

    public function planLabel(): string
    {
        return match ($this->plan_key) {
            'startup' => 'Startup',
            'pro' => 'Pro',
            'enterprise' => 'Enterprise',
            default => ucfirst($this->plan_key),
        };
    }

    /**
     * Return the full plan config array from config/plans.php.
     */
    public function planConfig(): array
    {
        return config('plans.' . $this->plan_key, config('plans.free'));
    }
}
