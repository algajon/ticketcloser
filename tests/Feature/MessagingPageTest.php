<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\MessagingSetting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_messaging_page_shows_sidebar_entry_and_sms_ready_assistant(): void
    {
        config(['services.vapi.key' => 'vapi_test_key']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'onboarding_step' => 'done',
            'name' => 'Northline Support',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_messaging_ready',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Maintenance Desk',
            'vapi_assistant_id' => 'asst_sms_ready',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'e164' => '+18005550123',
            'vapi_phone_number_id' => 'pn_sms_ready',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->get(route('app.messaging.index', $workspace));

        $response
            ->assertOk()
            ->assertSee('Messaging')
            ->assertSee('Customize messages')
            ->assertSee('Chat preview')
            ->assertSee('Caller name')
            ->assertSee('Business name')
            ->assertSee('Appointment time')
            ->assertSee('When a booking is confirmed')
            ->assertDontSee('bookMeeting')
            ->assertSee('Open / read proxy')
            ->assertSee('Responses')
            ->assertSee('SMS-ready assistants')
            ->assertSee('SMS ready')
            ->assertSee('Maintenance Desk')
            ->assertSee('+18005550123')
            ->assertSee('Edit')
            ->assertSee('Number');
    }

    public function test_workspace_manager_can_customize_messaging_template(): void
    {
        config(['services.vapi.key' => null]);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'onboarding_step' => 'done',
            'name' => 'Northline Support',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_MANAGER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_messaging_customize',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['current_workspace_id' => $workspace->id])
            ->post(route('app.messaging.update', $workspace), [
                'booking_confirmation_enabled' => '1',
                'booking_confirmation_template' => 'Hi {{customer_name}}, {{workspace_name}} booked you for {{appointment_time}}. {{ticket_number}} {{signature}}',
                'signature' => '- Northline',
                'brand_voice' => 'brief',
                'include_ticket_number' => '1',
                'reply_capture_enabled' => '1',
            ]);

        $response
            ->assertRedirect(route('app.messaging.index', $workspace))
            ->assertSessionHas('success');

        $settings = MessagingSetting::where('workspace_id', $workspace->id)->firstOrFail();

        $this->assertSame('brief', $settings->brand_voice);
        $this->assertSame('- Northline', $settings->signature);
        $this->assertTrue($settings->booking_confirmation_enabled);
        $this->assertTrue($settings->include_ticket_number);
        $this->assertFalse($settings->include_issue_label);
        $this->assertStringContainsString('{{appointment_time}}', $settings->booking_confirmation_template);
    }
}
