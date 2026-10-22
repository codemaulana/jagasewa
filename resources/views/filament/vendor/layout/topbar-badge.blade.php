@php
    $vendor = auth('vendor')->user()?->vendor;
    $isPro = $vendor?->isPro() ?? false;
@endphp

@if($vendor)
    <div class="flex items-center gap-2 mr-2 select-none">
        <!-- Lihat Toko Publik Link -->
        <a 
            href="{{ route('public.vendor.catalog', ['vendor_slug' => $vendor->slug]) }}" 
            target="_blank" 
            rel="noopener noreferrer"
            class="hidden sm:inline-flex items-center gap-1.5 h-8 px-2.5 rounded-lg text-xs font-medium text-text-secondary hover:text-text-main bg-surface-subtle hover:bg-neutral-100 border border-border-subtle transition"
            title="Buka etalase toko publik di tab baru"
        >
            <svg class="w-3.5 h-3.5 text-text-secondary" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            <span>Toko Publik</span>
        </a>

        <!-- Plan Badge di Topbar -->
        <a 
            href="{{ route('vendor.billing.index') }}" 
            class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-full text-xs font-semibold border transition shadow-subtle {{
                $isPro 
                    ? 'bg-accent-lime text-accent-lime-text border-transparent hover:opacity-90' 
                    : 'bg-surface-subtle text-text-secondary border-border-subtle hover:border-border-hover'
            }}"
            title="Kelola langganan & kuota toko"
        >
            <span class="w-1.5 h-1.5 rounded-full {{ $isPro ? 'bg-text-main' : 'bg-text-secondary' }} flex-shrink-0"></span>
            <span>{{ $isPro ? 'Paket Pro' : 'Paket Free' }}</span>
        </a>
    </div>
@endif
