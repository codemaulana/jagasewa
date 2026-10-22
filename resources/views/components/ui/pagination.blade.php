@props([
    'paginator' => null,
])

@if($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-border-subtle']) }} role="navigation" aria-label="Navigasi Halaman">
        <div class="text-xs text-text-secondary">
            Menampilkan
            <span class="font-semibold text-text-main tabular-nums">{{ $paginator->firstItem() ?? 0 }}</span>
            sampai
            <span class="font-semibold text-text-main tabular-nums">{{ $paginator->lastItem() ?? 0 }}</span>
            dari
            <span class="font-semibold text-text-main tabular-nums">{{ $paginator->total() }}</span>
            data
        </div>

        <div class="flex items-center gap-1.5">
            {{-- Previous Page Link --}}
            @if($paginator->onFirstPage())
                <span class="h-10 px-3 text-xs font-medium rounded-lg text-text-muted bg-surface-subtle/50 border border-border-subtle cursor-not-allowed inline-flex items-center gap-1.5 select-none" aria-disabled="true">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="h-10 px-3 text-xs font-medium rounded-lg text-text-main bg-surface hover:bg-surface-subtle border border-border-subtle shadow-subtle transition inline-flex items-center gap-1.5 min-h-[44px]">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            {{-- Pagination Elements --}}
            <div class="hidden sm:flex items-center gap-1">
                @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                    @if($page == $paginator->currentPage())
                        <span class="h-10 min-w-[40px] px-3 text-xs font-bold rounded-lg bg-primary text-white inline-flex items-center justify-center tabular-nums shadow-subtle" aria-current="page">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="h-10 min-w-[40px] px-3 text-xs font-semibold rounded-lg text-text-main bg-surface hover:bg-surface-subtle border border-border-subtle transition inline-flex items-center justify-center tabular-nums min-h-[44px]">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            </div>

            {{-- Next Page Link --}}
            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="h-10 px-3 text-xs font-medium rounded-lg text-text-main bg-surface hover:bg-surface-subtle border border-border-subtle shadow-subtle transition inline-flex items-center gap-1.5 min-h-[44px]">
                    <span>Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <span class="h-10 px-3 text-xs font-medium rounded-lg text-text-muted bg-surface-subtle/50 border border-border-subtle cursor-not-allowed inline-flex items-center gap-1.5 select-none" aria-disabled="true">
                    <span>Selanjutnya</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
