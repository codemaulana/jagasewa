@props([
    'padding' => 'p-4 sm:p-5 sm:p-6',
    'rounded' => 'rounded-xl',
    'hoverable' => false,
    'title' => null,
    'subtitle' => null,
])

@php
    $hoverClasses = $hoverable ? 'hover:border-slate-300 hover:shadow-sm transition duration-150' : '';
@endphp

<div {{ $attributes->merge(['class' => "bg-white border border-slate-200/90 shadow-2xs {$rounded} {$hoverClasses}"]) }}>
    @if($title || isset($header) || isset($actions))
        <div class="px-4 sm:px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                @if(isset($header))
                    {{ $header }}
                @else
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">{{ $title }}</h3>
                    @if($subtitle)
                        <p class="text-xs text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                @endif
            </div>
            @if(isset($actions))
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-4 sm:px-6 py-3.5 bg-slate-50/60 border-t border-slate-100 rounded-b-xl">
            {{ $footer }}
        </div>
    @endif
</div>
