<?php

namespace App\Services;

use App\Models\BillingCustomer;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $secret = config('services.stripe.secret') ?: 'sk_test_dummy';
        $this->stripe = new StripeClient($secret);
    }

    /**
     * Retrieve or create a Stripe Customer for the workspace.
     */
    public function getOrCreateCustomer(Workspace $workspace): string
    {
        $billing = BillingCustomer::firstOrCreate(
            ['workspace_id' => $workspace->id],
            ['stripe_customer_id' => ''] // placeholder, updated below
        );

        if (!$billing->stripe_customer_id) {
            $owner = $workspace->owner ?? $workspace->memberships()->first()?->user;
            $customer = $this->stripe->customers->create([
                'email' => $owner?->email ?? 'unknown@example.com',
                'name' => $workspace->name,
                'metadata' => ['workspace_id' => $workspace->id],
            ]);

            $billing->update(['stripe_customer_id' => $customer->id]);
            Log::info('StripeService: customer created', ['customer_id' => $customer->id, 'workspace_id' => $workspace->id]);
        }

        return $billing->stripe_customer_id;
    }

    /**
     * Create a Stripe Checkout Session for subscription.
     */
    public function createCheckoutSession(Workspace $workspace, string $priceId, string $successUrl, string $cancelUrl): string
    {
        $customerId = $this->getOrCreateCustomer($workspace);

        $session = $this->stripe->checkout->sessions->create([
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'mode' => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
            'metadata' => ['workspace_id' => $workspace->id],
            'allow_promotion_codes' => true,
        ]);

        return $session->url;
    }

    /**
     * Create a Stripe Customer Portal session.
     */
    public function createPortalSession(Workspace $workspace, string $returnUrl): string
    {
        $customerId = $this->getOrCreateCustomer($workspace);

        $session = $this->stripe->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);

        return $session->url;
    }

    /**
     * Sync a Stripe subscription event to DB.
     */
    public function syncSubscription(array $stripeSub): void
    {
        $workspaceId = $stripeSub['metadata']['workspace_id'] ?? null;
        if (!$workspaceId)
            return;

        $planKey = $stripeSub['items']['data'][0]['price']['metadata']['plan_key'] ?? 'unknown';

        Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSub['id']],
            [
                'workspace_id' => $workspaceId,
                'stripe_price_id' => $stripeSub['items']['data'][0]['price']['id'] ?? null,
                'plan_key' => $planKey,
                'status' => $stripeSub['status'],
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSub['current_period_start']),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSub['current_period_end']),
                'cancel_at_period_end' => $stripeSub['cancel_at_period_end'],
                'canceled_at' => $stripeSub['canceled_at'] ? \Carbon\Carbon::createFromTimestamp($stripeSub['canceled_at']) : null,
            ]
        );

        // Keep workspace.plan_key in sync
        $workspace = Workspace::find($workspaceId);
        if ($workspace) {
            if (in_array($stripeSub['status'], ['active', 'trialing'])) {
                $workspace->update(['plan_key' => $planKey]);
            } elseif (in_array($stripeSub['status'], ['canceled', 'unpaid', 'past_due'])) {
                $workspace->update(['plan_key' => 'free']);
            }
        }

        Log::info('StripeService: subscription synced', [
            'stripe_subscription_id' => $stripeSub['id'],
            'status' => $stripeSub['status'],
            'plan_key' => $planKey,
        ]);
    }

    /**
     * Sync a Stripe invoice event to DB.
     */
    public function syncInvoice(array $stripeInvoice): void
    {
        $workspaceId = $stripeInvoice['subscription_details']['metadata']['workspace_id']
            ?? $stripeInvoice['metadata']['workspace_id']
            ?? null;

        if (!$workspaceId) {
            // Fallback: look up by customer
            $billing = BillingCustomer::where('stripe_customer_id', $stripeInvoice['customer'])->first();
            $workspaceId = $billing?->workspace_id;
        }

        if (!$workspaceId)
            return;

        Invoice::updateOrCreate(
            ['stripe_invoice_id' => $stripeInvoice['id']],
            [
                'workspace_id' => $workspaceId,
                'amount_due' => $stripeInvoice['amount_due'],
                'amount_paid' => $stripeInvoice['amount_paid'],
                'currency' => $stripeInvoice['currency'],
                'status' => $stripeInvoice['status'],
                'hosted_invoice_url' => $stripeInvoice['hosted_invoice_url'] ?? null,
                'invoice_pdf' => $stripeInvoice['invoice_pdf'] ?? null,
                'period_start' => $stripeInvoice['period_start'] ? \Carbon\Carbon::createFromTimestamp($stripeInvoice['period_start']) : null,
                'period_end' => $stripeInvoice['period_end'] ? \Carbon\Carbon::createFromTimestamp($stripeInvoice['period_end']) : null,
            ]
        );
    }
}
