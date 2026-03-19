<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\AssistantPreset;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use App\Services\Vapi\VapiProvisioningService;
use App\Services\Vapi\VapiClient;
use Illuminate\Http\Request;

class VoiceAssistantController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    // ─── Assistant ────────────────────────────────────────────────────────────

    public function edit(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $configs = AssistantConfig::where('workspace_id', $workspace->id)->get();
        // Use hardcoded voices for consistent picker experience
        $voices = self::vapiVoices();

        return view('assistants.index', compact('workspace', 'configs', 'voices'));
    }

    public function update(Request $request, Workspace $workspace, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        $data = $request->validate([
            'assistant_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'system_prompt' => ['nullable', 'string'],
            'voice_provider' => ['nullable', 'string', 'max:50'],
            'voice_id' => ['nullable', 'string', 'max:120'],
            'intake_params' => ['nullable', 'array'],
            'preset_key' => ['nullable', 'string', 'exists:assistant_presets,key'],
            'override_params' => ['nullable', 'array'],
        ]);

        try {
            if (!empty($data['assistant_id'])) {
                $config = AssistantConfig::where('workspace_id', $workspace->id)->where('id', $data['assistant_id'])->firstOrFail();
            } else {
                $config = AssistantConfig::create([
                    'workspace_id' => $workspace->id,
                    'name' => $data['name'],
                ]);
            }

            $provisioner->provisionAssistantAndToolForConfig($config, $workspace, $data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $raw = $e->response?->json('message') ?? $e->getMessage();
            $apiError = is_array($raw) ? implode(' ', $raw) : (string) $raw;
            return back()
                ->withInput()
                ->with('error', 'Vapi API error: ' . $apiError);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('app.assistant.edit', $workspace)
            ->with('success', 'Assistant + tool synced to Vapi.');
    }

    // ─── Phone Numbers ────────────────────────────────────────────────────────

    public function phoneNumbers(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $configs = AssistantConfig::where('workspace_id', $workspace->id)->get();

        $assistantId = $request->query('assistant_id');
        $config = $assistantId
            ? $configs->where('id', $assistantId)->first()
            : $configs->first();

        $phone = $config
            ? WorkspacePhoneNumber::where('workspace_id', $workspace->id)
                ->where('assistant_id', $config->id)
                ->first()
            : null;

        // If no assistant-specific number exists, we can still show the page for provisioning
        if (!$phone) {
            $phone = new WorkspacePhoneNumber(['workspace_id' => $workspace->id]);
        }

        return view('onboarding.phone', compact('workspace', 'phone', 'configs', 'config'));
    }

    public function storePhoneNumber(Request $request, Workspace $workspace, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        $data = $request->validate([
            'area_code' => ['nullable', 'string', 'max:10'],
            'assistant_id' => ['nullable', 'integer'],
        ]);

        try {
            $record = $provisioner->provisionPhoneNumber($workspace, $data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $body = $e->response?->json();
            $raw = $body['message'] ?? ($body['error'] ?? $e->getMessage());
            $apiError = is_array($raw) ? implode(' ', $raw) : (string) $raw;
            return back()->withInput()->with('error', 'Vapi error: ' . $apiError);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Provisioning failed: ' . $e->getMessage());
        }

        return redirect()
            ->route('app.phone_numbers.index', $workspace)
            ->with('success', 'Phone number provisioned/synced in Vapi.');
    }

    public function destroyPhoneNumber(Request $request, Workspace $workspace, WorkspacePhoneNumber $phoneNumber)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($phoneNumber->workspace_id !== $workspace->id, 404);
        $phoneNumber->delete();
        return back()->with('success', 'Phone number removed.');
    }



    // Create assistant form
    public function create(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        $voices = self::vapiVoices();
        $presets = AssistantPreset::all();
        return view('assistants.form', compact('workspace', 'voices', 'presets'));
    }

    // Show a single assistant for editing
    public function show(Request $request, Workspace $workspace, AssistantConfig $assistant)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($assistant->workspace_id !== $workspace->id, 404);

        $config = $assistant;
        $voices = self::vapiVoices();
        $presets = AssistantPreset::all();
        return view('assistants.form', compact('workspace', 'config', 'voices', 'presets'));
    }

    /**
     * Hardcoded Vapi voice catalogue (Vapi has no list-voices API).
     */
    private static function vapiVoices(): array
    {
        return [
            ['voiceId' => 'Clara', 'name' => 'Clara', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Godfrey', 'name' => 'Godfrey', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Elliot', 'name' => 'Elliot', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Kylie', 'name' => 'Kylie', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Rohan', 'name' => 'Rohan', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Lily', 'name' => 'Lily', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Savannah', 'name' => 'Savannah', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Hana', 'name' => 'Hana', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Cole', 'name' => 'Cole', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Harry', 'name' => 'Harry', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Paige', 'name' => 'Paige', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Spencer', 'name' => 'Spencer', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Nico', 'name' => 'Nico', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Kai', 'name' => 'Kai', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Emma', 'name' => 'Emma', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Sagar', 'name' => 'Sagar', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Neil', 'name' => 'Neil', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Leah', 'name' => 'Leah', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Tara', 'name' => 'Tara', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Jess', 'name' => 'Jess', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Leo', 'name' => 'Leo', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Dan', 'name' => 'Dan', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Mia', 'name' => 'Mia', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Zac', 'name' => 'Zac', 'provider' => 'vapi', 'language' => 'en'],
            ['voiceId' => 'Zoe', 'name' => 'Zoe', 'provider' => 'vapi', 'language' => 'en'],
        ];
    }

    // Delete an assistant
    public function destroy(Request $request, Workspace $workspace, AssistantConfig $assistant)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);
        abort_if($assistant->workspace_id !== $workspace->id, 404);
        $assistant->delete();
        return redirect()->route('app.assistant.edit', $workspace)->with('success', 'Assistant deleted.');
    }
}