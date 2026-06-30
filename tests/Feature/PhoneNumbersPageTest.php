<?php

namespace Tests\Feature;

use App\Models\AssistantConfig;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiClient;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PhoneNumbersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_numbers_page_defaults_to_the_assistant_that_has_a_phone_number(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['plan_key' => 'free']);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_redirect_live',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_redirect',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Draft Assistant',
        ]);

        $liveAssistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $liveAssistant->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('app.phone_numbers.index', $workspace));

        $response
            ->assertOk()
            ->assertSee('+14155550123')
            ->assertSee('Live Assistant')
            ->assertSee('Upgrade required')
            ->assertSee('Upgrade to connect a number')
            ->assertSee('Import my current number')
            ->assertSee('Germany')
            ->assertSee('US / Canada');
    }

    public function test_free_workspaces_cannot_assign_phone_numbers(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['plan_key' => 'free']);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->never())->method('provisionPhoneNumber');
        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->from(route('app.phone_numbers.index', $workspace))
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'area_code' => '415',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', $workspace, false))
            ->assertSessionHas('error', 'Free workspaces cannot connect a live phone number. Upgrade to assign a number to this assistant.');
    }

    public function test_store_phone_number_redirects_back_to_the_selected_assistant(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['plan_key' => 'startup']);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_redirect_live',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Live Assistant',
            'vapi_assistant_id' => 'asst_live_123',
        ]);

        $record = WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_live_123',
            'is_active' => true,
        ]);

        $service = $this->createMock(VapiProvisioningService::class);
        $service->expects($this->once())
            ->method('provisionPhoneNumber')
            ->willReturn($record);

        $this->app->instance(VapiProvisioningService::class, $service);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'area_code' => '415',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionMissing('phone_activation_countdown');
    }

    public function test_store_phone_number_flashes_activation_countdown_for_new_number(): void
    {
        Carbon::setTestNow('2026-03-31 10:15:00');

        try {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['plan_key' => 'startup']);

            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceMembership::ROLE_OWNER,
            ]);

            Subscription::create([
                'workspace_id' => $workspace->id,
                'stripe_subscription_id' => 'sub_phone_countdown',
                'plan_key' => 'startup',
                'status' => 'active',
            ]);

            $assistant = AssistantConfig::create([
                'workspace_id' => $workspace->id,
                'name' => 'Fresh Assistant',
                'vapi_assistant_id' => 'asst_fresh_123',
            ]);

            $record = new WorkspacePhoneNumber([
                'workspace_id' => $workspace->id,
                'assistant_id' => $assistant->id,
                'e164' => '+14155550124',
                'vapi_phone_number_id' => 'pn_fresh_123',
                'activation_started_at' => Carbon::now(),
                'is_active' => true,
            ]);

            $service = $this->createMock(VapiProvisioningService::class);
            $service->expects($this->once())
                ->method('provisionPhoneNumber')
                ->willReturn($record);

            $this->app->instance(VapiProvisioningService::class, $service);

            $response = $this
                ->actingAs($user)
                ->post(route('app.phone_numbers.store', $workspace), [
                    'assistant_id' => $assistant->id,
                    'area_code' => '415',
                ]);

            $response->assertSessionHas('phone_activation_countdown', function (array $countdown) use ($assistant) {
                return $countdown['assistant_id'] === $assistant->id
                    && $countdown['ends_at'] === Carbon::now()->addSeconds(180)->toIso8601String();
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_phone_numbers_page_shows_activation_countdown_for_newly_provisioned_number(): void
    {
        Carbon::setTestNow('2026-03-31 10:15:00');

        try {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['plan_key' => 'free']);

            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceMembership::ROLE_OWNER,
            ]);

            Subscription::create([
                'workspace_id' => $workspace->id,
                'stripe_subscription_id' => 'sub_phone_forwarding',
                'plan_key' => 'startup',
                'status' => 'active',
            ]);

            $assistant = AssistantConfig::create([
                'workspace_id' => $workspace->id,
                'name' => 'Fresh Assistant',
                'vapi_assistant_id' => 'asst_fresh_123',
            ]);

            WorkspacePhoneNumber::create([
                'workspace_id' => $workspace->id,
                'assistant_id' => $assistant->id,
                'e164' => '+14155550124',
                'vapi_phone_number_id' => 'pn_fresh_123',
                'activation_started_at' => Carbon::now()->subSeconds(45),
                'is_active' => true,
            ]);

            $response = $this
                ->actingAs($user)
                ->get(route('app.phone_numbers.index', [
                    'workspace' => $workspace,
                    'assistant_id' => $assistant->id,
                ]));

            $response
                ->assertOk()
                ->assertSee('Activation countdown')
                ->assertSee('This number was just added.')
                ->assertSee('Activating');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_phone_numbers_page_hides_persisted_activation_countdown_after_it_expires(): void
    {
        Carbon::setTestNow('2026-03-31 10:20:00');

        try {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create(['plan_key' => 'startup']);

            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceMembership::ROLE_OWNER,
            ]);

            Subscription::create([
                'workspace_id' => $workspace->id,
                'stripe_subscription_id' => 'sub_phone_countdown_expired',
                'plan_key' => 'startup',
                'status' => 'active',
            ]);

            $assistant = AssistantConfig::create([
                'workspace_id' => $workspace->id,
                'name' => 'Already Active Assistant',
                'vapi_assistant_id' => 'asst_active_123',
            ]);

            WorkspacePhoneNumber::create([
                'workspace_id' => $workspace->id,
                'assistant_id' => $assistant->id,
                'e164' => '+14155550125',
                'vapi_phone_number_id' => 'pn_active_123',
                'activation_started_at' => Carbon::now()->subMinutes(4),
                'is_active' => true,
            ]);

            $response = $this
                ->actingAs($user)
                ->get(route('app.phone_numbers.index', [
                    'workspace' => $workspace,
                    'assistant_id' => $assistant->id,
                ]));

            $response
                ->assertOk()
                ->assertDontSee('Activation countdown')
                ->assertSee('Active');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_existing_business_number_can_create_a_forwarding_target_immediately(): void
    {
        Carbon::setTestNow('2026-03-31 10:30:00');

        try {
            $user = User::factory()->create();
            $workspace = Workspace::factory()->create([
                'plan_key' => 'startup',
                'primary_market' => 'global',
            ]);

            WorkspaceMembership::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'role' => WorkspaceMembership::ROLE_OWNER,
            ]);

            Subscription::create([
                'workspace_id' => $workspace->id,
                'stripe_subscription_id' => 'sub_phone_import_plan',
                'plan_key' => 'startup',
                'status' => 'active',
            ]);

            $assistant = AssistantConfig::create([
                'workspace_id' => $workspace->id,
                'name' => 'Forwarding Assistant',
                'vapi_assistant_id' => 'asst_forward_123',
            ]);

            $client = $this->createMock(VapiClient::class);
            $client->expects($this->once())
                ->method('createPhoneNumber')
                ->with($this->callback(function (array $payload) use ($assistant) {
                    return $payload['provider'] === 'vapi'
                        && $payload['assistantId'] === $assistant->vapi_assistant_id;
                }))
                ->willReturn([
                    'id' => 'pn_forward_123',
                    'number' => '+14155550999',
                ]);

            $this->app->instance(VapiClient::class, $client);

            $response = $this
                ->actingAs($user)
                ->post(route('app.phone_numbers.store', $workspace), [
                    'assistant_id' => $assistant->id,
                    'provisioning_mode' => 'existing_business_number',
                    'forwarding_number' => '+14155550111',
                    'auto_forwarding_target' => '1',
                ]);

            $response
                ->assertRedirect(route('app.phone_numbers.index', [
                    'workspace' => $workspace,
                    'assistant_id' => $assistant->id,
                ], false))
                ->assertSessionHas('success', 'Forward your existing number to +14155550999 whenever you are ready to switch calls over.');

            $this->assertDatabaseHas('workspace_phone_numbers', [
                'workspace_id' => $workspace->id,
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'existing_business_number',
                'forwarding_number' => '+14155550111',
                'vapi_phone_number_id' => 'pn_forward_123',
                'e164' => '+14155550999',
                'is_active' => true,
            ]);

            $phoneNumber = WorkspacePhoneNumber::query()->where('vapi_phone_number_id', 'pn_forward_123')->firstOrFail();
            $this->assertTrue(Carbon::now()->equalTo($phoneNumber->activation_started_at));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_existing_number_import_plan_can_be_saved_without_a_vapi_credential(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_import_live',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Import Assistant',
            'vapi_assistant_id' => 'asst_import_123',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'manual',
                'existing_number_country' => 'de',
                'forwarding_number' => '+49 30 1234567',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionHas('success', 'Import details saved. Add or confirm a Vapi BYO credential when you are ready to attach this number live.');

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'provisioning_mode' => 'external_provider',
            'external_provider' => 'manual',
            'forwarding_number' => '+49 30 1234567',
            'vapi_phone_number_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_twilio_number_already_in_vapi_can_be_attached_and_shows_status(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_twilio_import',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sales Assistant',
            'vapi_assistant_id' => 'asst_sales_123',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->once())
            ->method('getPhoneNumber')
            ->with('pn_twilio_trial_123')
            ->willReturn([
                'id' => 'pn_twilio_trial_123',
                'number' => '+13613263105',
            ]);
        $client->expects($this->once())
            ->method('updatePhoneNumber')
            ->with('pn_twilio_trial_123', $this->callback(function (array $payload) use ($assistant, $workspace) {
                return $payload['assistantId'] === $assistant->vapi_assistant_id
                    && $payload['name'] === $workspace->name.' Support'
                    && isset($payload['serverUrl']);
            }))
            ->willReturn([
                'id' => 'pn_twilio_trial_123',
                'number' => '+13613263105',
            ]);

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'twilio',
                'existing_number_country' => 'us',
                'forwarding_number' => '+1 (361) 326 3105',
                'vapi_phone_number_id' => 'pn_twilio_trial_123',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionHas('success', 'Existing number imported and linked to the assistant.');

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'provisioning_mode' => 'external_provider',
            'external_provider' => 'twilio',
            'forwarding_number' => '+1 (361) 326 3105',
            'vapi_phone_number_id' => 'pn_twilio_trial_123',
            'e164' => '+13613263105',
            'is_active' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ]))
            ->assertOk()
            ->assertSee('Number status')
            ->assertSee('+13613263105')
            ->assertSee('Assigned to Sales Assistant')
            ->assertSee('Twilio')
            ->assertSee('Vapi linked')
            ->assertSee('SMS ready');
    }

    public function test_twilio_import_through_tickit_creates_vapi_phone_number_and_assigns_it(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_twilio_create',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Sales Assistant',
            'vapi_assistant_id' => 'asst_sales_456',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->method('updateAssistant')->willReturn([]);
        $client->expects($this->once())
            ->method('listPhoneNumbers')
            ->willReturn([]);
        $client->expects($this->once())
            ->method('createPhoneNumber')
            ->with($this->callback(function (array $payload) use ($assistant, $workspace) {
                return $payload['provider'] === 'twilio'
                    && $payload['number'] === '+13613263105'
                    && $payload['twilioAccountSid'] === 'ACTestOnlySidNeverReal123'
                    && $payload['twilioAuthToken'] === 'token_secret'
                    && $payload['smsEnabled'] === true
                    && $payload['assistantId'] === $assistant->vapi_assistant_id
                    && $payload['name'] === $workspace->name.' Support'
                    && data_get($payload, 'server.url') === config('services.vapi.webhook_url');
            }))
            ->willReturn([
                'id' => 'pn_twilio_created_123',
                'number' => '+13613263105',
                'provider' => 'twilio',
            ]);

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'twilio',
                'existing_number_country' => 'us',
                'forwarding_number' => '+1 (361) 326 3105',
                'twilio_account_sid' => 'ACTestOnlySidNeverReal123',
                'twilio_auth_token' => 'token_secret',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionHas('success', 'Existing number imported and linked to the assistant.');

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'provisioning_mode' => 'external_provider',
            'external_provider' => 'twilio',
            'forwarding_number' => '+1 (361) 326 3105',
            'vapi_phone_number_id' => 'pn_twilio_created_123',
            'e164' => '+13613263105',
            'is_active' => true,
        ]);
    }

    public function test_twilio_import_reuses_existing_vapi_number_found_by_phone_number(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_twilio_find',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Property Maintenance Assistant',
            'vapi_assistant_id' => 'asst_pm_456',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->method('updateAssistant')->willReturn([]);
        $client->expects($this->once())
            ->method('listPhoneNumbers')
            ->willReturn([
                [
                    'id' => 'pn_twilio_existing_123',
                    'number' => '+13613263105',
                    'provider' => 'twilio',
                ],
            ]);
        $client->expects($this->once())
            ->method('updatePhoneNumber')
            ->with('pn_twilio_existing_123', $this->callback(function (array $payload) use ($assistant, $workspace) {
                return ! isset($payload['provider'])
                    && $payload['number'] === '+13613263105'
                    && $payload['twilioAccountSid'] === 'ACTestOnlySidNeverReal123'
                    && $payload['twilioAuthToken'] === 'token_secret'
                    && $payload['smsEnabled'] === true
                    && $payload['assistantId'] === $assistant->vapi_assistant_id
                    && $payload['name'] === $workspace->name.' Support'
                    && data_get($payload, 'server.url') === config('services.vapi.webhook_url');
            }))
            ->willReturn([
                'id' => 'pn_twilio_existing_123',
                'number' => '+13613263105',
                'provider' => 'twilio',
            ]);
        $client->expects($this->never())->method('createPhoneNumber');

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'twilio',
                'existing_number_country' => 'us',
                'forwarding_number' => '+1 (361) 326 3105',
                'twilio_account_sid' => 'ACTestOnlySidNeverReal123',
                'twilio_auth_token' => 'token_secret',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionHas('success', 'Existing number imported and linked to the assistant.');

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'external_provider' => 'twilio',
            'vapi_phone_number_id' => 'pn_twilio_existing_123',
            'e164' => '+13613263105',
        ]);
    }

    public function test_twilio_import_rejects_login_email_autofilled_as_account_sid(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_twilio_email_sid',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Property Maintenance Assistant',
            'vapi_assistant_id' => 'asst_pm_789',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->never())->method('listPhoneNumbers');
        $client->expects($this->never())->method('createPhoneNumber');
        $client->expects($this->never())->method('updatePhoneNumber');

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->from(route('app.phone_numbers.index', $workspace))
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'twilio',
                'existing_number_country' => 'us',
                'forwarding_number' => '+1 (361) 326 3105',
                'twilio_account_sid' => 'algajon123@gmail.com',
                'twilio_auth_token' => 'token_secret',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', $workspace, false))
            ->assertSessionHasErrors([
                'twilio_account_sid' => 'Twilio Account SIDs start with AC. Paste the Account SID from Twilio, not your login email.',
            ]);
    }

    public function test_twilio_import_rejects_a_vapi_id_for_a_different_number(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_twilio_mismatch',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'Property Maintenance Assistant',
            'vapi_assistant_id' => 'asst_pm_123',
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->once())
            ->method('getPhoneNumber')
            ->with('pn_wrong_existing_123')
            ->willReturn([
                'id' => 'pn_wrong_existing_123',
                'number' => '+16823321532',
            ]);
        $client->expects($this->never())->method('updatePhoneNumber');

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->from(route('app.phone_numbers.index', $workspace))
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'twilio',
                'existing_number_country' => 'us',
                'forwarding_number' => '+1 (361) 326 3105',
                'vapi_phone_number_id' => 'pn_wrong_existing_123',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', $workspace, false))
            ->assertSessionHas('error', 'Provisioning failed: That Vapi phone number ID belongs to +16823321532, not +13613263105. Paste the Vapi phone ID for the number you are importing.');

        $this->assertDatabaseMissing('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'vapi_phone_number_id' => 'pn_wrong_existing_123',
        ]);
    }

    public function test_importing_an_existing_number_rebuilds_the_binding_when_switching_from_an_instant_number(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create([
            'plan_key' => 'startup',
            'primary_market' => 'global',
            'default_vapi_credential_id' => 'cred_saved_123',
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        Subscription::create([
            'workspace_id' => $workspace->id,
            'stripe_subscription_id' => 'sub_phone_import_rebuild',
            'plan_key' => 'startup',
            'status' => 'active',
        ]);

        $assistant = AssistantConfig::create([
            'workspace_id' => $workspace->id,
            'name' => 'German Import Assistant',
            'vapi_assistant_id' => 'asst_import_456',
        ]);

        WorkspacePhoneNumber::create([
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'provisioning_mode' => 'vapi_instant',
            'e164' => '+14155550123',
            'vapi_phone_number_id' => 'pn_old_instant_123',
            'is_active' => true,
        ]);

        $client = $this->createMock(VapiClient::class);
        $client->expects($this->once())
            ->method('deletePhoneNumber')
            ->with('pn_old_instant_123');
        $client->expects($this->once())
            ->method('createPhoneNumber')
            ->with($this->callback(function (array $payload) use ($assistant) {
                return $payload['provider'] === 'byo-phone-number'
                    && $payload['credentialId'] === 'cred_saved_123'
                    && $payload['assistantId'] === $assistant->vapi_assistant_id
                    && $payload['number'] === '+49301234567';
            }))
            ->willReturn([
                'id' => 'pn_import_789',
                'number' => '+49301234567',
            ]);

        $this->app->instance(VapiClient::class, $client);

        $response = $this
            ->actingAs($user)
            ->post(route('app.phone_numbers.store', $workspace), [
                'assistant_id' => $assistant->id,
                'provisioning_mode' => 'external_provider',
                'external_provider' => 'telnyx',
                'existing_number_country' => 'de',
                'forwarding_number' => '+49 30 1234567',
            ]);

        $response
            ->assertRedirect(route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $assistant->id,
            ], false))
            ->assertSessionHas('success', 'Existing number imported and linked to the assistant.');

        $this->assertDatabaseHas('workspace_phone_numbers', [
            'workspace_id' => $workspace->id,
            'assistant_id' => $assistant->id,
            'provisioning_mode' => 'external_provider',
            'external_provider' => 'telnyx',
            'forwarding_number' => '+49 30 1234567',
            'vapi_credential_id' => 'cred_saved_123',
            'vapi_phone_number_id' => 'pn_import_789',
            'e164' => '+49301234567',
            'is_active' => true,
        ]);
    }
}
