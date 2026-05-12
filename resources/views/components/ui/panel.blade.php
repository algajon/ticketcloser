@props([
    'title' => null,
    'description' => null,
    'muted' => false,
    'bodyClass' => '',
])

<div {{ $attributes->class($muted ? 'tc-panel-muted' : 'tc-panel') }}>
    @if($title || $description || isset($actions))
        <div class="tc-panel-header">
            <div class="min-w-0 max-w-2xl">
                @if($title)
                    <h2 class="tc-h3 break-words">{{ $title }}</h2>
                @endif

                @if($description)
                    <p class="mt-1 break-words text-sm leading-6 text-slate-600">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex min-w-0 flex-wrap items-center gap-3 sm:justify-end">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div class="{{ trim($bodyClass) !== '' ? $bodyClass : 'tc-panel-body' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="tc-panel-footer">
            {{ $footer }}
        </div>
    @endisset
</div>
