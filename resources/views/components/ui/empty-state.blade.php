@props([
    'title' => 'Tidak ada data ditemukan',
    'description' => 'Belum ada data yang tercatat untuk tampilan ini.',
    'icon' => 'box', // box, calendar, search, check
])

<div {{ $attributes->merge(['class' => 'p-8 sm:p-12 text-center bg-surface rounded-lg border border-border-subtle shadow-subtle']) }}>
    <div class="w-12 h-12 mx-auto rounded-full bg-surface-subtle border border-border-subtle flex items-center justify-center text-text-secondary mb-3.5">
        @if($icon === 'calendar')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        @elseif($icon === 'search')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
        @elseif($icon === 'check')
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
        @endif
    </div>

    <h3 class="text-sm sm:text-base font-semibold text-text-main leading-snug">{{ $title }}</h3>
    
    @if($description)
        <p class="text-xs text-text-secondary max-w-sm mx-auto mt-1 leading-relaxed">{{ $description }}</p>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-4 flex items-center justify-center gap-2.5">
            {{ $slot }}
        </div>
    @endif
</div>
