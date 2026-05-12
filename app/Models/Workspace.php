<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\RegionalPilotStackCatalog;
use App\Support\WorkspaceUseCaseCatalog;
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
        'use_case',
        'use_case_details',
        'primary_market',
        'team_size',
        'default_assistant_name',
        'default_preset_key',
        'default_language_code',
        'default_phone_provisioning_mode',
        'default_external_phone_provider',
        'default_vapi_credential_id',
        'logo_path',
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

    public function bypassesPlanLimits(): bool
    {
        if ($this->relationLoaded('users')) {
            return $this->getRelation('users')->contains(fn (User $user) => $user->isAdmin());
        }

        return $this->users()
            ->where('users.is_admin', true)
            ->exists();
    }

    public function useCaseDefinition(): array
    {
        return WorkspaceUseCaseCatalog::definition($this->use_case, $this->use_case_details);
    }

    public function useCaseLabel(): string
    {
        return $this->useCaseDefinition()['label'];
    }

    public function primaryMarket(): string
    {
        return RegionalPilotStackCatalog::normalizeMarket($this->primary_market);
    }

    public function preferredLanguageCode(): string
    {
        return trim((string) ($this->default_language_code ?: RegionalPilotStackCatalog::defaultLanguageForMarket($this->primaryMarket())));
    }

    public function preferredPhoneSetupMode(): string
    {
        return trim((string) ($this->default_phone_provisioning_mode ?: RegionalPilotStackCatalog::defaultPhoneSetupMode($this->primaryMarket())));
    }

    public function preferredExternalPhoneProvider(): string
    {
        return trim((string) ($this->default_external_phone_provider ?: RegionalPilotStackCatalog::defaultExternalProvider($this->primaryMarket())));
    }

    public function logoUrl(): ?string
    {
        $path = trim((string) $this->logo_path);

        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Whether the workspace has a paid, active Stripe subscription.
     */
    public function hasActiveSubscription(): bool
    {
        if ($this->relationLoaded('subscription')) {
            $subscription = $this->getRelation('subscription');

            return $subscription !== null
                && in_array($subscription->status, ['active', 'trialing'], true);
        }

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

    public function includedMinutesLimit(): ?int
    {
        if ($this->bypassesPlanLimits()) {
            return null;
        }

        $limit = (int) ($this->activePlan()['max_minutes'] ?? 0);

        return $limit === -1 ? null : $limit;
    }

    public function voiceMinutesUsed(?Carbon $since = null): int
    {
        $usageQuery = UsageEvent::query()
            ->where('workspace_id', $this->id)
            ->where('event_type', 'call');

        $callQuery = $this->callEvents();

        if ($since) {
            $usageQuery->where('occurred_at', '>=', $since);
            $callQuery->where('created_at', '>=', $since);
        }

        $usageMinutes = (int) $usageQuery->sum('minutes');

        if ($usageMinutes > 0) {
            return $usageMinutes;
        }

        return (int) ceil(((int) $callQuery->sum('duration_seconds')) / 60);
    }

    public function hasReachedVoiceMinuteLimit(?Carbon $since = null): bool
    {
        $limit = $this->includedMinutesLimit();

        if ($limit === null) {
            return false;
        }

        return $this->voiceMinutesUsed($since) >= $limit;
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

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_memberships')
                    ->withPivot('role');
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

    public function intakeConfig(): HasOne
    {
        return $this->hasOne(IntakeConfig::class);
    }

    public function assistantConfigs(): HasMany
    {
        return $this->hasMany(AssistantConfig::class);
    }

    public function callEvents(): HasMany
    {
        return $this->hasMany(CallEvent::class);
    }

    public function feedbackEntries(): HasMany
    {
        return $this->hasMany(WorkspaceFeedback::class);
    }

    public function vapiMinutesUsed(): int
    {
        $sumSeconds = $this->callEvents()->sum('duration_seconds');
        return (int) ceil($sumSeconds / 60);
    }
}
