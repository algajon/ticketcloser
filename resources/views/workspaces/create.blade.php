@extends('layouts.saas')

@section('title', 'tickIt - New Workspace')
@section('header_eyebrow', 'Workspace')
@section('header', 'Create workspace')
@section('header_description', 'Launch a workspace first. You can refine the rest afterward.')

@section('header_actions')
    <a href="{{ route('app.workspaces.index') }}" class="tc-btn-secondary">Back to workspaces</a>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-4xl">
        @include('workspaces.partials.setup-flow', [
            'theme' => 'light',
            'submitAction' => route('app.workspaces.store'),
            'submitLabel' => 'Create workspace',
            'backUrl' => route('app.workspaces.index'),
            'backLabel' => 'Back to workspaces',
            'workspace' => null,
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
    </div>
@endsection
