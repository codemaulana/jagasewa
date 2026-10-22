@php
    $vendor = auth('vendor')->user()?->vendor;
    $isPro = $vendor?->isPro() ?? false;
@endphp

@if($vendor)
    <div class="flex items-center gap-2 mr-2 select-none">
        <!-- Tombol Sekunder Toko Publik -->
        <a 
            href="{{ route('public.vendor.catalog', ['vendor_slug' => $vendor->slug]) }}" 
            target="_blank" 
            rel="noopener noreferrer"
            class="hidden sm:inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-medium text-text bg-surface hover:bg-surface-subtle border border-border hover:border-border-hover transition shadow-xs"
            title="Buka etalase toko publik di tab baru"
        >
            <svg class="w-3.5 h-3.5 text-text-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            <span>Toko Publik</span>
        </a>

        <!-- Lencana Paket Kecil di Topbar -->
        <a 
            href="{{ route('vendor.billing.index') }}" 
            class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full text-[11px] font-semibold border transition shadow-xs {{
                $isPro 
                    ? 'bg-accent text-accent-text border-transparent hover:opacity-90' 
                    : 'bg-surface-subtle text-text-muted border-border hover:border-border-hover'
            }}"
            title="Kelola langganan & kuota toko"
        >
            <span class="w-1.5 h-1.5 rounded-full {{ $isPro ? 'bg-text' : 'bg-text-muted' }} flex-shrink-0"></span>
            <span>{{ $isPro ? 'Pro' : 'Free' }}</span>
        </a>
    </div>
@endif
