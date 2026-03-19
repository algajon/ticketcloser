<?php

namespace Tests\Feature;

use App\Jobs\ExtractSuggestedEvents;
use App\Models\SuggestedEvent;
use App\Models\SupportCase;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->workspace = Workspace::factory()->create();
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
