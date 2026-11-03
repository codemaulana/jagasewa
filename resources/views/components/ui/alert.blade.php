@props([
    'type' => 'info', // info, warning, danger, success
    'title' => null,
    'dismissible' => false,
])

@php
    $configs = [
        'info' => [
            'classes' => 'bg-sky-50/80 border-sky-200/90 text-sky-900',
            'iconColor' => 'text-sky-600',
            'titleColor' => 'text-sky-950 font-bold',
        ],
        'warning' => [
            'classes' => 'bg-amber-50/80 border-amber-200/90 text-amber-900',
            'iconColor' => 'text-amber-600',
            'titleColor' => 'text-amber-950 font-bold',
        ],
        'danger' => [
            'classes' => 'bg-rose-50/80 border-rose-200/90 text-rose-900',
            'iconColor' => 'text-rose-600',
            'titleColor' => 'text-rose-950 font-bold',
        ],
        'success' => [
            'classes' => 'bg-emerald-50/80 border-emerald-200/90 text-emerald-900',
            'iconColor' => 'text-emerald-600',
            'titleColor' => 'text-emerald-950 font-bold',
        ],
    ];

    $cfg = $configs[$type] ?? $configs['info'];
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-xl border text-xs sm:text-sm flex items-start gap-3 leading-relaxed shadow-2xs {$cfg['classes']}"]) }} role="alert">
    @if($type === 'info')
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $cfg['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
    @elseif($type === 'warning')
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $cfg['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
    @elseif($type === 'danger')
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $cfg['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    @elseif($type === 'success')
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5 {{ $cfg['iconColor'] }}" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    @endif

    <div class="flex-1 min-w-0">
        @if($title)
            <div class="mb-0.5 {{ $cfg['titleColor'] }}">{{ $title }}</div>
        @endif
        <div class="text-slate-700">
            {{ $slot }}
        </div>
    </div>
</div>
