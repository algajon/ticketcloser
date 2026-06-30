<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\CalendarConnection;
use App\Models\SuggestedEvent;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Http\Request;

class MessagingController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

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

        return view('messaging.index', compact(
            'workspace',
            'assistants',
            'assistantRows',
            'phoneNumbers',
            'smsReadyCount',
            'vapiNumberCount',
            'calendarProviderCount',
            'pendingBookingCount',
            'phoneNumbersLockedForFreePlan'
        ));
    }

    private function phoneCanSendSms(WorkspacePhoneNumber $phoneNumber): bool
    {
        return (bool) $phoneNumber->is_active
            && filled($phoneNumber->vapi_phone_number_id)
            && filled($phoneNumber->e164);
    }
}
