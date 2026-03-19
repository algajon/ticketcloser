<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\UsageEvent;
use App\Models\CreditLedger;
use App\Models\Workspace;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(protected StripeService $stripe)
    {
    }

    /**
     * Billing overview page.
     */
    public function index(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404, 'No active workspace.');

        $subscription = Subscription::where('workspace_id', $workspace->id)
            ->latest()
            ->first();

        $invoices = Invoice::where('workspace_id', $workspace->id)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        // Usage this billing period
        $periodStart = $subscription?->current_period_start ?? now()->startOfMonth();
        $usageMinutes = UsageEvent::where('workspace_id', $workspace->id)
            ->where('occurred_at', '>=', $periodStart)
            ->sum('minutes');

        $usageCalls = UsageEvent::where('workspace_id', $workspace->id)
            ->where('event_type', 'call')
            ->where('occurred_at', '>=', $periodStart)
            ->count();

        $plan = $workspace->activePlan();
        $creditsBalance = $workspace->credits_balance;

        return view('billing.index', compact(
            'workspace',
            'subscription',
            'invoices',
            'usageMinutes',
            'usageCalls',
            'plan',
            'creditsBalance'
        ));
    }

    /**
     * Show the plan picker page.
     */
    public function plans(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404, 'No active workspace.');

        $plans = config('plans');
        $currentPlan = $workspace->plan_key ?? 'free';

        return view('billing.plans', compact('workspace', 'plans', 'currentPlan'));
    }

    /**
     * Handle plan selection — either start Stripe Checkout or activate free trial.
     */
    public function selectPlan(Request $request)
    {
        $request->validate(['plan' => 'required|in:' . implode(',', array_keys(config('plans')))]);
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404);

        $planKey = $request->plan;

        // Free plan — just activate it
        if ($planKey === 'free') {
            $workspace->update(['plan_key' => 'free']);
            return redirect()->route('app.dashboard')->with('success', 'Free trial activated!');
        }

        // Paid plan — redirect to Stripe Checkout
        $priceMap = [
            'startup' => config('services.stripe.prices.startup'),
            'pro' => config('services.stripe.prices.pro'),
            'enterprise' => config('services.stripe.prices.enterprise'),
        ];

        $priceId = $priceMap[$planKey] ?? null;
        if (!$priceId) {
            return back()->with('error', 'This plan is not configured yet. Please contact sales.');
        }

        $url = $this->stripe->createCheckoutSession(
            $workspace,
            $priceId,
            route('app.billing.index'),
            route('app.billing.plans')
        );

        return redirect($url);
    }

    /**
     * Redirect to Stripe Customer Portal.
     */
    public function portal(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404);

        $url = $this->stripe->createPortalSession(
            $workspace,
            route('app.billing.index')
        );

        return redirect($url);
    }

    /**
     * Initiate Stripe Checkout (for a given price key sent from the UI).
     */
    public function checkout(Request $request)
    {
        $request->validate(['plan' => 'required|in:startup,pro,enterprise']);
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404);

        // Map plan keys to Stripe Price IDs (set in env)
        $priceMap = [
            'startup' => config('services.stripe.prices.startup'),
            'pro' => config('services.stripe.prices.pro'),
            'enterprise' => config('services.stripe.prices.enterprise'),
        ];

        $priceId = $priceMap[$request->plan] ?? null;
        if (!$priceId) {
            return back()->with('error', 'This plan is not configured yet. Please contact sales.');
        }

        $url = $this->stripe->createCheckoutSession(
            $workspace,
            $priceId,
            route('app.billing.index'),
            route('app.billing.index')
        );

        return redirect($url);
    }

    /**
     * Stripe webhook endpoint.
     */
    public function webhook(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sig = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Exception $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $data = $event->data->object->toArray();

        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->stripe->syncSubscription($data),

            'invoice.paid',
            'invoice.payment_failed',
            'invoice.finalized' => $this->stripe->syncInvoice($data),

            default => null,
        };

        Log::info('Stripe webhook handled', ['type' => $event->type]);

        return response()->json(['ok' => true]);
    }
}
