@props([
    'title',
    'description',
    'actionText' => null,
    'actionHref' => null,
])

<div {{ $attributes->class('tc-empty') }}>
    <h3 class="tc-empty-title">{{ $title }}</h3>
    <p class="tc-empty-copy">{{ $description }}</p>

    @if($actionText && $actionHref)
        <div class="mt-6">
            <a href="{{ $actionHref }}" class="tc-btn-secondary">{{ $actionText }}</a>
        </div>
    @endif

    @if(trim($slot))
        <div class="mt-5">
            {{ $slot }}
        </div>
    @endif
</div>
