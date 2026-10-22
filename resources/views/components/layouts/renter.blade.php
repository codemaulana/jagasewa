@props([
    'title' => 'JagaSewa — Platform Booking Rental Aman',
    'vendor' => null,
    'headerTag' => null,
    'backUrl' => null,
    'backText' => null,
    'maxWidth' => 'max-w-xl',
])

<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('jagasewa_logo3.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Stylesheet & Client Scripting -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-bg-base text-text-main antialiased flex flex-col justify-between selection:bg-accent-lime selection:text-accent-lime-text">

    <!-- Header Portal Penyewa (Bernuansa Etalase Toko, Bukan Dashboard) -->
    <header class="sticky top-0 z-30 bg-surface/95 backdrop-blur-md border-b border-border-subtle shadow-2xs">
        <div class="{{ $maxWidth }} mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2.5 min-w-0">
                @if($vendor)
                    <a href="{{ route('public.vendor.catalog', ['vendor_slug' => $vendor->slug]) }}" class="flex items-center gap-2 hover:opacity-90 transition group flex-shrink-0">
                        @if($vendor->logo_url)
                            <img src="{{ $vendor->logo_url }}" alt="Logo {{ $vendor->name }}" class="w-8 h-8 rounded-lg object-cover border border-border-subtle flex-shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-lg bg-surface-subtle border border-border-subtle flex items-center justify-center text-text-main font-bold text-xs flex-shrink-0">
                                {{ strtoupper(substr($vendor->name, 0, 1)) }}
                            </div>
                        @endif
                        <span class="text-sm font-bold text-text-main truncate max-w-[130px] sm:max-w-[200px]">{{ $vendor->name }}</span>
                    </a>
                @else
                    <a href="{{ url('/') }}" class="flex items-center gap-2 hover:opacity-90 transition flex-shrink-0">
                        <img src="{{ asset('jagasewa_logo1.png') }}" alt="JagaSewa" class="h-7 w-auto object-contain">
                    </a>
                @endif

                @if($headerTag)
                    <span class="hidden xs:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-surface-subtle text-text-secondary border border-border-subtle flex-shrink-0">
                        {{ $headerTag }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
                @if($backUrl)
                    <a 
                        href="{{ $backUrl }}" 
                        class="text-xs text-text-secondary hover:text-text-main font-medium transition min-h-[44px] inline-flex items-center gap-1"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                        <span>{{ $backText ?? 'Kembali' }}</span>
                    </a>
                @else
                    <div class="inline-flex items-center gap-1.5 text-[11px] font-medium text-text-secondary">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-lime-hover"></span>
                        <span class="hidden sm:inline">Penyewaan Terverifikasi</span>
                        <span class="sm:hidden">Aman</span>
                    </div>
                @endif
            </div>
        </div>
    </header>

    <!-- Kontainer Konten Utama -->
    <main class="{{ $maxWidth }} mx-auto px-4 pt-4 sm:pt-6 pb-20 w-full space-y-4 flex-1">
        {{ $slot }}
    </main>

    <!-- Footer Tenang & Terpercaya -->
    @if(!($vendor?->isWhiteLabelActive() ?? false))
        <footer class="border-t border-border-subtle py-8 bg-surface/70 text-center text-xs text-text-secondary">
            <div class="{{ $maxWidth }} mx-auto px-4 space-y-2">
                <div class="flex items-center justify-center gap-1.5 text-text-main font-semibold">
                    <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-2.18-7.5a9.04 9.04 0 0 0-4.07 1.13A9.04 9.04 0 0 0 4.13 8.25a9.04 9.04 0 0 0-.13 4.07 9.04 9.04 0 0 0 1.13 4.07 9.04 9.04 0 0 0 4.07 3.61 9.04 9.04 0 0 0 4.07-.13 9.04 9.04 0 0 0 3.61-4.07 9.04 9.04 0 0 0-.13-4.07 9.04 9.04 0 0 0-3.61-3.61 9.04 9.04 0 0 0-4.07-1.13Z" />
                    </svg>
                    <span>Pemesanan Tanpa Login & Data Identitas Terlindungi</span>
                </div>
                <p class="text-[11px] text-text-muted leading-relaxed">
                    Didukung oleh <a href="https://jagasewa.web.id" target="_blank" class="font-medium text-text-secondary hover:underline">JagaSewa</a> • Platform Penyewaan Aman
                </p>
            </div>
        </footer>
    @endif

    <!-- Global Toast Component for Alerts & Livewire Flashes -->
    <x-ui.toast />

    @livewireScripts
</body>
</html>
