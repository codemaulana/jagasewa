@props([
    'variant' => 'primary', // primary, secondary, outline, ghost, danger, success, whatsapp, accent
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
    $baseStyles = "inline-flex items-center justify-center font-medium {$radiusClass} transition duration-150 ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:scale-[0.99] select-none";

    $sizeStyles = match($size) {
        'xs' => 'h-8 px-2.5 text-xs gap-1.5',
        'sm' => 'h-9 px-3 text-xs gap-1.5',
        'lg' => 'h-12 py-3 px-6 text-sm font-semibold gap-2 shadow-xs',
        default => 'h-11 py-2.5 px-4 text-sm gap-2', // md = 44px WCAG touch target
    };

    $variantStyles = match($variant) {
        'secondary' => 'bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-800 border border-slate-200 hover:border-slate-300 focus-visible:ring-slate-400 shadow-2xs',
        'outline' => 'bg-transparent hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-300 hover:border-slate-400 focus-visible:ring-slate-400',
        'ghost' => 'bg-transparent hover:bg-slate-100 active:bg-slate-200 text-slate-600 hover:text-slate-900 focus-visible:ring-slate-400',
        'danger' => 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 hover:border-rose-300 active:bg-rose-200 focus-visible:ring-rose-500 shadow-2xs',
        'success' => 'bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 hover:border-emerald-300 active:bg-emerald-200 focus-visible:ring-emerald-500 shadow-2xs',
        'whatsapp' => 'bg-[#25D366] hover:bg-[#20BA5A] active:bg-[#1DA851] text-white shadow-xs focus-visible:ring-[#25D366]',
        'accent' => 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold shadow-xs focus-visible:ring-blue-600',
        default => 'bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-white shadow-xs focus-visible:ring-slate-900',
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
