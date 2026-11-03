@php
    $vendor = auth('vendor')->user()?->vendor;
    $isPro = $vendor?->isPro() ?? false;
@endphp

@if($vendor)
    <div class="flex items-center gap-2 mr-2 select-none">
        <a 
            href="{{ route('public.vendor.catalog', ['vendor_slug' => $vendor->slug]) }}" 
            target="_blank" 
            rel="noopener noreferrer"
            class="hidden sm:inline-flex items-center gap-1.5 h-8 px-3 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200/90 hover:border-slate-300 transition shadow-2xs"
            title="Buka etalase toko publik di tab baru"
        >
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            <span>Toko Publik</span>
        </a>

        <!-- Lencana Paket di Topbar -->
        <a 
            href="{{ route('vendor.billing.index') }}" 
            class="inline-flex items-center gap-1.5 h-7 px-2.5 rounded-full text-[11px] font-semibold border transition shadow-2xs {{
                $isPro 
                    ? 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-100' 
                    : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200/70'
            }}"
            title="Kelola langganan & kuota toko"
        >
            <span class="w-1.5 h-1.5 rounded-full {{ $isPro ? 'bg-blue-600' : 'bg-slate-400' }} flex-shrink-0"></span>
            <span>{{ $isPro ? 'Paket Pro' : 'Paket Free' }}</span>
        </a>
    </div>
@endif
