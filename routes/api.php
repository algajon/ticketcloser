<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\VapiWebhookController;
use Illuminate\Support\Facades\Artisan;

Route::post('/webhooks/vapi', [VapiWebhookController::class, 'handle']);
Route::get('/health', fn() => response()->json(['ok' => true]));

// Protected migration runner — for use when shell is unavailable (e.g. Render free plan)
// Secured by SERVER_API_TOKEN. Hit: POST /api/run-migrations with Authorization: Bearer <SERVER_API_TOKEN>
Route::post('/run-migrations', function () {
    $expected = config('services.server_api_token', '');
    $auth = request()->header('Authorization', '');
    $token = str_starts_with(strtolower($auth), 'bearer ') ? trim(substr($auth, 7)) : trim($auth);

    if ($expected === '' || !hash_equals($expected, $token)) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    try {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();
        return response()->json(['success' => true, 'output' => $output]);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::middleware(['resolve.workspace', 'verify.workspace.token'])->group(function () {
    Route::post('/cases', [CaseController::class, 'store']);
    Route::get('/cases', [CaseController::class, 'index']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
});
