@props([
    'padding' => 'p-4 sm:p-5',
    'rounded' => 'rounded-xl',
    'hoverable' => false,
])

@php
    $hoverClasses = $hoverable ? 'hover:border-neutral-300 transition duration-150' : '';
@endphp

<div {{ $attributes->merge(['class' => "bg-white border border-neutral-200 shadow-xs {$rounded} {$padding} {$hoverClasses}"]) }}>
    {{ $slot }}
</div>
