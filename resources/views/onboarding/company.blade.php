@extends('layouts.guest')

@section('title', 'tickIt - Workspace Setup')
@section('guest_layout', 'centered')
@section('auth_width', 'max-w-3xl')

@section('content')
    @include('workspaces.partials.setup-flow', [
        'theme' => 'dark',
        'submitAction' => route('app.onboarding.company.save'),
        'submitLabel' => 'Create workspace',
        'workspace' => $workspace,
        'useCases' => $useCases,
        'presetChoices' => $presetChoices,
        'teamSizeOptions' => $teamSizeOptions,
        'languageOptions' => $languageOptions,
        'selectedUseCase' => $selectedUseCase,
        'selectedUseCaseDetails' => $selectedUseCaseDetails,
        'selectedCaptureFields' => $selectedCaptureFields,
        'suggestedDefaults' => $suggestedDefaults,
        'slugCheckUrl' => route('app.workspaces.check-slug'),
    ])
@endsection
