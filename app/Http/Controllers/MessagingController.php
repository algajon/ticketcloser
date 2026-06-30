<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\CalendarConnection;
use App\Models\MessageEvent;
use App\Models\MessagingSetting;
use App\Models\SuggestedEvent;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessagingController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $settings = MessagingSetting::forWorkspace($workspace);
        $assistants = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->latest('updated_at')
            ->get();

        $phoneNumbers = WorkspacePhoneNumber::query()
            ->where('workspace_id', $workspace->id)
            ->latest('updated_at')
            ->get();

        $phonesByAssistant = $phoneNumbers
            ->filter(fn (WorkspacePhoneNumber $phoneNumber) => filled($phoneNumber->assistant_id))
            ->groupBy('assistant_id');

        $assistantRows = $assistants->map(function (AssistantConfig $assistant) use ($phonesByAssistant) {
            $assistantPhones = $phonesByAssistant->get($assistant->id, collect());
            $phone = $assistantPhones->first(fn (WorkspacePhoneNumber $candidate) => $this->phoneCanSendSms($candidate))
                ?? $assistantPhones->first();
            $assistantSynced = filled($assistant->vapi_assistant_id);
            $hasSmsNumber = $phone instanceof WorkspacePhoneNumber && $this->phoneCanSendSms($phone);
            $ready = $assistantSynced && $hasSmsNumber;

            $status = match (true) {
                $ready => ['label' => 'SMS ready', 'tone' => 'success'],
                ! $assistantSynced => ['label' => 'Sync assistant', 'tone' => 'warning'],
                ! $phone => ['label' => 'Assign number', 'tone' => 'warning'],
                ! $phone->is_active => ['label' => 'Activate number', 'tone' => 'warning'],
                blank($phone->vapi_phone_number_id) => ['label' => 'Needs Vapi number', 'tone' => 'warning'],
                blank($phone->e164) => ['label' => 'Missing caller line', 'tone' => 'warning'],
                default => ['label' => 'Needs review', 'tone' => 'slate'],
            };

            return [
                'assistant' => $assistant,
                'phone' => $phone,
                'ready' => $ready,
                'status' => $status,
            ];
        });

        $eventsQuery = MessageEvent::query()
            ->where('workspace_id', $workspace->id);
        $outboundQuery = (clone $eventsQuery)
            ->where('direction', MessageEvent::DIRECTION_OUTBOUND);
        $sentCount = (clone $outboundQuery)
            ->where(function ($query) {
                $query->whereNotNull('sent_at')
                    ->orWhereIn('status', [
                        MessageEvent::STATUS_SENT,
                        MessageEvent::STATUS_DELIVERED,
                        MessageEvent::STATUS_REPLIED,
                        MessageEvent::STATUS_CLICKED,
                        MessageEvent::STATUS_OPENED,
                    ]);
            })
            ->count();
        $deliveredCount = (clone $outboundQuery)
            ->where(function ($query) {
                $query->whereNotNull('delivered_at')
                    ->orWhereIn('status', [
                        MessageEvent::STATUS_DELIVERED,
                        MessageEvent::STATUS_REPLIED,
                        MessageEvent::STATUS_CLICKED,
                        MessageEvent::STATUS_OPENED,
                    ]);
            })
            ->count();
        $replyCount = (clone $eventsQuery)
            ->where(function ($query) {
                $query->where('direction', MessageEvent::DIRECTION_INBOUND)
                    ->orWhereNotNull('replied_at')
                    ->orWhere('status', MessageEvent::STATUS_REPLIED);
            })
            ->count();
        $openProxyCount = (clone $outboundQuery)
            ->where(function ($query) {
                $query->whereNotNull('opened_at')
                    ->orWhereNotNull('clicked_at')
                    ->orWhereNotNull('replied_at')
                    ->orWhereIn('status', [
                        MessageEvent::STATUS_OPENED,
                        MessageEvent::STATUS_CLICKED,
                        MessageEvent::STATUS_REPLIED,
                    ]);
            })
            ->count();
        $failedCount = (clone $outboundQuery)
            ->where(function ($query) {
                $query->whereNotNull('failed_at')
                    ->orWhere('status', MessageEvent::STATUS_FAILED);
            })
            ->count();

        $recentMessages = (clone $eventsQuery)
            ->with(['assistantConfig', 'contact', 'supportCase', 'calendarEvent'])
            ->latest()
            ->limit(8)
            ->get();

        $smsReadyCount = $assistantRows->where('ready', true)->count();
        $vapiNumberCount = $phoneNumbers->filter(fn (WorkspacePhoneNumber $phoneNumber) => $this->phoneCanSendSms($phoneNumber))->count();
        $calendarProviderCount = CalendarConnection::query()
            ->where('workspace_id', $workspace->id)
            ->count();
        $pendingBookingCount = SuggestedEvent::query()
            ->where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->count();
        $phoneNumbersLockedForFreePlan = $workspace->isFreePlan() && ! $workspace->bypassesPlanLimits();
        $openProxyRate = $sentCount > 0 ? (int) round(($openProxyCount / $sentCount) * 100) : 0;
        $deliveryRate = $sentCount > 0 ? (int) round(($deliveredCount / $sentCount) * 100) : 0;
        $replyRate = $sentCount > 0 ? (int) round(($replyCount / $sentCount) * 100) : 0;
        $messagePreview = $settings->renderPreview();
        $brandVoiceOptions = MessagingSetting::BRAND_VOICES;

        return view('messaging.index', compact(
            'workspace',
            'settings',
            'brandVoiceOptions',
            'assistants',
            'assistantRows',
            'phoneNumbers',
            'smsReadyCount',
            'vapiNumberCount',
            'calendarProviderCount',
            'pendingBookingCount',
            'phoneNumbersLockedForFreePlan',
            'sentCount',
            'deliveredCount',
            'failedCount',
            'replyCount',
            'openProxyCount',
            'openProxyRate',
            'deliveryRate',
            'replyRate',
            'recentMessages',
            'messagePreview'
        ));
    }

    public function update(Request $request, Workspace $workspace, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage messaging.');

        $data = $request->validate([
            'booking_confirmation_enabled' => ['nullable', 'boolean'],
            'booking_confirmation_template' => ['required', 'string', 'min:20', 'max:500'],
            'signature' => ['nullable', 'string', 'max:160'],
            'brand_voice' => ['required', 'string', Rule::in(array_keys(MessagingSetting::BRAND_VOICES))],
            'include_ticket_number' => ['nullable', 'boolean'],
            'include_issue_label' => ['nullable', 'boolean'],
            'reply_capture_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = MessagingSetting::forWorkspace($workspace);
        $settings->fill([
            'booking_confirmation_enabled' => (bool) ($data['booking_confirmation_enabled'] ?? false),
            'booking_confirmation_template' => trim($data['booking_confirmation_template']),
            'signature' => filled($data['signature'] ?? null) ? trim((string) $data['signature']) : null,
            'brand_voice' => $data['brand_voice'],
            'include_ticket_number' => (bool) ($data['include_ticket_number'] ?? false),
            'include_issue_label' => (bool) ($data['include_issue_label'] ?? false),
            'reply_capture_enabled' => (bool) ($data['reply_capture_enabled'] ?? false),
        ])->save();

        $synced = 0;
        $syncWarning = null;

        if (filled(config('services.vapi.key'))) {
            try {
                AssistantConfig::query()
                    ->where('workspace_id', $workspace->id)
                    ->whereNotNull('vapi_assistant_id')
                    ->get()
                    ->each(function (AssistantConfig $assistant) use ($workspace, $provisioner, &$synced) {
                        $provisioner->syncAssistantPayload($assistant, $workspace);
                        $synced++;
                    });
            } catch (\Throwable $e) {
                $syncWarning = 'Messaging was saved, but synced assistants could not be refreshed in Vapi: '.$e->getMessage();
            }
        }

        $redirect = redirect()
            ->route('app.messaging.index', $workspace)
            ->with('success', $synced > 0
                ? "Messaging saved and {$synced} synced assistant(s) refreshed."
                : 'Messaging saved.');

        if ($syncWarning) {
            $redirect->with('warning', $syncWarning);
        }

        return $redirect;
    }

    private function phoneCanSendSms(WorkspacePhoneNumber $phoneNumber): bool
    {
        return (bool) $phoneNumber->is_active
            && filled($phoneNumber->vapi_phone_number_id)
            && filled($phoneNumber->e164);
    }
}
