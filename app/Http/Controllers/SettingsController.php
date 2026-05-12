<?php

namespace App\Http\Controllers;

use App\Models\CalendarConnection;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function __construct(protected StripeService $stripe)
    {
    }

    /**
     * Unified settings page - tab is driven by ?tab= query param.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $workspace = $user->currentWorkspace();
        $tab = $request->query('tab', 'profile');

        $data = compact('user', 'workspace', 'tab');

        // Tab-specific data
        if ($tab === 'integrations' && $workspace) {
            abort_unless($user->canManageIntegrations($workspace), 403, 'Only workspace admins can manage integrations.');
            $data['integrationToken'] = $workspace->integration_token;
            $data['vapiWebhookUrl'] = config('services.vapi.webhook_url');
        }

        if ($tab === 'calendar' && $workspace) {
            $data['connections'] = CalendarConnection::where('workspace_id', $workspace->id)
                ->get()
                ->keyBy('provider');
        }

        if ($tab === 'payment' && $workspace) {
            abort_unless($user->canManageBilling($workspace), 403, 'Only workspace admins can manage billing.');
            $data['paymentMethods'] = $this->getPaymentMethods($workspace);
            $data['subscription'] = $workspace->subscription;
        }

        return view('settings.index', $data);
    }

    /**
     * Update profile information.
     */
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    /**
     * Update workspace preferences (timezone, case label, etc.).
     */
    public function updateWorkspace(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404);
        abort_unless($request->user()->canManageWorkspace($workspace), 403, 'Only workspace admins can change workspace settings.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'default_timezone' => 'required|string|max:80',
            'case_label' => 'required|string|max:40',
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        unset($data['logo'], $data['remove_logo']);

        if ($request->hasFile('logo')) {
            $removeLogo = true;
            $data['logo_path'] = $request->file('logo')->store('workspace-logos', 'public');
        } elseif ($removeLogo) {
            $data['logo_path'] = null;
        }

        $previousLogoPath = $workspace->logo_path;

        $workspace->update($data);

        if ($removeLogo && filled($previousLogoPath) && $previousLogoPath !== $workspace->logo_path) {
            Storage::disk('public')->delete($previousLogoPath);
        }

        return back()->with('success', 'Workspace settings updated.');
    }

    /**
     * Delete account.
     */
    public function destroyAccount(Request $request)
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        auth()->guard()->logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Redirect to Stripe setup for adding/managing payment methods.
     */
    public function setupPaymentMethod(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_unless($workspace, 404);
        abort_unless($request->user()->canManageBilling($workspace), 403, 'Only workspace admins can manage billing.');

        $url = $this->stripe->createPortalSession(
            $workspace,
            route('app.settings', ['tab' => 'payment'])
        );

        return redirect($url);
    }

    /**
     * Get payment methods from Stripe for this workspace.
     */
    protected function getPaymentMethods($workspace): array
    {
        try {
            $billing = \App\Models\BillingCustomer::where('workspace_id', $workspace->id)->first();
            if (!$billing || !$billing->stripe_customer_id) {
                return [];
            }

            $stripe = new \Stripe\StripeClient(config('services.stripe.secret') ?: 'sk_test_dummy');
            $methods = $stripe->paymentMethods->all([
                'customer' => $billing->stripe_customer_id,
                'type' => 'card',
            ]);

            return collect($methods->data)->map(function ($pm) {
                return [
                    'id' => $pm->id,
                    'brand' => ucfirst($pm->card->brand),
                    'last4' => $pm->card->last4,
                    'exp_month' => str_pad($pm->card->exp_month, 2, '0', STR_PAD_LEFT),
                    'exp_year' => $pm->card->exp_year,
                ];
            })->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to fetch payment methods', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
