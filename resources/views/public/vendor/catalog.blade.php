<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog Rental {{ $vendor->name }} | JagaSewa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased pb-16">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold text-lg tracking-tight">🏷️ JagaSewa</span>
                <span class="text-xs bg-emerald-50 text-emerald-700 font-medium px-2 py-0.5 rounded-full border border-emerald-200">Katalog Toko</span>
            </div>
            <div class="text-xs text-gray-500 font-medium">
                Rental Terpercaya
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 pt-4 space-y-5">

        <!-- Vendor Profile Header Card -->
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center gap-3.5">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-extrabold text-2xl shadow-md shadow-emerald-500/20 flex-shrink-0">
                    {{ strtoupper(substr($vendor->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 mb-0.5">
                        <h1 class="text-lg font-bold text-gray-900 truncate">{{ $vendor->name }}</h1>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ Resmi</span>
                    </div>
                    <p class="text-xs text-gray-500 flex items-center gap-1.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        Vendor Terverifikasi JagaSewa
                    </p>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
                <span class="bg-gray-100 text-gray-600 px-2.5 py-1 rounded-lg font-medium">@ {{ $vendor->slug }}</span>
                <span class="font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                    {{ $items->total() }} Pilihan Rental
                </span>
            </div>
        </div>

        <!-- Section Title -->
        <div class="flex items-center justify-between px-1">
            <h2 class="text-sm font-bold text-gray-900 tracking-tight flex items-center gap-2">
                <span>📦</span> Katalog Rental Tersedia
            </h2>
            <span class="text-xs text-gray-400 font-medium">Klik untuk cek jadwal</span>
        </div>

        <!-- Items Grid -->
        @if($items->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($items as $item)
                    <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <!-- Thumbnail Image -->
                        <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
                            @if(!empty($item->primary_photo_url))
                                <img src="{{ $item->primary_photo_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400 p-4 text-center">
                                    <svg class="w-10 h-10 mb-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-[11px]">Foto belum ada</span>
                                </div>
                            @endif

                            @if($item->size)
                                <div class="absolute top-2.5 left-2.5 bg-black/60 backdrop-blur-sm text-white text-[11px] font-semibold px-2 py-0.5 rounded-lg">
                                    Size: {{ $item->size }}
                                </div>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 leading-snug line-clamp-2">
                                    {{ $item->name }}
                                </h3>
                                
                                <div class="mt-2.5 flex items-baseline gap-1">
                                    <span class="text-base font-extrabold text-emerald-700">
                                        Rp {{ number_format($item->price_per_day, 0, ',', '.') }}
                                    </span>
                                    <span class="text-[11px] text-gray-500 font-medium">/ hari</span>
                                </div>

                                <div class="mt-2 flex items-center justify-between text-[11px] text-gray-500 border-t border-gray-50 pt-2">
                                    <span>
                                        {{ $item->deposit_amount > 0 ? 'Dep: Rp ' . number_format($item->deposit_amount, 0, ',', '.') : 'Bebas Deposit' }}
                                    </span>
                                    <span class="text-emerald-600 font-medium">
                                        Jeda {{ $item->buffer_before + $item->buffer_after }}h
                                    </span>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <a href="{{ route('public.items.show', ['vendor_slug' => $vendor->slug, 'item_slug' => $item->slug]) }}" 
                               class="w-full text-center bg-emerald-50 hover:bg-emerald-600 active:bg-emerald-700 text-emerald-700 hover:text-white font-bold text-xs py-2.5 px-3 rounded-xl border border-emerald-200 hover:border-transparent transition flex items-center justify-center gap-1">
                                <span>Lihat Detail & Kalender</span>
                                <span class="text-[10px]">→</span>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Links -->
            <div class="pt-4">
                {{ $items->links() }}
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm text-center space-y-3">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-50 flex items-center justify-center text-2xl text-gray-400">
                    🏷️
                </div>
                <h3 class="text-sm font-bold text-gray-900">Belum Ada Barang Rental</h3>
                <p class="text-xs text-gray-500 max-w-xs mx-auto leading-relaxed">
                    Vendor ini sedang menyiapkan katalog barang rentalnya. Silakan cek kembali beberapa saat lagi.
                </p>
            </div>
        @endif

        <!-- Footer Info -->
        <footer class="pt-6 pb-4 text-center">
            <p class="text-[11px] text-gray-400">
                Didukung oleh <span class="font-semibold text-emerald-700">JagaSewa</span> • Platform Booking Rental Aman
            </p>
        </footer>

    </main>

</body>
</html>
