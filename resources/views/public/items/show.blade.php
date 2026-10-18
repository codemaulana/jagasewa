<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $item->name }} — {{ $vendor->name }} | JagaSewa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased pb-28">

    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold text-lg tracking-tight">🏷️ JagaSewa</span>
                <span class="text-xs bg-emerald-50 text-emerald-700 font-medium px-2 py-0.5 rounded-full border border-emerald-200">Katalog Publik</span>
            </div>
            <div class="text-xs text-gray-500 font-medium">
                Rental Terpercaya
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 pt-4 space-y-4">

        <!-- Vendor Header Banner -->
        <div class="flex items-center justify-between bg-white p-3 rounded-2xl border border-gray-100 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center text-white font-bold text-base shadow-sm">
                    {{ strtoupper(substr($vendor->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 leading-tight">{{ $vendor->name }}</h2>
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Vendor Terverifikasi
                    </p>
                </div>
            </div>
            <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-lg font-medium">@ {{ $vendor->slug }}</span>
        </div>

        <!-- Gallery / Foto Barang -->
        <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
            @if(!empty($photos))
                <div class="relative aspect-[4/3] bg-gray-100">
                    <img id="main-product-image" src="{{ $photos[0] }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                    @if(count($photos) > 1)
                        <div class="absolute bottom-3 right-3 bg-black/60 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-full font-medium">
                            <span id="active-photo-index">1</span> / {{ count($photos) }} Foto
                        </div>
                    @endif
                </div>

                @if(count($photos) > 1)
                    <div class="p-3 flex gap-2 overflow-x-auto border-t border-gray-50">
                        @foreach($photos as $index => $photoUrl)
                            <button type="button" 
                                    onclick="switchPhoto('{{ $photoUrl }}', {{ $index + 1 }})" 
                                    class="photo-thumb flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden border-2 {{ $index === 0 ? 'border-emerald-500' : 'border-transparent' }} focus:outline-none transition">
                                <img src="{{ $photoUrl }}" alt="Thumbnail {{ $index + 1 }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="aspect-[4/3] bg-gradient-to-b from-gray-50 to-gray-100 flex flex-col items-center justify-center text-gray-400 p-6 text-center">
                    <svg class="w-16 h-16 mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-xs font-medium">Foto barang belum diunggah</p>
                </div>
            @endif
        </div>

        <!-- Judul & Harga Pokok -->
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    @if($item->size)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Ukuran: {{ $item->size }}
                        </span>
                    @endif
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                        ID: {{ substr($item->ulid, 0, 8) }}
                    </span>
                </div>
                <h1 class="text-xl font-bold text-gray-900 leading-snug">{{ $item->name }}</h1>
            </div>

            <!-- Price Card -->
            <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl p-4 flex items-baseline justify-between">
                <div>
                    <span class="text-xs text-emerald-800 font-medium block">Tarif Sewa Harian</span>
                    <span class="text-2xl font-black text-emerald-700">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</span>
                    <span class="text-xs text-emerald-600 font-medium"> / hari</span>
                </div>
                <div class="text-right">
                    <span class="inline-block text-xs bg-emerald-600 text-white font-semibold px-2 py-0.5 rounded-md shadow-xs">Tersedia</span>
                </div>
            </div>

            <!-- Deposit & Denda Grid -->
            <div class="grid grid-cols-2 gap-3 pt-1">
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3">
                    <span class="text-xs text-gray-500 font-medium block mb-0.5">Uang Jaminan (Deposit)</span>
                    <span class="text-sm font-bold text-gray-900">
                        {{ $item->deposit_amount > 0 ? 'Rp ' . number_format($item->deposit_amount, 0, ',', '.') : 'Bebas Deposit' }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Dikembalikan penuh jika tidak ada kerusakan</p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3">
                    <span class="text-xs text-gray-500 font-medium block mb-0.5">Denda Telat / Hari</span>
                    <span class="text-sm font-bold text-rose-600">
                        Rp {{ number_format($item->fine_per_day, 0, ',', '.') }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Dikenakan per 24 jam keterlambatan</p>
                </div>
            </div>
        </div>

        <!-- Atribut Rental (Kelengkapan Set & Perawatan) -->
        @if(!empty($item->included_items) || $item->care_instructions)
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                    <span>✨</span> Kelengkapan & Spesifikasi
                </h3>

                @php
                    $includedList = is_array($item->included_items) 
                        ? $item->included_items 
                        : (is_string($item->included_items) && trim($item->included_items) !== '' ? array_map('trim', explode(',', $item->included_items)) : []);
                @endphp

                @if(!empty($includedList))
                    <div>
                        <span class="text-xs text-gray-500 font-medium block mb-2">Item yang Didapat (Included Set):</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($includedList as $included)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                    ✓ {{ $included }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($item->care_instructions)
                    <div class="bg-amber-50/70 border border-amber-200/60 rounded-2xl p-3 text-xs text-amber-900">
                        <span class="font-bold flex items-center gap-1.5 text-amber-900 mb-1">
                            <span>⚠️</span> Instruksi Perawatan & Kebersihan:
                        </span>
                        <p class="leading-relaxed text-amber-800">{{ $item->care_instructions }}</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Ketentuan Jeda Otomatis (Smart Buffer) -->
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span>🛡️</span> Ketentuan Jeda Otomatis (Smart Buffer)
            </h3>
            <p class="text-xs text-gray-600 leading-relaxed">
                Untuk memastikan barang tiba dalam kondisi prima dan bersih, vendor menerapkan jeda jadwal otomatis di kalender:
            </p>
            <div class="grid grid-cols-2 gap-3 pt-1">
                <div class="border border-gray-100 rounded-2xl p-3 bg-gray-50/50">
                    <span class="text-[11px] text-gray-500 font-medium block">Jeda Sebelum Sewa</span>
                    <span class="text-sm font-bold text-gray-800">{{ $item->buffer_before }} Hari</span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Persiapan & laundry</p>
                </div>
                <div class="border border-gray-100 rounded-2xl p-3 bg-gray-50/50">
                    <span class="text-[11px] text-gray-500 font-medium block">Jeda Sesudah Sewa</span>
                    <span class="text-sm font-bold text-gray-800">{{ $item->buffer_after }} Hari</span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Pengiriman balik & cek barang</p>
                </div>
            </div>
        </div>

        <!-- Interactive Smart Buffer Calendar -->
        <livewire:item-availability-calendar :item="$item" />

        <!-- Public Booking Form Section -->
        <div id="booking-section">
            <livewire:public.booking-form :item="$item" />
        </div>

        <!-- Deskripsi Barang -->
        @if($item->description)
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-2">
                <h3 class="text-sm font-bold text-gray-900">Deskripsi Lengkap</h3>
                <div class="text-xs text-gray-600 leading-relaxed whitespace-pre-line">
                    {{ $item->description }}
                </div>
            </div>
        @endif

    </main>

    <!-- Bottom Sticky CTA -->
    <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-gray-100 p-4 z-40">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-4">
            <div>
                <span class="text-[11px] text-gray-400 block font-medium">Total Tarif Sewa</span>
                <span class="text-lg font-black text-emerald-700">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</span>
                <span class="text-xs text-gray-500"> / hari</span>
            </div>
            <a href="#booking-section" 
               class="flex-1 max-w-[200px] text-center bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold py-3 px-5 rounded-2xl shadow-lg shadow-emerald-600/25 transition">
                Pesan Sekarang
            </a>
        </div>
    </div>

    <!-- Script for photo switching -->
    <script>
        function switchPhoto(url, index) {
            const mainImg = document.getElementById('main-product-image');
            const indexLabel = document.getElementById('active-photo-index');
            if (mainImg) mainImg.src = url;
            if (indexLabel) indexLabel.innerText = index;

            document.querySelectorAll('.photo-thumb').forEach((thumb, i) => {
                if (i === index - 1) {
                    thumb.classList.add('border-emerald-500');
                    thumb.classList.remove('border-transparent');
                } else {
                    thumb.classList.remove('border-emerald-500');
                    thumb.classList.add('border-transparent');
                }
            });
        }
    </script>
    @livewireScripts
</body>
</html>
