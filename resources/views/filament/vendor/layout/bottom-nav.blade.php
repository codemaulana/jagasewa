@php
    $currentPath = request()->path();
    $vendor = auth('vendor')->user()?->vendor;
@endphp

@if(auth('vendor')->check())
    <!-- Mobile Bottom Navigation (Visible only on screens <768px) -->
    <nav 
        class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-lg flex items-center justify-around h-16 px-1 pb-safe"
        aria-label="Navigasi Bawah Vendor"
    >
        {{-- 1. Dashboard --}}
        @php $isDashboard = request()->routeIs('filament.vendor.pages.dashboard') || $currentPath === 'vendor'; @endphp
        <a 
            href="{{ route('filament.vendor.pages.dashboard') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isDashboard ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}"
            title="Dashboard"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isDashboard ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span class="text-[10px] leading-tight">Dashboard</span>
        </a>

        {{-- 2. Booking --}}
        @php $isBookings = str_contains($currentPath, 'vendor/bookings'); @endphp
        <a 
            href="{{ url('/vendor/bookings') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none relative {{ $isBookings ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}"
            title="Booking"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isBookings ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
            <span class="text-[10px] leading-tight">Booking</span>
        </a>

        {{-- 3. Kalender --}}
        @php $isCalendar = str_contains($currentPath, 'vendor/calendar') || str_contains($currentPath, 'vendor/vendor-booking-calendar'); @endphp
        <a 
            href="{{ url('/vendor/vendor-booking-calendar') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isCalendar ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}"
            title="Kalender"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isCalendar ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span class="text-[10px] leading-tight">Kalender</span>
        </a>

        {{-- 4. Katalog / Barang --}}
        @php $isItems = str_contains($currentPath, 'vendor/items'); @endphp
        <a 
            href="{{ url('/vendor/items') }}" 
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none {{ $isItems ? 'text-blue-600 font-bold' : 'text-slate-500 hover:text-slate-900' }}"
            title="Katalog"
        >
            <svg class="w-5 h-5 mb-0.5 {{ $isItems ? 'stroke-[2.25]' : 'stroke-[1.75]' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3.75h4m-4 3h4m-4 3h4m-12.75-9.75h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
            </svg>
            <span class="text-[10px] leading-tight">Katalog</span>
        </a>

        {{-- 5. Lainnya (Drawer Toggle) --}}
        <button 
            type="button"
            @click="$store.sidebar.open()"
            class="flex-1 flex flex-col items-center justify-center min-h-[44px] py-1 transition select-none text-slate-500 hover:text-slate-900"
            title="Lainnya"
        >
            <svg class="w-5 h-5 mb-0.5 stroke-[1.75]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <span class="text-[10px] leading-tight">Lainnya</span>
        </button>
    </nav>
@endif