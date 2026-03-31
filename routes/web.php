<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\SupportCaseController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\VoiceAssistantController;
use App\Http\Controllers\TicketUiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\AdminBillingController;
use App\Http\Controllers\PromptWriterController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AdminPresetController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // If logged in, send to the auth/workspace gate
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('index');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        if (! auth()->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->route('app.dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| App (authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'workspace.ready', 'subscribed'])->prefix('app')->name('app.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Tickets (simple inbox)
    |--------------------------------------------------------------------------
    */
    Route::get('/tickets', [TicketUiController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{case}', [TicketUiController::class, 'show'])->name('tickets.show');

    /*
    |--------------------------------------------------------------------------
    | Onboarding
    |--------------------------------------------------------------------------
    | Flow: Company -> Dashboard (setup tasks shown on dashboard)
    */
    Route::get('/onboarding/company', [OnboardingController::class, 'company'])->name('onboarding.company');
    Route::post('/onboarding/company', [OnboardingController::class, 'saveCompany'])->name('onboarding.company.save');


    /*
    |--------------------------------------------------------------------------
    | Workspaces
    |--------------------------------------------------------------------------
    */
    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::get('/workspaces/new', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');

    // Switch current workspace (store slug in session, etc.)
    Route::post('/workspaces/{workspace:slug}/switch', [WorkspaceController::class, 'switch'])
        ->name('workspaces.switch');

    // Workspace settings page
    Route::get('/workspaces/{workspace:slug}/settings', [WorkspaceController::class, 'settings'])
        ->name('workspaces.settings');
    Route::post('/workspaces/{workspace:slug}/settings', [WorkspaceController::class, 'updateSettings'])
        ->name('workspaces.settings.update');

    /*
    |--------------------------------------------------------------------------
    | Cases (Support Tickets)
    |--------------------------------------------------------------------------
    */
    Route::get('/workspaces/{workspace:slug}/cases/new', [SupportCaseController::class, 'create'])
        ->name('cases.create');
    Route::get('/workspaces/{workspace:slug}/cases', [SupportCaseController::class, 'index'])
        ->name('cases.index');
    Route::get('/workspaces/{workspace:slug}/cases/{case}', [SupportCaseController::class, 'show'])
        ->name('cases.show');
    Route::post('/workspaces/{workspace:slug}/cases', [SupportCaseController::class, 'store'])
        ->name('cases.store');

    Route::post('/workspaces/{workspace:slug}/cases/{case}/status', [SupportCaseController::class, 'updateStatus'])
        ->name('cases.status.update');

    /*
    |--------------------------------------------------------------------------
    | Integrations (Tokens, Webhooks, Vapi settings)
    |--------------------------------------------------------------------------
    */
    Route::get('/workspaces/{workspace:slug}/integrations', [IntegrationController::class, 'index'])
        ->name('integrations.index');

    // Regenerate integration token (tc_...)
    Route::post('/workspaces/{workspace:slug}/integrations/token/regenerate', [IntegrationController::class, 'regenerateToken'])
        ->name('integrations.token.regenerate');

    /*
    |--------------------------------------------------------------------------
    | Voice Assistant Setup (prompt, voice, Vapi phone number mapping)
    |--------------------------------------------------------------------------
    */
    // Assistant CRUD (multiple assistants per workspace)
    Route::get('/workspaces/{workspace:slug}/assistants', [VoiceAssistantController::class, 'edit'])
        ->name('assistant.edit');
    Route::get('/workspaces/{workspace:slug}/assistants/create', [VoiceAssistantController::class, 'create'])
        ->name('assistant.create');
    Route::post('/workspaces/{workspace:slug}/assistants', [VoiceAssistantController::class, 'update'])
        ->name('assistant.store');
    Route::get('/workspaces/{workspace:slug}/assistants/{assistant}', [VoiceAssistantController::class, 'show'])
        ->name('assistant.show');
    Route::post('/workspaces/{workspace:slug}/assistants/{assistant}', [VoiceAssistantController::class, 'update'])
        ->name('assistant.update');
    Route::delete('/workspaces/{workspace:slug}/assistants/{assistant}', [VoiceAssistantController::class, 'destroy'])
        ->name('assistant.destroy');

    // Optional: Phone numbers management UI for a workspace
    Route::get('/workspaces/{workspace:slug}/phone-numbers', [VoiceAssistantController::class, 'phoneNumbers'])
        ->name('phone_numbers.index');
    Route::post('/workspaces/{workspace:slug}/phone-numbers', [VoiceAssistantController::class, 'storePhoneNumber'])
        ->name('phone_numbers.store');
    Route::delete('/workspaces/{workspace:slug}/phone-numbers/{phoneNumber}', [VoiceAssistantController::class, 'destroyPhoneNumber'])
        ->name('phone_numbers.destroy');

    // Queues, Contacts, Call logs
    Route::get('/workspaces/{workspace:slug}/queues', [\App\Http\Controllers\QueuesController::class, 'index'])->name('queues.index');
    Route::get('/workspaces/{workspace:slug}/contacts', [\App\Http\Controllers\ContactsController::class, 'index'])->name('contacts.index');
    Route::get('/workspaces/{workspace:slug}/contacts/{contact}/edit', [\App\Http\Controllers\ContactsController::class, 'edit'])->name('contacts.edit');
    Route::patch('/workspaces/{workspace:slug}/contacts/{contact}', [\App\Http\Controllers\ContactsController::class, 'update'])->name('contacts.update');
    Route::delete('/workspaces/{workspace:slug}/contacts/{contact}', [\App\Http\Controllers\ContactsController::class, 'destroy'])->name('contacts.destroy');
    Route::get('/workspaces/{workspace:slug}/calls', [\App\Http\Controllers\CallEventsController::class, 'index'])->name('calls.index');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | Settings (unified)
    |--------------------------------------------------------------------------
    */
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::patch('/settings/workspace', [SettingsController::class, 'updateWorkspace'])->name('settings.workspace');
    Route::delete('/settings/account', [SettingsController::class, 'destroyAccount'])->name('settings.destroy');
    Route::post('/settings/payment', [SettingsController::class, 'setupPaymentMethod'])->name('settings.payment');

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    */
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/plans', [BillingController::class, 'plans'])->name('billing.plans');
    Route::post('/billing/plans', [BillingController::class, 'selectPlan'])->name('billing.selectPlan');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');

    /*
    |--------------------------------------------------------------------------
    | AI Prompt Writer
    |--------------------------------------------------------------------------
    */
    Route::get('/prompt-writer', [PromptWriterController::class, 'index'])->name('prompt-writer.index');
    Route::post('/prompt-writer/generate', [PromptWriterController::class, 'generate'])->name('prompt-writer.generate');
    Route::patch('/prompt-writer/versions/{version}/name', [PromptWriterController::class, 'saveName'])->name('prompt-writer.version.name');
    Route::delete('/prompt-writer/versions/{version}', [PromptWriterController::class, 'destroy'])->name('prompt-writer.version.destroy');

    /*
    |--------------------------------------------------------------------------
    | Calendar Booking
    |--------------------------------------------------------------------------
    */
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/settings', [CalendarController::class, 'settings'])->name('calendar.settings');

    // Google Calendar OAuth
    Route::get('/calendar/google/auth', [CalendarController::class, 'googleAuth'])->name('calendar.google.auth');
    Route::get('/calendar/google/callback', [CalendarController::class, 'googleCallback'])->name('calendar.google.callback');

    // Calendly
    Route::post('/calendar/calendly/save', [CalendarController::class, 'saveCalendlyLink'])->name('calendar.calendly.save');

    // Suggested event actions
    Route::post('/calendar/suggested/{suggestedEvent}/confirm', [CalendarController::class, 'confirm'])->name('calendar.confirm');
    Route::post('/calendar/suggested/{suggestedEvent}/dismiss', [CalendarController::class, 'dismiss'])->name('calendar.dismiss');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'is_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/presets', [AdminPresetController::class, 'index'])->name('presets.index');
    Route::get('/presets/{preset}/edit', [AdminPresetController::class, 'edit'])->name('presets.edit');
    Route::put('/presets/{preset}', [AdminPresetController::class, 'update'])->name('presets.update');

    // Billing management
    Route::get('/billing', [AdminBillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/{workspace}', [AdminBillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/{workspace}/credits', [AdminBillingController::class, 'grantCredits'])->name('billing.grantCredits');
    Route::post('/billing/{workspace}/plan', [AdminBillingController::class, 'changePlan'])->name('billing.changePlan');
});

/*
|--------------------------------------------------------------------------
| Stripe Webhook (no auth)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/stripe', [BillingController::class, 'webhook'])
    ->name('webhooks.stripe');

if (file_exists(__DIR__ . '/auth.php')) {
    require __DIR__ . '/auth.php';
}
