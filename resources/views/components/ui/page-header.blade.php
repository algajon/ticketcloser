@props([
    'title',
    'description' => null,
    'eyebrow' => null,
])

<div {{ $attributes->class('tc-page-header') }}>
    <div class="tc-page-header-main">
        <div class="tc-page-header-copy">
            @if($eyebrow)
                <div class="tc-page-header-eyebrow">{{ $eyebrow }}</div>
            @endif

            <h1>{{ $title }}</h1>
        </div>

        @isset($actions)
            <div class="min-w-0 flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center lg:w-auto lg:justify-end [&>*]:w-full sm:[&>*]:w-auto">
                {{ $actions }}
            </div>
        @endisset
    </div>

    @if(trim($slot))
        <div class="border-t border-slate-200/80 pt-4">
            {{ $slot }}
        </div>
    @endif
</div>
