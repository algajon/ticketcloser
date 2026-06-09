<?php

namespace App\Http\Controllers;

use App\Models\AssistantConfig;
use App\Models\AssistantPreset;
use App\Models\Workspace;
use App\Models\WorkspacePhoneNumber;
use App\Support\RegionalPilotStackCatalog;
use App\Support\WorkspaceUseCaseCatalog;
use App\Services\Vapi\VapiProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class VoiceAssistantController extends Controller
{
    use Concerns\AuthorizesWorkspace;

    private const PHONE_ACTIVATION_COUNTDOWN_SECONDS = 180;

    // ─── Assistant ────────────────────────────────────────────────────────────

    public function edit(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceAccess($request, $workspace);

        $configs = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->latest('updated_at')
            ->get();

        $phonesByAssistant = WorkspacePhoneNumber::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('assistant_id')
            ->latest('updated_at')
            ->get(['assistant_id', 'e164'])
            ->keyBy('assistant_id');

        // Use hardcoded voices for consistent picker experience
        $voices = self::vapiVoices();

        return view('assistants.index', compact('workspace', 'configs', 'voices', 'phonesByAssistant'));
    }

    public function update(Request $request, Workspace $workspace, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage assistants.');
        $availablePresetKeys = AssistantPreset::ensureDefaults()->pluck('key')->all();

        $data = $request->validate([
            'assistant_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:120'],
            'first_message' => ['nullable', 'string', 'max:500'],
            'system_prompt' => ['nullable', 'string'],
            'voice_provider' => ['nullable', 'string', 'max:50'],
            'voice_id' => ['nullable', 'string', 'max:120'],
            'language_code' => ['nullable', 'string', Rule::in(collect(RegionalPilotStackCatalog::languageOptions($workspace->primaryMarket()))->pluck('value')->all())],
            'model_name' => ['nullable', 'string', Rule::in(collect(AssistantConfig::modelOptions())->pluck('value')->all())],
            'intake_params' => ['nullable', 'array'],
            'intake_params.operator' => ['nullable', 'array'],
            'intake_params.operator.enabled' => ['nullable', 'boolean'],
            'intake_params.operator.mode' => ['nullable', 'string', Rule::in(['spoken_handoff'])],
            'intake_params.operator.intro' => ['nullable', 'string', 'max:500'],
            'intake_params.operator.fallback_message' => ['nullable', 'string', 'max:500'],
            'intake_params.operator.routes' => ['nullable', 'array', 'max:8'],
            'intake_params.operator.routes.*.label' => ['nullable', 'string', 'max:80'],
            'intake_params.operator.routes.*.keywords' => ['nullable', 'string', 'max:180'],
            'intake_params.operator.routes.*.assistant_id' => ['nullable', 'integer'],
            'intake_params.operator.routes.*.language_code' => ['nullable', 'string', Rule::in(collect(RegionalPilotStackCatalog::languageOptions($workspace->primaryMarket()))->pluck('value')->all())],
            'preset_key' => ['nullable', 'string', Rule::in($availablePresetKeys)],
            'override_params' => ['nullable', 'array'],
            'fallback_phone' => ['nullable', 'string', 'max:20'],
        ]);

        $isCreating = empty($data['assistant_id']);
        $wasClampedForFreePlan = false;

        if ($isCreating) {
            if ($workspace->isFreePlan() && $workspace->hasReachedVoiceMinuteLimit()) {
                return redirect()
                    ->route('app.assistant.edit', $workspace)
                    ->with('error', 'This free workspace has reached its 5 minute limit. Upgrade to add another assistant.');
            }

            $assistantLimit = $workspace->bypassesPlanLimits()
                ? -1
                : (int) ($workspace->activePlan()['max_assistants'] ?? -1);

            if ($assistantLimit !== -1 && AssistantConfig::query()->where('workspace_id', $workspace->id)->count() >= $assistantLimit) {
                return back()
                    ->withInput()
                    ->with('error', 'This plan has reached its assistant limit.');
            }
        }

        if ($workspace->isFreePlan() && ! $workspace->bypassesPlanLimits()) {
            $incomingModel = AssistantConfig::normalizedModelName($data['model_name'] ?? null);
            $incomingProvider = $data['voice_provider'] ?? null;
            $incomingLanguage = RegionalPilotStackCatalog::normalizeLanguageCode(
                $data['language_code'] ?? null,
                $workspace->preferredLanguageCode()
            ) ?: 'en-US';
            $recommendedVoice = RegionalPilotStackCatalog::standardVoiceProfile(
                $incomingLanguage,
                $data['preset_key'] ?? null,
                $workspace->primaryMarket()
            );
            $allowedProvider = $recommendedVoice['provider'] ?? 'vapi';

            if ($incomingModel !== AssistantConfig::DEFAULT_MODEL || ($incomingProvider && $incomingProvider !== $allowedProvider)) {
                $wasClampedForFreePlan = true;
            }

            $data['model_name'] = AssistantConfig::DEFAULT_MODEL;
            $data['voice_provider'] = $allowedProvider;
        }

        try {
            if (!empty($data['assistant_id'])) {
                $config = AssistantConfig::where('workspace_id', $workspace->id)->where('id', $data['assistant_id'])->firstOrFail();
            } else {
                $config = AssistantConfig::create([
                    'workspace_id' => $workspace->id,
                    'name' => $data['name'],
                ]);
            }

            $data['intake_params'] = $this->normalizeAssistantIntakeParams(
                $workspace,
                $data['intake_params'] ?? [],
                $config->intake_params ?? [],
                $config,
            );

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

        $redirect = redirect()
            ->route('app.assistant.edit', $workspace)
            ->with('success', 'Assistant + tool synced to Vapi.');

        if ($wasClampedForFreePlan) {
            $redirect->with('warning', 'Free workspaces use the Standard AI engine. Supported languages can still use the curated regional voice path.');
        }

        if ($isCreating) {
            $redirect->with('assistant_review_prompt', [
                'assistant_id' => $config->id,
                'assistant_name' => $config->name,
                'workspace_id' => $workspace->id,
            ]);
        }

        return $redirect;
    }

    // ─── Phone Numbers ────────────────────────────────────────────────────────

    public function phoneNumbers(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage phone numbers.');
        $phoneNumbersLockedForFreePlan = $workspace->isFreePlan() && ! $workspace->bypassesPlanLimits();

        $configs = AssistantConfig::where('workspace_id', $workspace->id)->get();

        $assistantId = $request->query('assistant_id');
        $config = $assistantId
            ? $configs->firstWhere('id', (int) $assistantId)
            : null;

        if (!$config) {
            $assistantWithPhoneId = WorkspacePhoneNumber::where('workspace_id', $workspace->id)
                ->whereNotNull('assistant_id')
                ->latest('updated_at')
                ->value('assistant_id');

            if ($assistantWithPhoneId) {
                $config = $configs->firstWhere('id', (int) $assistantWithPhoneId);
            }
        }

        if (!$config) {
            $config = $configs->first(function (AssistantConfig $assistantConfig) {
                return filled($assistantConfig->vapi_assistant_id);
            }) ?? $configs->first();
        }

        $phone = null;

        if ($config) {
            $phone = WorkspacePhoneNumber::where('workspace_id', $workspace->id)
                ->where('assistant_id', $config->id)
                ->latest('updated_at')
                ->first();
        }

        if (!$phone && !$assistantId) {
            $phone = WorkspacePhoneNumber::where('workspace_id', $workspace->id)
                ->whereNull('assistant_id')
                ->latest('updated_at')
                ->first();
        }

        // If no assistant-specific number exists, we can still show the page for provisioning
        if (!$phone) {
            $phone = new WorkspacePhoneNumber(['workspace_id' => $workspace->id]);
        }

        $activationCountdownEndsAt = null;
        $countdown = $request->session()->get('phone_activation_countdown');

        if (
            $config
            && is_array($countdown)
            && (int) ($countdown['assistant_id'] ?? 0) === (int) $config->id
            && filled($countdown['ends_at'] ?? null)
        ) {
            try {
                $candidate = Carbon::parse($countdown['ends_at']);

                if ($candidate->isFuture()) {
                    $activationCountdownEndsAt = $candidate;
                }
            } catch (\Throwable) {
                // Ignore malformed countdown payloads and render the normal page state.
            }
        }

        $pilotStack = RegionalPilotStackCatalog::forWorkspace($workspace, $config?->language_code ?: $workspace->preferredLanguageCode());
        $phoneSetupOptions = RegionalPilotStackCatalog::phoneSetupOptions($workspace->primaryMarket());
        $externalProviderOptions = RegionalPilotStackCatalog::externalProviderOptions($workspace->primaryMarket());
        $existingNumberCountryOptions = RegionalPilotStackCatalog::existingNumberCountryOptions($workspace->primaryMarket());
        $defaultExistingNumberCountry = RegionalPilotStackCatalog::inferExistingNumberCountry(
            old('forwarding_number', $phone?->forwarding_number),
            $workspace->primaryMarket()
        );

        return view('onboarding.phone', compact(
            'workspace',
            'phone',
            'configs',
            'config',
            'phoneNumbersLockedForFreePlan',
            'activationCountdownEndsAt',
            'pilotStack',
            'phoneSetupOptions',
            'externalProviderOptions',
            'existingNumberCountryOptions',
            'defaultExistingNumberCountry'
        ));
    }

    public function storePhoneNumber(Request $request, Workspace $workspace, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage phone numbers.');

        if ($workspace->isFreePlan() && ! $workspace->bypassesPlanLimits()) {
            return redirect()
                ->route('app.phone_numbers.index', $workspace)
                ->with('error', 'Free workspaces cannot connect a live phone number. Upgrade to assign a number to this assistant.');
        }

        $data = $request->validate([
            'area_code' => ['nullable', 'string', 'max:10'],
            'assistant_id' => ['nullable', 'integer'],
            'provisioning_mode' => ['nullable', 'string', Rule::in(['vapi_instant', 'existing_business_number', 'external_provider'])],
            'external_provider' => ['nullable', 'string', Rule::in(collect(RegionalPilotStackCatalog::externalProviderOptions())->pluck('value')->all())],
            'vapi_credential_id' => ['nullable', 'string', 'max:120'],
            'forwarding_number' => ['nullable', 'string', 'max:40'],
            'existing_number_country' => ['nullable', 'string', Rule::in(collect(RegionalPilotStackCatalog::existingNumberCountryOptions($workspace->primaryMarket()))->pluck('value')->all())],
            'auto_forwarding_target' => ['nullable', 'boolean'],
        ]);

        $data['forwarding_number'] = filled($data['forwarding_number'] ?? null)
            ? trim((string) $data['forwarding_number'])
            : null;

        if ($workspace->hasReachedVoiceMinuteLimit()) {
            return back()->withInput()->with('error', 'This workspace has reached its free voice limit. Upgrade to re-enable calling.');
        }

        $setupMode = $data['provisioning_mode'] ?? RegionalPilotStackCatalog::defaultPhoneSetupMode($workspace->primary_market);

        if (
            in_array($setupMode, ['existing_business_number', 'external_provider'], true)
            && blank($data['forwarding_number'])
        ) {
            return back()
                ->withErrors(['forwarding_number' => 'Enter the existing number you want this assistant to use.'])
                ->withInput();
        }

        try {
            $assistantId = !empty($data['assistant_id'])
                ? (int) $data['assistant_id']
                : (int) AssistantConfig::where('workspace_id', $workspace->id)->value('id');

            $hadProvisionedNumber = $assistantId > 0
                && WorkspacePhoneNumber::where('workspace_id', $workspace->id)
                    ->where('assistant_id', $assistantId)
                    ->whereNotNull('vapi_phone_number_id')
                    ->where('vapi_phone_number_id', '!=', '')
                    ->exists();

            $record = $provisioner->provisionPhoneNumber($workspace, $data);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $body = $e->response?->json();
            $raw = $body['message'] ?? ($body['error'] ?? $e->getMessage());
            $apiError = is_array($raw) ? implode(' ', $raw) : (string) $raw;
            return back()->withInput()->with('error', 'Vapi error: ' . $apiError);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Provisioning failed: ' . $e->getMessage());
        }

        $successMessage = match (true) {
            $setupMode === 'vapi_instant' => 'Phone number provisioned/synced in Vapi.',
            $setupMode === 'existing_business_number' && filled($record->e164) && filled($record->forwarding_number)
                => 'Forward your existing number to '.$record->e164.' whenever you are ready to switch calls over.',
            $setupMode === 'existing_business_number'
                => 'Forwarding plan saved. We will keep this number ready while you finish the routing switch.',
            $setupMode === 'external_provider' && filled($record->vapi_phone_number_id)
                => 'Existing number imported and linked to the assistant.',
            $setupMode === 'external_provider'
                => 'Import details saved. Add or confirm a Vapi BYO credential when you are ready to attach this number live.',
            default => 'Number setup saved.',
        };

        $redirect = redirect()
            ->route('app.phone_numbers.index', [
                'workspace' => $workspace,
                'assistant_id' => $record->assistant_id,
            ])
            ->with('success', $successMessage);

        if (!$hadProvisionedNumber && filled($record->vapi_phone_number_id)) {
            $redirect->with('phone_activation_countdown', [
                'assistant_id' => $record->assistant_id,
                'ends_at' => now()->addSeconds(self::PHONE_ACTIVATION_COUNTDOWN_SECONDS)->toIso8601String(),
            ]);
        }

        return $redirect;
    }

    public function destroyPhoneNumber(Request $request, Workspace $workspace, WorkspacePhoneNumber $phoneNumber, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage phone numbers.');
        abort_if($phoneNumber->workspace_id !== $workspace->id, 404);

        try {
            $provisioner->deletePhoneNumber($workspace, $phoneNumber);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $raw = $e->response?->json('message') ?? $e->getMessage();
            $apiError = is_array($raw) ? implode(' ', $raw) : (string) $raw;

            return back()->with('error', 'Vapi cleanup failed: '.$apiError);
        } catch (\Throwable $e) {
            return back()->with('error', 'Phone number could not be deleted: '.$e->getMessage());
        }

        return back()->with('success', 'Phone number removed.');
    }



    // Create assistant form
    public function create(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage assistants.');

        if ($workspace->isFreePlan() && $workspace->hasReachedVoiceMinuteLimit()) {
            return redirect()
                ->route('app.assistant.edit', $workspace)
                ->with('error', 'This free workspace has reached its 5 minute limit. Upgrade to add another assistant.');
        }

        $voices = self::vapiVoices();
        $presets = AssistantPreset::ensureDefaults();
        $defaultAssistantDraft = WorkspaceUseCaseCatalog::assistantDraft($workspace);
        $assistantRouteOptions = $this->assistantRouteOptions($workspace);

        return view('assistants.form', compact('workspace', 'voices', 'presets', 'defaultAssistantDraft', 'assistantRouteOptions'));
    }

    // Show a single assistant for editing
    public function show(Request $request, Workspace $workspace, AssistantConfig $assistant)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage assistants.');
        abort_if($assistant->workspace_id !== $workspace->id, 404);

        $config = $assistant;
        $voices = self::vapiVoices();
        $presets = AssistantPreset::ensureDefaults();
        $assistantRouteOptions = $this->assistantRouteOptions($workspace, $config);

        return view('assistants.form', compact('workspace', 'config', 'voices', 'presets', 'assistantRouteOptions'));
    }

    public function duplicate(Request $request, Workspace $workspace, AssistantConfig $assistant, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage assistants.');
        abort_if($assistant->workspace_id !== $workspace->id, 404);

        if ($workspace->isFreePlan() && $workspace->hasReachedVoiceMinuteLimit()) {
            return redirect()
                ->route('app.assistant.edit', $workspace)
                ->with('error', 'This free workspace has reached its 5 minute limit. Upgrade to add another assistant.');
        }

        $assistantLimit = $workspace->bypassesPlanLimits()
            ? -1
            : (int) ($workspace->activePlan()['max_assistants'] ?? -1);

        if ($assistantLimit !== -1 && AssistantConfig::query()->where('workspace_id', $workspace->id)->count() >= $assistantLimit) {
            return redirect()
                ->route('app.assistant.edit', $workspace)
                ->with('error', 'This plan has reached its assistant limit.');
        }

        $duplicate = null;
        $input = [
            'name' => $this->nextDuplicateAssistantName($workspace, $assistant->name),
            'first_message' => $assistant->first_message,
            'system_prompt' => $assistant->system_prompt,
            'voice_provider' => $assistant->voice_provider,
            'voice_id' => $assistant->voice_id,
            'language_code' => $assistant->language_code,
            'model_name' => $assistant->model_name,
            'intake_params' => $assistant->intake_params ?? [],
            'preset_key' => $assistant->preset_key,
            'override_params' => $assistant->override_params ?? [],
            'fallback_phone' => $assistant->fallback_phone,
            'is_active' => $assistant->is_active,
        ];
        $wasClampedForFreePlan = false;

        if ($workspace->isFreePlan() && ! $workspace->bypassesPlanLimits()) {
            $incomingModel = AssistantConfig::normalizedModelName($input['model_name'] ?? null);
            $incomingProvider = $input['voice_provider'] ?? null;
            $incomingLanguage = RegionalPilotStackCatalog::normalizeLanguageCode(
                $input['language_code'] ?? null,
                $workspace->preferredLanguageCode()
            ) ?: 'en-US';
            $recommendedVoice = RegionalPilotStackCatalog::standardVoiceProfile(
                $incomingLanguage,
                $input['preset_key'] ?? null,
                $workspace->primaryMarket()
            );
            $allowedProvider = $recommendedVoice['provider'] ?? 'vapi';

            if ($incomingModel !== AssistantConfig::DEFAULT_MODEL || ($incomingProvider && $incomingProvider !== $allowedProvider)) {
                $wasClampedForFreePlan = true;
            }

            $input['model_name'] = AssistantConfig::DEFAULT_MODEL;
            $input['voice_provider'] = $allowedProvider;
        }

        try {
            $duplicate = AssistantConfig::create([
                'workspace_id' => $workspace->id,
                'name' => $input['name'],
            ]);

            $provisioner->provisionAssistantAndToolForConfig($duplicate, $workspace, $input);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $this->cleanupFailedDuplicate($workspace, $duplicate, $provisioner);

            $raw = $e->response?->json('message') ?? $e->getMessage();
            $apiError = is_array($raw) ? implode(' ', $raw) : (string) $raw;

            return redirect()
                ->route('app.assistant.edit', $workspace)
                ->with('error', 'Duplicate sync failed: '.$apiError);
        } catch (\Throwable $e) {
            $this->cleanupFailedDuplicate($workspace, $duplicate, $provisioner);

            return redirect()
                ->route('app.assistant.edit', $workspace)
                ->with('error', 'Assistant could not be duplicated: '.$e->getMessage());
        }

        $redirect = redirect()
            ->route('app.assistant.edit', $workspace)
            ->with('success', 'Assistant duplicated successfully.');

        if ($wasClampedForFreePlan) {
            $redirect->with('warning', 'Free workspaces use the Standard AI engine. Supported languages can still use the curated regional voice path.');
        }

        return $redirect;
    }

    /**
     * Hardcoded Vapi voice catalogue (Vapi has no list-voices API).
     */
    private static function vapiVoices(): array
    {
        return RegionalPilotStackCatalog::voiceCatalog();
    }

    private function assistantRouteOptions(Workspace $workspace, ?AssistantConfig $current = null): array
    {
        $phonesByAssistant = WorkspacePhoneNumber::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('assistant_id')
            ->latest('updated_at')
            ->get(['assistant_id', 'e164'])
            ->keyBy('assistant_id');

        return AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->when($current, fn ($query) => $query->whereKeyNot($current->id))
            ->orderBy('name')
            ->get(['id', 'name', 'language_code', 'vapi_assistant_id'])
            ->map(fn (AssistantConfig $assistant) => [
                'id' => $assistant->id,
                'name' => $assistant->name,
                'language_code' => $assistant->language_code ?: $workspace->preferredLanguageCode(),
                'vapi_assistant_id' => $assistant->vapi_assistant_id,
                'is_synced' => filled($assistant->vapi_assistant_id),
                'phone' => $phonesByAssistant->get($assistant->id)?->e164,
            ])
            ->values()
            ->all();
    }

    private function normalizeAssistantIntakeParams(Workspace $workspace, array $incoming, array $existing, AssistantConfig $current): array
    {
        $normalized = array_replace_recursive($existing, $incoming);
        $operator = data_get($incoming, 'operator', data_get($existing, 'operator', []));

        if (! is_array($operator)) {
            $operator = [];
        }

        $allowedLanguages = collect(RegionalPilotStackCatalog::languageOptions($workspace->primaryMarket()))
            ->pluck('value')
            ->all();
        $workspaceLanguage = $workspace->preferredLanguageCode();
        $assistantNames = AssistantConfig::query()
            ->where('workspace_id', $workspace->id)
            ->whereKeyNot($current->id)
            ->pluck('name', 'id');
        $allowedAssistantIds = $assistantNames->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        $routes = collect(data_get($operator, 'routes', []))
            ->values()
            ->map(function ($route) use ($assistantNames, $allowedAssistantIds, $allowedLanguages, $workspaceLanguage) {
                if (! is_array($route)) {
                    return null;
                }

                $assistantId = filled($route['assistant_id'] ?? null) ? (int) $route['assistant_id'] : null;
                if ($assistantId && ! in_array($assistantId, $allowedAssistantIds, true)) {
                    $assistantId = null;
                }

                $label = $this->cleanOperatorText($route['label'] ?? '', 80);
                if ($label === '' && $assistantId) {
                    $label = (string) ($assistantNames[$assistantId] ?? '');
                }

                $keywords = $this->cleanOperatorText($route['keywords'] ?? '', 180);
                $languageCode = (string) ($route['language_code'] ?? $workspaceLanguage);
                if (! in_array($languageCode, $allowedLanguages, true)) {
                    $languageCode = $workspaceLanguage;
                }

                if ($label === '' && $keywords === '' && ! $assistantId) {
                    return null;
                }

                return [
                    'label' => $label,
                    'keywords' => $keywords,
                    'assistant_id' => $assistantId,
                    'language_code' => $languageCode,
                ];
            })
            ->filter()
            ->values()
            ->take(8)
            ->all();

        $intro = $this->cleanOperatorText(
            data_get($operator, 'intro', "Thanks for calling {$workspace->name}. Tell me which team or language you need, and I will connect you."),
            500,
        );
        $fallbackMessage = $this->cleanOperatorText(
            data_get($operator, 'fallback_message', 'I can help route the call, but I need one more detail. Which team should I connect you with?'),
            500,
        );

        $normalized['operator'] = [
            'enabled' => filter_var(data_get($operator, 'enabled', false), FILTER_VALIDATE_BOOLEAN),
            'mode' => 'spoken_handoff',
            'intro' => $intro,
            'fallback_message' => $fallbackMessage,
            'routes' => $routes,
        ];

        return $normalized;
    }

    private function cleanOperatorText(mixed $value, int $maxLength): string
    {
        $text = trim(preg_replace('/\s+/', ' ', (string) $value));

        return mb_substr($text, 0, $maxLength);
    }

    private function nextDuplicateAssistantName(Workspace $workspace, string $name): string
    {
        $baseName = trim($name) !== '' ? trim($name) : 'Assistant';
        $candidate = $baseName.' Copy';
        $suffix = 2;

        while (
            AssistantConfig::query()
                ->where('workspace_id', $workspace->id)
                ->where('name', $candidate)
                ->exists()
        ) {
            $candidate = $baseName.' Copy '.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function cleanupFailedDuplicate(Workspace $workspace, ?AssistantConfig $duplicate, VapiProvisioningService $provisioner): void
    {
        if (! $duplicate?->exists) {
            return;
        }

        $duplicate->refresh();

        try {
            if (
                filled($duplicate->vapi_tool_id)
                || filled($duplicate->vapi_booking_tool_id)
                || filled($duplicate->vapi_lookup_tool_id)
                || filled($duplicate->vapi_case_lookup_tool_id)
                || filled($duplicate->vapi_assistant_id)
            ) {
                $provisioner->deleteAssistantAndLinkedResources($workspace, $duplicate);

                return;
            }
        } catch (\Throwable) {
            // Leave the partial record alone if cleanup fails so it can be inspected manually.
            return;
        }

        $duplicate->delete();
    }

    // Delete an assistant
    public function destroy(Request $request, Workspace $workspace, AssistantConfig $assistant, VapiProvisioningService $provisioner)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin', 'manager'], 'Only workspace managers can manage assistants.');
        abort_if($assistant->workspace_id !== $workspace->id, 404);

        try {
            $provisioner->deleteAssistantAndLinkedResources($workspace, $assistant);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            $raw = $e->response?->json('message') ?? $e->getMessage();
            $apiError = is_array($raw) ? implode(' ', $raw) : (string) $raw;

            return back()->with('error', 'Vapi cleanup failed: '.$apiError);
        } catch (\Throwable $e) {
            return back()->with('error', 'Assistant could not be deleted: '.$e->getMessage());
        }

        return redirect()
            ->route('app.assistant.edit', $workspace)
            ->with('success', 'Assistant and linked phone number deleted.');
    }
}
