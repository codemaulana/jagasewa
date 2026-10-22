@props([
    'type' => 'info', // info, warning, danger, success
    'title' => null,
    'dismissible' => false,
])

@php
    $configs = [
        'info' => [
            'classes' => 'badge-status-info border',
            'titleColor' => 'text-info',
        ],
        'warning' => [
            'classes' => 'badge-status-warning border',
            'titleColor' => 'text-warning',
        ],
        'danger' => [
            'classes' => 'badge-status-danger border',
            'titleColor' => 'text-danger',
        ],
        'success' => [
            'classes' => 'badge-status-success border',
            'titleColor' => 'text-success',
        ],
    ];

    $cfg = $configs[$type] ?? $configs['info'];
@endphp

<div {{ $attributes->merge(['class' => "p-3.5 rounded-xl border text-xs flex items-start gap-2.5 leading-relaxed {$cfg['classes']}"]) }} role="alert">
    @if($type === 'info')
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
        </svg>
    @elseif($type === 'warning')
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
    @elseif($type === 'danger')
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    @elseif($type === 'success')
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
    @endif

    <div class="flex-1 min-w-0">
        @if($title)
            <div class="font-semibold mb-0.5 {{ $cfg['titleColor'] }}">{{ $title }}</div>
        @endif
        <div>
            {{ $slot }}
        </div>
    </div>
</div>
