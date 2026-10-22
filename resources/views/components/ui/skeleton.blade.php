@props([
    'type' => 'text', // text, title, avatar, card, button, input
    'count' => 1,
])

@php
    $typeClasses = match($type) {
        'title' => 'h-7 w-1/2 rounded-md',
        'avatar' => 'w-10 h-10 rounded-full flex-shrink-0',
        'card' => 'h-36 w-full rounded-lg',
        'button' => 'h-11 w-32 rounded-lg',
        'input' => 'h-11 w-full rounded-lg',
        default => 'h-4 w-full rounded-sm',
    };
@endphp

@for($i = 0; $i < $count; $i++)
    <div {{ $attributes->merge(['class' => "animate-pulse bg-surface-subtle border border-border-subtle/40 {$typeClasses}"]) }} aria-hidden="true"></div>
@endfor
