<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Http\Request;

class InternalVapiController extends Controller
{
    public function resyncAssistants(Request $request, VapiProvisioningService $provisioner)
    {
        if (!$this->authorized($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $configs = AssistantConfig::with('workspace')
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNotNull('vapi_assistant_id')
                    ->orWhereNotNull('vapi_tool_id')
                    ->orWhereNotNull('vapi_booking_tool_id');
            })
            ->get();

        $synced = [];
        $skipped = [];

        foreach ($configs as $config) {
            $workspace = $config->workspace;

            if (!$workspace || !$workspace->integration_token) {
                $skipped[] = [
                    'assistant_id' => $config->id,
                    'name' => $config->name,
                    'reason' => 'Missing workspace or integration token',
                ];
                continue;
            }

            $updated = $provisioner->provisionAssistantAndToolForConfig($config, $workspace, [
                'name' => $config->name,
                'system_prompt' => $config->system_prompt,
                'voice_provider' => $config->voice_provider,
                'voice_id' => $config->voice_id,
                'preset_key' => $config->preset_key,
                'override_params' => $config->override_params,
                'intake_params' => $config->intake_params,
                'fallback_phone' => $config->fallback_phone,
                'is_active' => $config->is_active,
            ]);

            $synced[] = [
                'assistant_id' => $updated->id,
                'workspace_slug' => $workspace->slug,
                'name' => $updated->name,
                'vapi_assistant_id' => $updated->vapi_assistant_id,
                'vapi_tool_id' => $updated->vapi_tool_id,
                'vapi_booking_tool_id' => $updated->vapi_booking_tool_id,
            ];
        }

        return response()->json([
            'ok' => true,
            'syncedCount' => count($synced),
            'skippedCount' => count($skipped),
            'synced' => $synced,
            'skipped' => $skipped,
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('services.server_api_token');
        if ($expected === '') {
            return false;
        }

        $provided = trim((string) $request->header('Authorization', ''));
        if (str_starts_with(strtolower($provided), 'bearer ')) {
            $provided = trim(substr($provided, 7));
        }

        return $provided !== '' && hash_equals($expected, $provided);
    }
}
