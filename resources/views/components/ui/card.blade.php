@props([
    'padding' => 'p-4 sm:p-5',
    'rounded' => 'rounded-lg',
    'hoverable' => false,
])

@php
    $hoverClasses = $hoverable ? 'hover:border-border-hover transition duration-150' : '';
@endphp

<div {{ $attributes->merge(['class' => "bg-surface border border-border-subtle shadow-subtle {$rounded} {$padding} {$hoverClasses}"]) }}>
    {{ $slot }}
</div>
