<?php

namespace App\Http\Controllers;

use App\Models\IntakeConfig;
use App\Models\VoiceConfig;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Support\RegionalPilotStackCatalog;
use App\Support\WorkspaceUseCaseCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    use Concerns\AuthorizesWorkspace;


    public function index(Request $request)
    {
        $workspaces = $request->user()->workspaces;
        return view('workspaces.index', compact('workspaces'));
    }

    public function create(Request $request)
    {
        if ($request->user()->hasReachedWorkspaceLimit()) {
            return redirect()
                ->route('app.workspaces.index')
                ->with('error', 'Free workspaces are limited to 1 workspace. Upgrade to add more.');
        }

        $useCases = WorkspaceUseCaseCatalog::options();
        $presetChoices = WorkspaceUseCaseCatalog::presetChoices();
        $teamSizeOptions = WorkspaceUseCaseCatalog::teamSizeOptions();
        $marketOptions = RegionalPilotStackCatalog::marketOptions();
        $selectedMarket = old('primary_market', RegionalPilotStackCatalog::GLOBAL);
        $languageOptions = RegionalPilotStackCatalog::languageOptions($selectedMarket);
        $selectedUseCase = old('use_case', 'customer_support');
        $selectedUseCaseDetails = old('use_case_details');
        $suggestedDefaults = WorkspaceUseCaseCatalog::definition($selectedUseCase, $selectedUseCaseDetails);
        $selectedCaptureFields = old('capture_fields', WorkspaceUseCaseCatalog::defaultCaptureFieldKeys($selectedUseCase, $selectedUseCaseDetails));

        return view('workspaces.create', compact(
            'useCases',
            'presetChoices',
            'teamSizeOptions',
            'marketOptions',
            'selectedMarket',
            'languageOptions',
            'selectedUseCase',
            'selectedUseCaseDetails',
            'selectedCaptureFields',
            'suggestedDefaults'
        ));
    }

    public function store(Request $request)
    {
        if ($request->user()->hasReachedWorkspaceLimit()) {
            return redirect()
                ->route('app.workspaces.index')
                ->with('error', 'Free workspaces are limited to 1 workspace. Upgrade to add more.');
        }

        $selectedUseCase = (string) $request->input('use_case', 'customer_support');
        $selectedUseCaseDetails = $request->input('use_case_details');

        $captureFieldKeys = collect(WorkspaceUseCaseCatalog::options())
            ->flatMap(fn (array $definition) => collect($definition['capture_fields'] ?? [])->pluck('key'))
            ->unique()
            ->values()
            ->all();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:80|alpha_dash',
            'default_timezone' => 'required|string|max:80',
            'primary_market' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::marketOptions())->pluck('value')->all()),
            'team_size' => 'nullable|string|in:' . implode(',', collect(WorkspaceUseCaseCatalog::teamSizeOptions())->pluck('value')->all()),
            'use_case' => 'required|string|in:' . implode(',', WorkspaceUseCaseCatalog::keys()),
            'use_case_details' => 'nullable|string|max:500|required_if:use_case,other',
            'assistant_name' => 'nullable|string|max:120',
            'preset_key' => 'nullable|string|in:' . implode(',', collect(WorkspaceUseCaseCatalog::presetChoices())->pluck('key')->all()),
            'language_code' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::languageOptions())->pluck('value')->all()),
            'case_label' => 'nullable|string|max:40',
            'capture_fields' => 'nullable|array|min:2',
            'capture_fields.*' => 'nullable|string|in:' . implode(',', $captureFieldKeys),
        ]);

        $definition = WorkspaceUseCaseCatalog::definition($data['use_case'], $data['use_case_details'] ?? null);
        $resolvedCaptureFieldKeys = $data['capture_fields'] ?? WorkspaceUseCaseCatalog::defaultCaptureFieldKeys(
            $data['use_case'],
            $data['use_case_details'] ?? null
        );

        if ($resolvedCaptureFieldKeys === []) {
            $resolvedCaptureFieldKeys = WorkspaceUseCaseCatalog::defaultCaptureFieldKeys($selectedUseCase, $selectedUseCaseDetails);
        }

        $normalizedSlug = Str::slug($data['slug']);

        if ($normalizedSlug === '') {
            return back()
                ->withErrors(['slug' => 'Use letters, numbers, or dashes for the workspace slug.'])
                ->withInput();
        }

        if (Workspace::where('slug', $normalizedSlug)->exists()) {
            return back()
                ->withErrors(['slug' => 'This slug is already taken.'])
                ->withInput();
        }

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => $normalizedSlug,
            'default_timezone' => $data['default_timezone'],
            'case_label' => trim((string) ($data['case_label'] ?? $definition['case_label'])) ?: $definition['case_label'],
            'credits_balance' => 0,
            'onboarding_step' => 'done',
            'primary_market' => ($data['primary_market'] ?? null) ?: RegionalPilotStackCatalog::GLOBAL,
            'use_case' => $data['use_case'],
            'use_case_details' => $data['use_case_details'] ?? null,
            'team_size' => $data['team_size'] ?? null,
            'default_assistant_name' => trim((string) ($data['assistant_name'] ?? $definition['assistant_name'])) ?: $definition['assistant_name'],
            'default_preset_key' => $data['preset_key'] ?? $definition['preset_key'],
            'default_language_code' => ($data['language_code'] ?? null) ?: RegionalPilotStackCatalog::defaultLanguageForMarket($data['primary_market'] ?? null),
            'default_phone_provisioning_mode' => RegionalPilotStackCatalog::defaultPhoneSetupMode($data['primary_market'] ?? null),
            'default_external_phone_provider' => RegionalPilotStackCatalog::defaultExternalProvider($data['primary_market'] ?? null),
        ]);

        WorkspaceMembership::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'role' => WorkspaceMembership::ROLE_OWNER,
        ]);

        VoiceConfig::firstOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'provider' => 'vapi',
                'transcript_enabled' => true,
                'recording_enabled' => true,
            ]
        );

        $requiredFields = WorkspaceUseCaseCatalog::resolveCaptureFields(
            $data['use_case'],
            $resolvedCaptureFieldKeys,
            $data['use_case_details'] ?? null
        );

        IntakeConfig::updateOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'required_fields' => $requiredFields,
                'category_options' => $definition['category_options'],
                'priority_rules' => $definition['priority_rules'],
            ]
        );

        $workspace->unsetRelation('intakeConfig');
        $defaults = WorkspaceUseCaseCatalog::applyWorkspaceDefaults($workspace);

        IntakeConfig::updateOrCreate(
            ['workspace_id' => $workspace->id],
            [
                'system_prompt' => $defaults['system_prompt'],
                'required_fields' => $requiredFields,
                'category_options' => $definition['category_options'],
                'priority_rules' => $definition['priority_rules'],
            ]
        );

        $request->session()->put('current_workspace_id', $workspace->id);

        return redirect()
            ->route('app.assistant.create', $workspace)
            ->with('success', 'Workspace created. We prefilled your first assistant based on your workflow.');
    }

    public function checkSlug(Request $request)
    {
        $slug = Str::slug((string) $request->query('slug', ''));
        $ignore = (int) $request->query('ignore', 0);

        if ($slug === '') {
            return response()->json([
                'slug' => '',
                'available' => false,
                'message' => 'Use letters, numbers, or dashes.',
            ]);
        }

        $query = Workspace::query()->where('slug', $slug);

        if ($ignore > 0) {
            $query->whereKeyNot($ignore);
        }

        $available = ! $query->exists();

        return response()->json([
            'slug' => $slug,
            'available' => $available,
            'message' => $available ? 'Available' : 'Already taken',
        ]);
    }

    public function switch(Request $request, Workspace $workspace)
    {
        abort_unless(
            $request->user()->hasWorkspace($workspace->id),
            403
        );
        $request->session()->put('current_workspace_id', $workspace->id);
        return redirect()->route('app.dashboard');
    }

    public function settings(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin'], 'Only workspace admins can change workspace settings.');
        $selectedMarket = old('primary_market', $workspace->primaryMarket());
        $languageOptions = RegionalPilotStackCatalog::languageOptions($selectedMarket);
        $marketOptions = RegionalPilotStackCatalog::marketOptions();
        $phoneSetupOptions = RegionalPilotStackCatalog::phoneSetupOptions($selectedMarket);
        $externalProviderOptions = RegionalPilotStackCatalog::externalProviderOptions($selectedMarket);
        $pilotStack = RegionalPilotStackCatalog::forWorkspace(
            $workspace,
            old('default_language_code', $workspace->preferredLanguageCode())
        );

        return view('workspaces.settings', compact(
            'workspace',
            'selectedMarket',
            'languageOptions',
            'marketOptions',
            'phoneSetupOptions',
            'externalProviderOptions',
            'pilotStack',
        ));
    }

    public function updateSettings(Request $request, Workspace $workspace)
    {
        $this->authorizeWorkspaceRole($request, $workspace, ['owner', 'admin'], 'Only workspace admins can change workspace settings.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'default_timezone' => 'required|string|max:80',
            'case_label' => 'required|string|max:40',
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
            'primary_market' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::marketOptions())->pluck('value')->all()),
            'default_language_code' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::languageOptions())->pluck('value')->all()),
            'default_phone_provisioning_mode' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::phoneSetupOptions())->pluck('value')->all()),
            'default_external_phone_provider' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::externalProviderOptions())->pluck('value')->all()),
            'default_vapi_credential_id' => 'nullable|string|max:120',
        ]);

        $data['primary_market'] = $data['primary_market'] ?: $workspace->primaryMarket();
        $data['default_language_code'] = $data['default_language_code'] ?: RegionalPilotStackCatalog::defaultLanguageForMarket($data['primary_market']);
        $data['default_phone_provisioning_mode'] = $data['default_phone_provisioning_mode'] ?: RegionalPilotStackCatalog::defaultPhoneSetupMode($data['primary_market']);
        $data['default_external_phone_provider'] = $data['default_phone_provisioning_mode'] === 'vapi_instant'
            ? null
            : ($data['default_external_phone_provider'] ?: RegionalPilotStackCatalog::defaultExternalProvider($data['primary_market']));
        $data['default_vapi_credential_id'] = $data['default_phone_provisioning_mode'] === 'vapi_instant'
            ? null
            : ($data['default_vapi_credential_id'] ?: null);
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

        return back()->with('success', 'Settings saved.');
    }
}
