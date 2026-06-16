<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\SuggestedEvent;
use App\Services\Meetings\MeetingBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
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

        $clientId = trim((string) config('services.google.client_id'));
        $clientSecret = trim((string) config('services.google.client_secret'));
        $redirectUri = $this->googleRedirectUri();

        if ($clientId === '' || $clientSecret === '') {
            return redirect()
                ->route('app.calendar.settings')
                ->with('error', 'Google Calendar is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET, then clear the app config cache.');
        }

        $statePayload = [
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'nonce' => Str::random(40),
        ];
        $request->session()->put('google_oauth_state', $statePayload);
        $state = Crypt::encryptString(json_encode($statePayload));

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);

        return redirect($url);
    }

    /**
     * Google OAuth callback.
     */
    public function googleCallback(Request $request)
    {
        $encodedState = (string) $request->get('state', '');
        abort_unless($encodedState !== '', 400);

        try {
            $state = json_decode(Crypt::decryptString(urldecode($encodedState)), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            abort(400, 'Invalid OAuth state.');
        }

        $expectedState = $request->session()->pull('google_oauth_state');
        abort_unless(
            is_array($expectedState)
                && is_array($state)
                && hash_equals((string) ($expectedState['workspace_id'] ?? ''), (string) ($state['workspace_id'] ?? ''))
                && hash_equals((string) ($expectedState['user_id'] ?? ''), (string) ($state['user_id'] ?? ''))
                && hash_equals((string) ($expectedState['nonce'] ?? ''), (string) ($state['nonce'] ?? '')),
            403
        );

        $workspaceId = (int) ($state['workspace_id'] ?? 0);
        abort_unless($workspaceId > 0, 400);
        abort_unless($request->user()?->hasWorkspace($workspaceId), 403);

        $code = $request->get('code');
        $tokens = $this->exchangeGoogleCode($code);
        if (!$tokens) {
            return redirect()->route('app.calendar.settings')->with('error', 'Google auth failed.');
        }

        $connection = CalendarConnection::updateOrCreate(
            ['workspace_id' => $workspaceId, 'provider' => 'google'],
            [
                'tokens_encrypted' => '-',
                'expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                'metadata' => ['scope' => $tokens['scope'] ?? null],
            ]
        );
        $connection->tokens = $tokens;
        $connection->save();

        return redirect()->route('app.calendar.settings')->with('success', 'Google Calendar connected!');
    }

    /**
     * Confirm a suggested event and send the user to the selected provider.
     */
    public function confirm(Request $request, SuggestedEvent $suggestedEvent, MeetingBookingService $meetingBooking)
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

        try {
            $event = $meetingBooking->confirmSuggestedEvent($suggestedEvent, $provider, $startsAt, $endsAt);
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'url' => $event->url,
                'event_id' => $event->id,
            ]);
        }

        if (in_array($provider, ['google', 'calendly'], true) && $event->url) {
            return redirect()->away($event->url);
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

    protected function exchangeGoogleCode(string $code): ?array
    {
        $clientId = trim((string) config('services.google.client_id'));
        $clientSecret = trim((string) config('services.google.client_secret'));

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $response = \Illuminate\Support\Facades\Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->googleRedirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        return $response->ok() ? $response->json() : null;
    }

    protected function googleRedirectUri(): string
    {
        return trim((string) config('services.google.redirect')) ?: route('app.calendar.google.callback');
    }
}
