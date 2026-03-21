<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Workspace;
use App\Models\Contact;
use App\Models\SupportCase;
use App\Models\CallEvent;
use App\Models\UsageEvent;
use App\Models\CreditLedger;

class ProcessEndCallReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public Workspace $workspace,
        public array $payload
    ) {
    }

    public function handle(): void
    {
        $workspace = $this->workspace;
        $payload = $this->payload;

        $vapiCallId = data_get($payload, 'message.call.id');

        if (!$vapiCallId) {
            return;
        }

        $callBlock = data_get($payload, 'message.call', []);
        $duration = data_get($callBlock, 'durationSeconds') ?? data_get($payload, 'message.durationSeconds');
        $cost = data_get($callBlock, 'cost') ?? data_get($payload, 'message.cost');

        try {
            CallEvent::updateOrCreate(
                ['vapi_call_id' => $vapiCallId],
                [
                    'workspace_id' => $workspace->id,
                    'duration_seconds' => $duration,
                    'cost' => $cost,
                    'meta' => $callBlock ?: data_get($payload, 'message', []),
                ]
            );

            // Find case associated with this call
            $case = SupportCase::where('workspace_id', $workspace->id)
                ->where('external_call_id', $vapiCallId)
                ->first();

            // End-of-Call Prospecting: Auto-log all callers as Contacts
            $customerNumber = data_get($payload, 'message.call.customer.number');
            if ($customerNumber) {
                $phoneStr = '+' . ltrim(preg_replace('/\D+/', '', $customerNumber), '+');
                $phoneSearch = ltrim(preg_replace('/\D+/', '', $customerNumber), '1+');
                if (strlen($phoneSearch) > 5) {
                    $contact = Contact::where('workspace_id', $workspace->id)
                        ->where('phone_e164', 'like', "%{$phoneSearch}%")
                        ->first();

                    if (!$contact) {
                        $contact = Contact::create([
                            'workspace_id' => $workspace->id,
                            'phone_e164' => $phoneStr,
                            // We can extract an actual name from transcript if needed, but leaving blank as MVP.
                        ]);
                    }

                    if ($case && !$case->contact_id) {
                        $case->contact_id = $contact->id;
                        $case->save();
                    }
                }
            }

            // 1) Billing - Usage Event
            if ($duration > 0) {
                $existingUsage = UsageEvent::where('workspace_id', $workspace->id)
                    ->where('event_type', 'call')
                    ->where('metadata->vapi_call_id', $vapiCallId)
                    ->exists();

                if (!$existingUsage) {
                    $minutes = (int) ceil($duration / 60);
                    UsageEvent::create([
                        'workspace_id' => $workspace->id,
                        'support_case_id' => $case?->id,
                        'minutes' => $minutes,
                        'event_type' => 'call',
                        'occurred_at' => now(),
                        'metadata' => ['vapi_call_id' => $vapiCallId],
                    ]);
                }
            }

            // 2) Billing - Deduct Credits
            if ($cost > 0) {
                $costInCents = (int) round($cost * 100);
                if ($costInCents > 0) {
                    DB::transaction(function () use ($workspace, $vapiCallId, $costInCents) {
                        $lockedWorkspace = Workspace::lockForUpdate()->find($workspace->id);
                        
                        $existingCreditDeduction = CreditLedger::where('workspace_id', $lockedWorkspace->id)
                            ->where('type', 'call_deduction')
                            ->where('meta->vapi_call_id', $vapiCallId)
                            ->exists();

                        if (!$existingCreditDeduction) {
                            CreditLedger::create([
                                'workspace_id' => $lockedWorkspace->id,
                                'type' => 'call_deduction',
                                'amount' => -$costInCents,
                                'meta' => ['vapi_call_id' => $vapiCallId],
                            ]);
                            $lockedWorkspace->decrement('credits_balance', $costInCents);
                        }
                    });
                }
            }

            // 3) Update case title and description
            $summary = data_get($payload, 'message.analysis.summary');
            if ($case && $summary) {
                $changed = false;
                
                // Replace missing or generic description
                if (empty($case->description) || trim($case->description) === 'New case, no description') {
                    $case->description = $summary;
                    $changed = true;
                }
                
                // Replace missing or generic title
                if (empty($case->title) || $case->title === 'New ticket' || stripos($case->title, 'New ticket') === 0 || $case->title === 'New case' || stripos($case->title, 'New case') === 0) {
                    $case->title = substr($summary, 0, 80) . (strlen($summary) > 80 ? '...' : '');
                    $changed = true;
                }
                
                if ($changed) {
                    $case->save();
                }
            }

        } catch (\Throwable $e) {
            Log::error('VAPI_END_CALL_REPORT_ERROR', [
                'callId' => $vapiCallId,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-throw so the job retries
        }
    }
}
