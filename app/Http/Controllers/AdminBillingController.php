<?php

namespace App\Http\Controllers;

use App\Models\CreditLedger;
use App\Models\Subscription;
use App\Models\Workspace;
use Illuminate\Http\Request;

class AdminBillingController extends Controller
{
    /**
     * List all workspaces with billing info.
     */
    public function index()
    {
        $workspaces = Workspace::withCount('cases')
            ->with(['subscription', 'memberships.user'])
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.billing.index', compact('workspaces'));
    }

    /**
     * Detailed billing view for a single workspace.
     */
    public function show(Workspace $workspace)
    {
        $workspace->load(['subscription', 'memberships.user']);

        $plan = $workspace->activePlan();
        $credits = CreditLedger::where('workspace_id', $workspace->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        $usageMinutes = \App\Models\UsageEvent::where('workspace_id', $workspace->id)
            ->where('occurred_at', '>=', now()->startOfMonth())
            ->sum('minutes');

        return view('admin.billing.workspace', compact('workspace', 'plan', 'credits', 'usageMinutes'));
    }

    /**
     * Grant credits to a workspace.
     */
    public function grantCredits(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'amount' => 'required|integer|min:1|max:100000',
            'reason' => 'nullable|string|max:255',
        ]);

        CreditLedger::create([
            'workspace_id' => $workspace->id,
            'type' => 'admin_grant',
            'amount' => $data['amount'],
            'meta' => [
                'reason' => $data['reason'] ?? 'Admin grant',
                'admin_id' => auth()->id(),
            ],
        ]);

        $workspace->increment('credits_balance', $data['amount']);

        return back()->with('success', "Granted {$data['amount']} credits to {$workspace->name}.");
    }

    /**
     * Override workspace plan (admin bypass — no Stripe required).
     */
    public function changePlan(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'plan_key' => 'required|in:' . implode(',', array_keys(config('plans'))),
        ]);

        $workspace->update(['plan_key' => $data['plan_key']]);

        // If upgrading to a paid plan, create or update a local subscription record
        if ($data['plan_key'] !== 'free') {
            Subscription::updateOrCreate(
                ['workspace_id' => $workspace->id, 'stripe_subscription_id' => 'admin_override_' . $workspace->id],
                [
                    'plan_key' => $data['plan_key'],
                    'status' => 'active',
                    'current_period_start' => now(),
                    'current_period_end' => now()->addYear(),
                    'cancel_at_period_end' => false,
                ]
            );
        }

        return back()->with('success', "Plan changed to {$data['plan_key']} for {$workspace->name}.");
    }
}
