<?php

namespace App\Http\Controllers;

use App\Models\IntakeConfig;
use App\Models\Workspace;
use App\Support\RegionalPilotStackCatalog;
use App\Support\WorkspaceUseCaseCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    private function workspaceOrFail(Request $request)
    {
        $workspace = $request->user()->currentWorkspace();
        abort_if(!$workspace, 403, 'No workspace found for user.');
        return $workspace;
    }

    public function company(Request $request)
    {
        $workspace = $this->workspaceOrFail($request)->loadMissing('intakeConfig');
        $useCases = WorkspaceUseCaseCatalog::options();
        $presetChoices = WorkspaceUseCaseCatalog::presetChoices();
        $teamSizeOptions = WorkspaceUseCaseCatalog::teamSizeOptions();
        $marketOptions = RegionalPilotStackCatalog::marketOptions();
        $selectedMarket = old('primary_market', $workspace->primary_market ?: RegionalPilotStackCatalog::GLOBAL);
        $languageOptions = RegionalPilotStackCatalog::languageOptions($selectedMarket);
        $selectedUseCase = old('use_case', $workspace->use_case ?: 'customer_support');
        $selectedUseCaseDetails = old('use_case_details', $workspace->use_case_details);
        $suggestedDefaults = WorkspaceUseCaseCatalog::definition($selectedUseCase, $selectedUseCaseDetails);
        $selectedCaptureFields = old(
            'capture_fields',
            WorkspaceUseCaseCatalog::captureFieldKeysForLabels(
                $selectedUseCase,
                $workspace->intakeConfig?->required_fields ?? [],
                $selectedUseCaseDetails
            )
        );

        if (empty($selectedCaptureFields)) {
            $selectedCaptureFields = WorkspaceUseCaseCatalog::defaultCaptureFieldKeys($selectedUseCase, $selectedUseCaseDetails);
        }

        return view('onboarding.company', compact(
            'workspace',
            'useCases',
            'presetChoices',
            'teamSizeOptions',
            'marketOptions',
            'selectedMarket',
            'languageOptions',
            'selectedUseCase',
            'selectedUseCaseDetails',
            'selectedCaptureFields',
            'suggestedDefaults',
        ));
    }

    public function saveCompany(Request $request)
    {
        $workspace = $this->workspaceOrFail($request);
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
            'case_label' => 'required|string|max:40',
            'primary_market' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::marketOptions())->pluck('value')->all()),
            'team_size' => 'nullable|string|in:' . implode(',', collect(WorkspaceUseCaseCatalog::teamSizeOptions())->pluck('value')->all()),
            'use_case' => 'required|string|in:' . implode(',', WorkspaceUseCaseCatalog::keys()),
            'use_case_details' => 'nullable|string|max:500|required_if:use_case,other',
            'assistant_name' => 'nullable|string|max:120',
            'preset_key' => 'nullable|string|in:' . implode(',', collect(WorkspaceUseCaseCatalog::presetChoices())->pluck('key')->all()),
            'language_code' => 'nullable|string|in:' . implode(',', collect(RegionalPilotStackCatalog::languageOptions())->pluck('value')->all()),
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
            return back()->withErrors(['slug' => 'Use letters, numbers, or dashes for the workspace slug.'])->withInput();
        }

        // ensure slug unique
        if ($normalizedSlug !== $workspace->slug && Workspace::where('slug', $normalizedSlug)->exists()) {
            return back()->withErrors(['slug' => 'This slug is already taken.'])->withInput();
        }

        $workspace->update([
            'name' => $data['name'],
            'slug' => $normalizedSlug,
            'default_timezone' => $data['default_timezone'],
            'case_label' => $data['case_label'],
            'primary_market' => ($data['primary_market'] ?? null) ?: RegionalPilotStackCatalog::GLOBAL,
            'team_size' => $data['team_size'] ?? null,
            'use_case' => $data['use_case'],
            'use_case_details' => $data['use_case_details'] ?? null,
            'default_assistant_name' => trim((string) ($data['assistant_name'] ?? $definition['assistant_name'])) ?: $definition['assistant_name'],
            'default_preset_key' => $data['preset_key'] ?? $definition['preset_key'],
            'default_language_code' => ($data['language_code'] ?? null) ?: RegionalPilotStackCatalog::defaultLanguageForMarket($data['primary_market'] ?? null),
            'default_phone_provisioning_mode' => RegionalPilotStackCatalog::defaultPhoneSetupMode($data['primary_market'] ?? null),
            'default_external_phone_provider' => RegionalPilotStackCatalog::defaultExternalProvider($data['primary_market'] ?? null),
            'onboarding_step' => 'done',
        ]);

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

        if (! $workspace->canCreateAssistants()) {
            return redirect()
                ->route('app.billing.plans')
                ->with('success', 'Workspace saved. Choose a paid plan to create and sync your first assistant.');
        }

        return redirect()
            ->route('app.assistant.create', $workspace)
            ->with('success', 'Workspace saved. We prefilled your first assistant based on your workflow.');
    }
}
