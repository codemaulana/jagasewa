@php
    $currentPath = request()->path();
    $vendor = auth('vendor')->user()?->vendor;
@endphp

@if(auth('vendor')->check())
    <!-- Mobile Bottom Navigation (Visible only on screens <768px) -->
    <nav 
        class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-surface/95 backdrop-blur-md border-t border-border-subtle shadow-subtle flex items-center justify-around h-16 px-1 pb-safe"
        aria-label="Navigasi Bawah Vendor"
    >
        {{-- 1. Beranda / Dashboard --}}
        @php $isDashboard = request()->routeIs('filament.vendor.pages.dashboard') || $currentPath === 'vendor'; @endphp
        <a 
            href="{{ route('filament.vendor.pages.dashboard') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isDashboard ? 'text-primary font-bold' : 'text-text-muted hover:text-text-main' }}"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isDashboard ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span class="text-[10px] leading-tight">Beranda</span>
        </a>

        {{-- 2. Pesanan Sewa --}}
        @php $isBookings = str_contains($currentPath, 'vendor/bookings'); @endphp
        <a 
            href="{{ url('/vendor/bookings') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none relative {{ $isBookings ? 'text-primary font-bold' : 'text-text-muted hover:text-text-main' }}"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isBookings ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
            </svg>
            <span class="text-[10px] leading-tight">Pesanan</span>
        </a>

        {{-- 3. Katalog Barang --}}
        @php $isItems = str_contains($currentPath, 'vendor/items'); @endphp
        <a 
            href="{{ url('/vendor/items') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isItems ? 'text-primary font-bold' : 'text-text-muted hover:text-text-main' }}"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isItems ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span class="text-[10px] leading-tight">Barang</span>
        </a>

        {{-- 4. Blacklist --}}
        @php $isBlacklist = str_contains($currentPath, 'vendor/blacklist'); @endphp
        <a 
            href="{{ url('/vendor/blacklist-entries') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isBlacklist ? 'text-primary font-bold' : 'text-text-muted hover:text-text-main' }}"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isBlacklist ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            <span class="text-[10px] leading-tight">Blacklist</span>
        </a>

        {{-- 5. Paket & Kuota --}}
        @php $isBilling = str_contains($currentPath, 'vendor/billing'); @endphp
        <a 
            href="{{ route('vendor.billing.index') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isBilling ? 'text-primary font-bold' : 'text-text-muted hover:text-text-main' }}"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isBilling ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
            </svg>
            <span class="text-[10px] leading-tight">Paket</span>
        </a>
    </nav>

    <!-- Bottom Spacing Spacer to prevent content cut-off by fixed bottom nav on mobile -->
    <div class="md:hidden h-16 w-full pointer-events-none" aria-hidden="true"></div>
@endif
