<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
class Workspace extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'integration_token',
        'credits_balance',
        'case_label',
        'default_timezone',
        'onboarding_step',
        'plan_key',
    ];

    protected static function booted()
    {
        static::creating(function (Workspace $workspace) {
            if (empty($workspace->integration_token)) {
                $workspace->integration_token = self::generateIntegrationToken();
            }
        });
    }

    public static function generateIntegrationToken(): string
    {
        // 64 hex chars (safe as a bearer token) with a small prefix for debugging
        return 'tc_' . Str::lower(Str::random(50));
    }

    /* ── Plan Helpers ────────────────────────────────── */

    /**
     * Return the config array for the workspace's current plan.
     */
    public function activePlan(): array
    {
        return config('plans.' . ($this->plan_key ?? 'free'), config('plans.free'));
    }

    public function planLabel(): string
    {
        return $this->activePlan()['label'] ?? ucfirst($this->plan_key ?? 'free');
    }

    /**
     * Whether the workspace has a paid, active Stripe subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription()
                ?->whereIn('status', ['active', 'trialing'])
            ->exists() ?? false;
    }

    /**
     * Whether the workspace is on the free trial plan.
     */
    public function isFreePlan(): bool
    {
        return ($this->plan_key ?? 'free') === 'free';
    }

    /* ── Relationships ───────────────────────────────── */

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMembership::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CaseComment::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(WorkspacePhoneNumber::class);
    }

    public function assistantConfigs(): HasMany
    {
        return $this->hasMany(AssistantConfig::class);
    }

    public function callEvents(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }

    public function vapiMinutesUsed(): int
    {
        $sumSeconds = $this->callEvents()->sum('duration_seconds');
        return (int) ceil($sumSeconds / 60);
    }
}

