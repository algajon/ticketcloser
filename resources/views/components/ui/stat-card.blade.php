@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'slate',
])

@php
    $tones = [
        'slate' => 'bg-slate-100 text-slate-600',
        'orange' => 'bg-orange-50 text-orange-700',
        'blue' => 'bg-blue-50 text-blue-700',
        'emerald' => 'bg-emerald-50 text-emerald-700',
        'amber' => 'bg-amber-50 text-amber-700',
        'red' => 'bg-red-50 text-red-700',
    ];
@endphp

<div {{ $attributes->class('tc-kpi-card') }}>
    <div class="relative z-[1]">
        <div class="min-w-0">
            <div class="min-w-0">
                <p class="tc-kpi-label">{{ $label }}</p>
                <p class="tc-kpi-value">{{ $value }}</p>
                @if($hint)
                    <p class="tc-kpi-hint">{{ $hint }}</p>
                @endif
            </div>
        </div>

        @if(trim($slot))
            <div class="mt-4 border-t border-slate-200/80 pt-4">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
