@props([
    'tone' => 'slate',
])

@php
    $classes = match ($tone) {
        'primary' => 'tc-badge tc-badge-primary',
        'success' => 'tc-badge tc-badge-success',
        'warning' => 'tc-badge tc-badge-warning',
        'danger' => 'tc-badge tc-badge-danger',
        'info' => 'tc-badge tc-badge-info',
        default => 'tc-badge tc-badge-slate',
    };
@endphp

<span {{ $attributes->class($classes) }}>
    {{ $slot }}
</span>
