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
    $radiusClass = $pill ? 'rounded-full' : 'rounded-xl';
    $baseStyles = "inline-flex items-center justify-center font-medium {$radiusClass} transition duration-150 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 active:scale-[0.98] select-none";

    $sizeStyles = match($size) {
        'sm' => 'h-9 px-3 text-xs gap-1.5',
        'lg' => 'h-12 py-3 px-6 text-sm font-semibold gap-2 shadow-xs',
        default => 'h-11 py-2.5 px-4 text-sm gap-2', // md = 44px touch target
    };

    $variantStyles = match($variant) {
        'secondary' => 'bg-white hover:bg-neutral-50 active:bg-neutral-100 text-neutral-800 border border-neutral-200 hover:border-neutral-300 focus-visible:outline-[#111111]',
        'ghost' => 'bg-transparent hover:bg-neutral-100 active:bg-neutral-200 text-neutral-600 hover:text-neutral-900 focus-visible:outline-[#111111]',
        'danger' => 'bg-status-danger-bg hover:bg-red-600 text-status-danger-text hover:text-white border border-status-danger-border hover:border-transparent active:bg-red-700 focus-visible:outline-red-600',
        'whatsapp' => 'bg-[#25D366] hover:bg-[#20ba5a] active:bg-[#1da851] text-white shadow-xs focus-visible:outline-[#25D366]',
        'accent' => 'bg-[#86F04C] hover:bg-[#77dc3e] active:bg-[#68c633] text-[#0A0A0A] font-semibold shadow-xs focus-visible:outline-[#0A0A0A]',
        default => 'bg-[#111111] hover:bg-[#262626] active:bg-black text-white shadow-xs focus-visible:outline-[#111111]',
    };

    $disabledStyles = ($disabled || $loading) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '';
    $widthStyles = $fullWidth ? 'w-full' : '';

    $classes = "{$baseStyles} {$sizeStyles} {$variantStyles} {$disabledStyles} {$widthStyles}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
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
