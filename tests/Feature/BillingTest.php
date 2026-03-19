<?php

namespace Tests\Feature;

use App\Models\BillingCustomer;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        // Link user via pivot
        \DB::table('workspace_memberships')->insert([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Set session so currentWorkspace() resolves
        session(['current_workspace_id' => $this->workspace->id]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function billing_page_is_accessible_when_authenticated(): void
    {
        $response = $this->get(route('app.billing.index'));
        $response->assertStatus(200);
        $response->assertViewIs('billing.index');
    }

    /** @test */
    public function subscription_model_is_active_for_active_status(): void
    {
        $sub = new Subscription(['workspace_id' => $this->workspace->id, 'stripe_subscription_id' => 'sub_1', 'plan_key' => 'pro', 'status' => 'active']);
        $this->assertTrue($sub->isActive());
    }

    /** @test */
    public function subscription_model_is_not_active_for_canceled_status(): void
    {
        $sub = new Subscription(['workspace_id' => $this->workspace->id, 'stripe_subscription_id' => 'sub_1', 'plan_key' => 'pro', 'status' => 'canceled']);
        $this->assertFalse($sub->isActive());
    }

    /** @test */
    public function subscription_plan_label_returns_correct_labels(): void
    {
        $sub = new Subscription(['plan_key' => 'pro', 'status' => 'active', 'stripe_subscription_id' => 'sub_x', 'workspace_id' => 1]);
        $this->assertEquals('Pro', $sub->planLabel());
    }
}
