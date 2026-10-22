@props([
    'variant' => 'primary', // primary, secondary, ghost, danger, whatsapp, accent
    'size' => 'md', // sm, md, lg
    'type' => 'button',
    'loading' => false,
    'disabled' => false,
    'fullWidth' => false,
    'href' => null,
    'pill' => false,
])

@php
    $radiusClass = $pill ? 'rounded-full' : 'rounded-lg';
    $baseStyles = "inline-flex items-center justify-center font-medium {$radiusClass} transition duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 active:scale-[0.98] select-none";

    $sizeStyles = match($size) {
        'sm' => 'h-9 px-3 text-xs gap-1.5',
        'lg' => 'h-12 py-3 px-6 text-sm font-semibold gap-2 shadow-subtle',
        default => 'h-11 py-2.5 px-4 text-sm gap-2', // md = 44px touch target
    };

    $variantStyles = match($variant) {
        'secondary' => 'bg-surface hover:bg-surface-subtle active:bg-neutral-100 text-text-main border border-border-subtle hover:border-border-hover focus-visible:outline-primary shadow-subtle',
        'ghost' => 'bg-transparent hover:bg-surface-subtle active:bg-neutral-200 text-text-secondary hover:text-text-main focus-visible:outline-primary',
        'danger' => 'bg-danger-bg hover:bg-danger text-danger hover:text-white border border-danger-border hover:border-transparent active:opacity-90 focus-visible:outline-danger shadow-subtle',
        'whatsapp' => 'bg-brand-whatsapp hover:bg-brand-whatsapp-hover active:opacity-90 text-white shadow-subtle focus-visible:outline-brand-whatsapp',
        'accent' => 'bg-accent-lime hover:bg-accent-lime-hover text-accent-lime-text font-semibold shadow-subtle focus-visible:outline-accent-lime-text',
        default => 'bg-primary hover:bg-primary-hover active:bg-primary-active text-primary-text shadow-subtle focus-visible:outline-primary',
    };

    $disabledStyles = ($disabled || $loading) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
    $widthStyles = $fullWidth ? 'w-full' : '';

    $classes = "{$baseStyles} {$sizeStyles} {$variantStyles} {$disabledStyles} {$widthStyles}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled || $loading ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        @if($loading)
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        @endif
        {{ $slot }}
    </button>
@endif
