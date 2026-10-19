<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Katalog {{ $vendor->name }} | JagaSewa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('public.partials.design-tokens')
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased pb-16">

    <!-- Header Navigasi Publik -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-neutral-200">
        <div class="max-w-xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-neutral-900 font-bold text-base tracking-tight hover:text-primary-700 transition">
                    <svg class="w-5 h-5 text-primary-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                    <span>JagaSewa</span>
                </a>
                <span class="text-[11px] font-medium bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full border border-neutral-200">Katalog</span>
            </div>
            <div class="text-xs text-neutral-500 font-medium">
                Penyewaan Terverifikasi
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 pt-4 space-y-4">

        <!-- Informasi Vendor -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-800 font-bold text-lg flex-shrink-0">
                    {{ strtoupper(substr($vendor->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 mb-0.5">
                        <h1 class="text-base font-bold text-neutral-900 truncate">{{ $vendor->name }}</h1>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium badge-status-info border">Terverifikasi</span>
                    </div>
                    <p class="text-xs text-neutral-500">
                        Penyedia rental terdaftar di JagaSewa
                    </p>
                </div>
            </div>

            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500">
                <span class="bg-neutral-100 text-neutral-700 px-2 py-0.5 rounded font-mono text-[11px]">@ {{ $vendor->slug }}</span>
                <span class="font-medium text-neutral-700">
                    {{ $items->total() }} Pilihan Rental
                </span>
            </div>
        </div>

        <!-- Judul Bagian -->
        <div class="flex items-center justify-between px-0.5 pt-1">
            <h2 class="text-sm font-semibold text-neutral-900">
                Daftar barang rental
            </h2>
            <span class="text-xs text-neutral-500">Pilih untuk melihat jadwal</span>
        </div>

        <!-- Grid Barang -->
        @if($items->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                @foreach($items as $item)
                    <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs hover:border-neutral-300 transition duration-150 flex flex-col justify-between">
                        <!-- Foto Utama -->
                        <div class="relative aspect-[4/3] bg-neutral-100 overflow-hidden">
                            @if(!empty($item->primary_photo_url))
                                <img src="{{ $item->primary_photo_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center text-neutral-400 p-4 text-center">
                                    <svg class="w-8 h-8 mb-1 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    <span class="text-[11px]">Foto belum tersedia</span>
                                </div>
                            @endif

                            @if($item->size)
                                <div class="absolute top-2 left-2 bg-neutral-900/80 backdrop-blur-xs text-white text-[11px] font-medium px-2 py-0.5 rounded">
                                    Ukuran: {{ $item->size }}
                                </div>
                            @endif
                        </div>

                        <!-- Isi Kartu -->
                        <div class="p-3.5 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                <h3 class="text-sm font-semibold text-neutral-900 leading-snug line-clamp-2">
                                    {{ $item->name }}
                                </h3>
                                
                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="text-base font-bold text-neutral-900">
                                        Rp {{ number_format($item->price_per_day, 0, ',', '.') }}
                                    </span>
                                    <span class="text-xs text-neutral-500">/ hari</span>
                                </div>

                                <div class="mt-2 flex items-center justify-between text-[11px] text-neutral-500 border-t border-neutral-100 pt-2">
                                    <span>
                                        {{ $item->deposit_amount > 0 ? 'Deposit Rp ' . number_format($item->deposit_amount, 0, ',', '.') : 'Tanpa deposit' }}
                                    </span>
                                    <span class="text-neutral-600 font-medium">
                                        Jeda {{ $item->buffer_before + $item->buffer_after }} hari
                                    </span>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <a href="{{ route('public.items.show', ['vendor_slug' => $vendor->slug, 'item_slug' => $item->slug]) }}" 
                               class="w-full text-center bg-white hover:bg-neutral-50 active:bg-neutral-100 text-neutral-900 font-semibold text-xs h-10 px-3 rounded-xl border border-neutral-300 transition duration-150 flex items-center justify-center">
                                Detail & ketersediaan
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Navigasi Halaman -->
            <div class="pt-3">
                {{ $items->links() }}
            </div>
        @else
            <!-- State Kosong -->
            <div class="bg-white p-8 rounded-xl border border-neutral-200 text-center space-y-2">
                <svg class="w-10 h-10 mx-auto text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                </svg>
                <h3 class="text-sm font-semibold text-neutral-900">Belum Ada Barang Rental</h3>
                <p class="text-xs text-neutral-500 max-w-xs mx-auto leading-relaxed">
                    Katalog barang rental sedang dipersiapkan oleh vendor.
                </p>
            </div>
        @endif

        <!-- Footer -->
        <footer class="pt-6 pb-2 text-center border-t border-neutral-200">
            <p class="text-[11px] text-neutral-400">
                Didukung oleh JagaSewa • Platform Penyewaan Aman
            </p>
        </footer>

    </main>

</body>
</html>
