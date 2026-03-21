<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\SuggestedEvent;
use App\Models\SupportCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalendarController extends Controller
{
    /**
     * Calendar overview page.
     */
    public function index(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        $connections = CalendarConnection::where('workspace_id', $workspace->id)->get()->keyBy('provider');
        $upcoming = CalendarEvent::where('workspace_id', $workspace->id)
            ->where('starts_at', '>=', now())
            ->where('status', 'created')
            ->orderBy('starts_at')
            ->with('supportCase')
            ->take(10)
            ->get();
        $suggested = SuggestedEvent::where('workspace_id', $workspace->id)
            ->where('status', 'pending')
            ->orderBy('starts_at', 'asc')
            ->with('supportCase')
            ->get();

        return view('calendar.index', compact('workspace', 'connections', 'upcoming', 'suggested'));
    }

    /**
     * Calendar settings page.
     */
    public function settings(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        $connections = CalendarConnection::where('workspace_id', $workspace->id)->get()->keyBy('provider');
        return view('calendar.settings', compact('workspace', 'connections'));
    }

    /**
     * Save Calendly scheduling link.
     */
    public function saveCalendlyLink(Request $request)
    {
        $request->validate(['calendly_link' => 'required|url|max:500']);
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 403);

        CalendarConnection::updateOrCreate(
            ['workspace_id' => $workspace->id, 'provider' => 'calendly'],
            [
                'tokens_encrypted' => '-',
                'calendly_scheduling_link' => $request->calendly_link,
            ]
        );

        return back()->with('success', 'Calendly scheduling link saved.');
    }

    /**
     * Redirect to Google OAuth.
     */
    public function googleAuth(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 403);

        $clientId = config('services.google.client_id');
        $redirectUri = route('app.calendar.google.callback');
        $scopes = urlencode('https://www.googleapis.com/auth/calendar.events');
        $state = base64_encode(json_encode(['workspace_id' => $workspace->id]));

        $url = "https://accounts.google.com/o/oauth2/v2/auth"
            . "?client_id={$clientId}"
            . "&redirect_uri={$redirectUri}"
            . "&response_type=code"
            . "&scope={$scopes}"
            . "&access_type=offline"
            . "&prompt=consent"
            . "&state={$state}";

        return redirect($url);
    }

    /**
     * Google OAuth callback.
     */
    public function googleCallback(Request $request)
    {
        $state = json_decode(base64_decode($request->get('state', '')), true);
        $workspaceId = $state['workspace_id'] ?? null;
        abort_unless($workspaceId, 400);

        $code = $request->get('code');
        $tokens = $this->exchangeGoogleCode($code);
        if (!$tokens)
            return redirect()->route('app.calendar.settings')->with('error', 'Google auth failed.');

        $conn = CalendarConnection::updateOrCreate(
            ['workspace_id' => $workspaceId, 'provider' => 'google'],
            [
                'tokens_encrypted' => '-',
                'expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                'metadata' => ['scope' => $tokens['scope'] ?? null],
            ]
        );
        $conn->tokens = $tokens;
        $conn->save();

        return redirect()->route('app.calendar.settings')->with('success', 'Google Calendar connected!');
    }

    /**
     * Confirm a suggested event and create a calendar booking.
     */
    public function confirm(Request $request, SuggestedEvent $suggestedEvent)
    {
        abort_unless($suggestedEvent->workspace_id === $request->user()->currentWorkspace()?->id, 403);

        $validated = $request->validate([
            'provider' => 'required|in:google,calendly,ics',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        $provider = $validated['provider'];
        $startsAt = isset($validated['starts_at']) ? \Carbon\Carbon::parse($validated['starts_at']) : $suggestedEvent->starts_at;
        $endsAt = isset($validated['ends_at']) ? \Carbon\Carbon::parse($validated['ends_at']) : $suggestedEvent->ends_at;

        $url = null;
        $providerEventId = null;

        if ($provider === 'ics') {
            // ICS download — handled client-side via JS, just record it
        } elseif ($provider === 'calendly') {
            $conn = CalendarConnection::where('workspace_id', $suggestedEvent->workspace_id)
                ->where('provider', 'calendly')
                ->first();
            $baseLink = $conn?->calendly_scheduling_link ?? '';
            
            if ($baseLink) {
                $case = $suggestedEvent->supportCase;
                $contact = $case?->contact;
                
                $name = urlencode(trim(($contact?->name ?? '')));
                $email = urlencode(trim(($case?->requester_email ?? $contact?->email ?? '')));
                
                $params = [];
                if ($name) $params['name'] = $name;
                if ($email) $params['email'] = $email;
                if ($startsAt) {
                    $params['month'] = $startsAt->format('Y-m');
                    $params['date'] = $startsAt->format('Y-m-d');
                }
                
                $queryString = urldecode(http_build_query($params));
                $separator = str_contains($baseLink, '?') ? '&' : '?';
                $url = $baseLink . $separator . $queryString;
            } else {
                $url = null;
            }
        } elseif ($provider === 'google') {
            $url = $this->createGoogleEvent($suggestedEvent, $startsAt, $endsAt);
        }

        $event = CalendarEvent::create([
            'workspace_id' => $suggestedEvent->workspace_id,
            'case_id' => $suggestedEvent->case_id,
            'suggested_event_id' => $suggestedEvent->id,
            'provider' => $provider,
            'provider_event_id' => $providerEventId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'created',
            'url' => $url,
        ]);

        $suggestedEvent->update(['status' => 'confirmed']);

        Log::info('CalendarController: event confirmed', [
            'calendar_event_id' => $event->id,
            'provider' => $provider,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'url' => $url,
                'event_id' => $event->id,
            ]);
        }

        if ($provider === 'calendly' && $url) {
            return redirect()->away($url);
        }

        return back()->with('success', 'Meeting confirmed and added to calendar.');
    }

    /**
     * Dismiss a suggested event.
     */
    public function dismiss(Request $request, SuggestedEvent $suggestedEvent)
    {
        abort_unless($suggestedEvent->workspace_id === $request->user()->currentWorkspace()?->id, 403);
        $suggestedEvent->update(['status' => 'dismissed']);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Suggested meeting was dismissed.');
    }

    // ── Private helpers ──────────────────────────────────────────────

    protected function exchangeGoogleCode(string $code): ?array
    {
        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('app.calendar.google.callback'),
            'grant_type' => 'authorization_code',
        ]);

        return $response->ok() ? $response->json() : null;
    }

    protected function createGoogleEvent(SuggestedEvent $suggested, $startsAt, $endsAt): ?string
    {
        $conn = CalendarConnection::where('workspace_id', $suggested->workspace_id)
            ->where('provider', 'google')
            ->first();
        if (!$conn)
            return null;

        $tokens = $conn->tokens;
        $case = $suggested->supportCase;
        $summary = $case ? "Case #{$case->id}: {$case->subject}" : 'Scheduled from TicketCloser';

        $response = \Illuminate\Support\Facades\Http::withToken($tokens['access_token'] ?? '')
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'summary' => $summary,
                'description' => $case?->description ?? '',
                'start' => ['dateTime' => $startsAt?->toRfc3339String(), 'timeZone' => $suggested->timezone],
                'end' => ['dateTime' => $endsAt?->toRfc3339String(), 'timeZone' => $suggested->timezone],
            ]);

        if ($response->ok()) {
            return $response->json('htmlLink');
        }

        Log::warning('CalendarController: Google event creation failed', ['response' => $response->json()]);
        return null;
    }
}
