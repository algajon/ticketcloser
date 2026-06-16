<?php

namespace Tests\Feature;

use App\Jobs\ExtractSuggestedEvents;
use App\Models\CalendarConnection;
use App\Models\Contact;
use App\Models\SuggestedEvent;
use App\Models\SupportCase;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CalendarBookingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workspace $workspace;
    protected SupportCase $case;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->markEmailAsVerified();
        $this->workspace = Workspace::factory()->create([
            'onboarding_step' => 'done',
        ]);
        \DB::table('workspace_memberships')->insert([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        session(['current_workspace_id' => $this->workspace->id]);
        $this->actingAs($this->user);

        // Fake queue to prevent actual job dispatching in setup
        Queue::fake();
        $this->case = SupportCase::create([
            'workspace_id' => $this->workspace->id,
            'case_number' => 'TC-CAL001',
            'title' => 'Test Case',
            'description' => 'A test case description.',
            'status' => 'new',
            'priority' => 'normal',
            'source' => 'web',
        ]);
        Queue::assertPushed(ExtractSuggestedEvents::class);
    }

    /** @test */
    public function calendar_page_is_accessible(): void
    {
        $response = $this->get(route('app.calendar.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function calendar_settings_page_is_accessible(): void
    {
        $response = $this->get(route('app.calendar.settings'));
        $response->assertStatus(200);
    }

    /** @test */
    public function google_auth_does_not_redirect_to_google_when_oauth_credentials_are_missing(): void
    {
        Config::set('services.google.client_id', null);
        Config::set('services.google.client_secret', null);

        $response = $this->get(route('app.calendar.google.auth'));

        $response->assertRedirect(route('app.calendar.settings'));
        $response->assertSessionHas('error', function (string $message): bool {
            return str_contains($message, 'Google Calendar is not configured yet')
                && str_contains($message, 'GOOGLE_CLIENT_ID')
                && str_contains($message, 'GOOGLE_CLIENT_SECRET');
        });
    }

    /** @test */
    public function creating_a_case_dispatches_extract_job(): void
    {
        // Already verified in setUp via Queue::assertPushed
        $this->assertTrue(true);
    }

    /** @test */
    public function extract_job_creates_suggested_event_for_date_phrase(): void
    {
        // Run job without queue
        $caseWithDate = SupportCase::withoutEvents(function () {
            return SupportCase::create([
                'workspace_id' => $this->workspace->id,
                'case_number' => 'TC-CAL002',
                'title' => 'Meeting request',
                'description' => 'I need a meeting tomorrow at 10am to discuss the lease.',
                'status' => 'new',
                'priority' => 'normal',
                'source' => 'web',
            ]);
        });

        (new ExtractSuggestedEvents($caseWithDate))->handle();

        $this->assertDatabaseHas('suggested_events', [
            'workspace_id' => $this->workspace->id,
            'case_id' => $caseWithDate->id,
        ]);
    }

    /** @test */
    public function confirming_suggested_event_creates_calendar_event(): void
    {
        $suggestion = SuggestedEvent::create([
            'workspace_id' => $this->workspace->id,
            'case_id' => $this->case->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'confidence' => 75,
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('app.calendar.confirm', $suggestion), [
            'provider' => 'ics',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('calendar_events', [
            'suggested_event_id' => $suggestion->id,
            'provider' => 'ics',
        ]);
        $this->assertDatabaseHas('suggested_events', [
            'id' => $suggestion->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function confirming_with_google_creates_prefilled_google_event_and_redirects_to_it(): void
    {
        Http::fake([
            'https://www.googleapis.com/calendar/v3/calendars/primary/events' => Http::response([
                'id' => 'google-event-cal-1',
                'htmlLink' => 'https://calendar.google.com/event?eid=cal-1',
            ]),
        ]);

        $connection = CalendarConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'google',
            'tokens_encrypted' => '-',
        ]);
        $connection->tokens = ['access_token' => 'google-token-123'];
        $connection->save();

        $contact = Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Ada Lovelace',
            'phone_e164' => '+12165550123',
            'email' => 'ada@example.com',
            'property_code' => 'Building A',
            'unit' => '5B',
        ]);
        $this->case->update([
            'contact_id' => $contact->id,
            'requester_phone' => '+12165550123',
            'requester_email' => 'ada@example.com',
            'access_notes' => 'Use the side door.',
            'preferred_visit_window' => 'Weekday mornings',
            'structured_payload' => ['propertyCode' => 'Building A', 'unit' => '5B'],
        ]);

        $startsAt = now()->addDays(2)->setTime(10, 30);
        $suggestion = SuggestedEvent::create([
            'workspace_id' => $this->workspace->id,
            'case_id' => $this->case->id,
            'contact_id' => $contact->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'timezone' => 'Europe/Budapest',
            'confidence' => 90,
            'status' => 'pending',
        ]);

        $response = $this->post(route('app.calendar.confirm', $suggestion), [
            'provider' => 'google',
        ]);

        $response->assertRedirect('https://calendar.google.com/event?eid=cal-1');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://www.googleapis.com/calendar/v3/calendars/primary/events'
                && $request['summary'] === 'Case #TC-CAL001: Test Case'
                && str_contains($request['description'], 'Caller: Ada Lovelace')
                && str_contains($request['description'], 'Phone: +12165550123')
                && str_contains($request['description'], 'Property: Building A Unit 5B')
                && str_contains($request['description'], 'Access notes: Use the side door.')
                && $request['attendees'][0]['email'] === 'ada@example.com'
                && $request['attendees'][0]['displayName'] === 'Ada Lovelace'
                && $request['start']['timeZone'] === 'Europe/Budapest'
                && $request['end']['timeZone'] === 'Europe/Budapest';
        });

        $this->assertDatabaseHas('calendar_events', [
            'suggested_event_id' => $suggestion->id,
            'provider' => 'google',
            'provider_event_id' => 'google-event-cal-1',
            'url' => 'https://calendar.google.com/event?eid=cal-1',
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function confirming_with_calendly_redirects_to_prefilled_scheduling_link(): void
    {
        CalendarConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'calendly',
            'tokens_encrypted' => '-',
            'calendly_scheduling_link' => 'https://calendly.com/tickit/intake?hide_gdpr_banner=1',
        ]);

        $contact = Contact::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Ada Lovelace',
            'phone_e164' => '+12165550123',
            'email' => 'ada@example.com',
            'property_code' => 'Building A',
            'unit' => '5B',
        ]);
        $this->case->update([
            'contact_id' => $contact->id,
            'requester_phone' => '+12165550123',
            'requester_email' => 'ada@example.com',
            'access_notes' => 'Use the side door.',
            'preferred_visit_window' => 'Weekday mornings',
            'structured_payload' => ['propertyCode' => 'Building A', 'unit' => '5B'],
        ]);

        $startsAt = now()->addDays(3)->setTime(14, 0);
        $suggestion = SuggestedEvent::create([
            'workspace_id' => $this->workspace->id,
            'case_id' => $this->case->id,
            'contact_id' => $contact->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(30),
            'timezone' => 'Europe/Budapest',
            'confidence' => 90,
            'status' => 'pending',
        ]);

        $response = $this->post(route('app.calendar.confirm', $suggestion), [
            'provider' => 'calendly',
        ]);

        $redirectUrl = $response->headers->get('Location');
        $response->assertRedirect();

        parse_str((string) parse_url($redirectUrl, PHP_URL_QUERY), $query);

        $this->assertStringStartsWith('https://calendly.com/tickit/intake', $redirectUrl);
        $this->assertSame('1', $query['hide_gdpr_banner']);
        $this->assertSame('Ada Lovelace', $query['name']);
        $this->assertSame('ada@example.com', $query['email']);
        $this->assertSame($startsAt->format('Y-m'), $query['month']);
        $this->assertSame($startsAt->format('Y-m-d'), $query['date']);
        $this->assertSame('+12165550123', $query['a1']);
        $this->assertSame('Case #TC-CAL001', $query['a2']);
        $this->assertSame('Test Case', $query['a3']);
        $this->assertSame('Building A Unit 5B', $query['a4']);
        $this->assertSame('Preferred window: Weekday mornings | Access: Use the side door.', $query['a5']);

        $this->assertDatabaseHas('calendar_events', [
            'suggested_event_id' => $suggestion->id,
            'provider' => 'calendly',
            'contact_id' => $contact->id,
            'url' => $redirectUrl,
        ]);
    }

    /** @test */
    public function dismissing_suggested_event_sets_status_dismissed(): void
    {
        $suggestion = SuggestedEvent::create([
            'workspace_id' => $this->workspace->id,
            'case_id' => $this->case->id,
            'starts_at' => now()->addDay(),
            'confidence' => 60,
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('app.calendar.dismiss', $suggestion));

        $response->assertStatus(200);
        $this->assertDatabaseHas('suggested_events', [
            'id' => $suggestion->id,
            'status' => 'dismissed',
        ]);
    }
}
