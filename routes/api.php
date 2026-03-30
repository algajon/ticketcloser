<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\InternalVapiController;
use App\Http\Controllers\VapiWebhookController;

Route::post('/webhooks/vapi', [VapiWebhookController::class, 'handle']);
Route::get('/health', fn() => response()->json(['ok' => true]));
Route::post('/internal/vapi/resync', [InternalVapiController::class, 'resyncAssistants']);

Route::middleware(['resolve.workspace', 'verify.workspace.token'])->group(function () {
    Route::post('/cases', [CaseController::class, 'store']);
    Route::get('/cases', [CaseController::class, 'index']);
    Route::get('/cases/{id}', [CaseController::class, 'show']);
});
