<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $item->name }} — {{ $vendor->name }} | JagaSewa</title>
    <link rel="icon" type="image/png" href="{{ asset('jagasewa_logo3.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('public.partials.design-tokens')
    @livewireStyles
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased pb-28">

    <!-- Header Navigasi Publik -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-neutral-200">
        <div class="max-w-xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('public.vendor.catalog', ['vendor_slug' => $vendor->slug]) }}" class="flex items-center gap-2 text-neutral-900 font-bold text-base tracking-tight hover:text-neutral-700 transition">
                    <img src="{{ asset('jagasewa_logo1.png') }}" alt="JagaSewa" class="h-8 sm:h-9 w-auto object-contain" style="height: 36px; width: auto; max-width: 160px; object-fit: contain;">
                </a>
                <span class="text-[11px] font-medium bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full border border-neutral-200">Katalog</span>
            </div>
            <a href="{{ route('public.vendor.catalog', ['vendor_slug' => $vendor->slug]) }}" class="text-xs text-neutral-500 hover:text-neutral-900 font-medium transition min-h-[44px] inline-flex items-center">
                Lihat semua barang
            </a>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 pt-4 space-y-4">

        <!-- Banner Vendor -->
        <div class="flex items-center justify-between bg-white p-3.5 rounded-xl border border-neutral-200 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-800 font-bold text-sm flex-shrink-0">
                    {{ strtoupper(substr($vendor->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-neutral-900 leading-tight">{{ $vendor->name }}</h2>
                    <p class="text-xs text-neutral-500 flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-neutral-900"></span>
                        Terverifikasi
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#86F04C] text-[#0A0A0A]">
                    Jaminan Aman
                </span>
                <span class="text-xs bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded font-mono text-[11px]">@ {{ $vendor->slug }}</span>
            </div>
        </div>

        <!-- Galeri Foto Barang -->
        <div class="bg-white rounded-xl border border-neutral-200 overflow-hidden shadow-xs">
            @if(!empty($photos))
                <div class="relative aspect-[4/3] bg-neutral-100">
                    <img id="main-product-image" src="{{ $photos[0] }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                    @if(count($photos) > 1)
                        <div class="absolute bottom-2.5 right-2.5 bg-neutral-900/80 backdrop-blur-xs text-white text-[11px] px-2 py-0.5 rounded font-medium">
                            <span id="active-photo-index">1</span> / {{ count($photos) }}
                        </div>
                    @endif
                </div>

                @if(count($photos) > 1)
                    <div class="p-2.5 flex gap-2 overflow-x-auto border-t border-neutral-100">
                        @foreach($photos as $index => $photoUrl)
                            <button type="button" 
                                    onclick="switchPhoto('{{ $photoUrl }}', {{ $index + 1 }})" 
                                    class="photo-thumb flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 {{ $index === 0 ? 'border-neutral-900' : 'border-transparent' }} focus:outline-none transition min-h-[44px]">
                                <img src="{{ $photoUrl }}" alt="Foto {{ $index + 1 }}" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="aspect-[4/3] bg-neutral-100 flex flex-col items-center justify-center text-neutral-400 p-6 text-center">
                    <svg class="w-10 h-10 mb-2 text-neutral-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    <p class="text-xs text-neutral-500">Foto barang belum diunggah</p>
                </div>
            @endif
        </div>

        <!-- Informasi Utama & Biaya -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-4">
            <div>
                <div class="flex items-center gap-2 mb-1.5">
                    @if($item->size)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-neutral-100 text-neutral-800 border border-neutral-200">
                            Ukuran: {{ $item->size }}
                        </span>
                    @endif
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono text-neutral-500 bg-neutral-100">
                        {{ substr($item->ulid, 0, 8) }}
                    </span>
                </div>
                <h1 class="text-lg sm:text-xl font-bold text-neutral-900 leading-snug">{{ $item->name }}</h1>
            </div>

            <!-- Kartu Tarif Sewa -->
            <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-3.5 flex items-baseline justify-between">
                <div>
                    <span class="text-xs text-neutral-500 block">Tarif sewa harian</span>
                    <span class="text-xl sm:text-2xl font-bold text-neutral-900">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</span>
                    <span class="text-xs text-neutral-500">/ hari</span>
                </div>
                <div class="text-right">
                    <span class="inline-block text-xs font-medium badge-status-success border px-2.5 py-1 rounded-full">Tersedia</span>
                </div>
            </div>

            <!-- Grid Deposit & Denda -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-3">
                    <span class="text-xs text-neutral-500 block mb-0.5">Uang jaminan (deposit)</span>
                    <span class="text-sm font-semibold text-neutral-900">
                        {{ $item->deposit_amount > 0 ? 'Rp ' . number_format($item->deposit_amount, 0, ',', '.') : 'Tanpa deposit' }}
                    </span>
                    <p class="text-[11px] text-neutral-500 mt-0.5">Dikembalikan jika tidak ada denda</p>
                </div>
                <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-3">
                    <span class="text-xs text-neutral-500 block mb-0.5">Denda keterlambatan</span>
                    <span class="text-sm font-semibold text-neutral-900">
                        Rp {{ number_format($item->fine_per_day, 0, ',', '.') }}
                    </span>
                    <p class="text-[11px] text-neutral-500 mt-0.5">Dikenakan per 24 jam</p>
                </div>
            </div>
        </div>

        <!-- Kelengkapan Set & Petunjuk Perawatan -->
        @if(!empty($item->included_items) || $item->care_instructions)
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-3.5">
                <h3 class="text-sm font-semibold text-neutral-900">
                    Spesifikasi dan kelengkapan
                </h3>

                @php
                    $includedList = is_array($item->included_items) 
                        ? $item->included_items 
                        : (is_string($item->included_items) && trim($item->included_items) !== '' ? array_map('trim', explode(',', $item->included_items)) : []);
                @endphp

                @if(!empty($includedList))
                    <div>
                        <span class="text-xs text-neutral-500 block mb-2">Komponen yang disertakan:</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($includedList as $included)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-neutral-100 text-neutral-800 border border-neutral-200">
                                    {{ $included }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($item->care_instructions)
                    <div class="badge-status-warning border rounded-xl p-3 text-xs">
                        <span class="font-semibold block text-[#92400e] mb-0.5">Instruksi perawatan:</span>
                        <p class="leading-relaxed">{{ $item->care_instructions }}</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- Ketentuan Jeda Sewa (Buffer) -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-2.5">
            <h3 class="text-sm font-semibold text-neutral-900">
                Ketentuan jeda sewa (buffer)
            </h3>
            <p class="text-xs text-neutral-500 leading-relaxed">
                Jadwal pembersihan dan pengiriman diterapkan otomatis pada kalender:
            </p>
            <div class="grid grid-cols-2 gap-3 pt-0.5">
                <div class="border border-neutral-200 rounded-xl p-3 bg-neutral-50">
                    <span class="text-[11px] text-neutral-500 block">Sebelum sewa</span>
                    <span class="text-sm font-semibold text-neutral-900">{{ $item->buffer_before }} Hari</span>
                    <p class="text-[10px] text-neutral-500 mt-0.5">Persiapan dan laundry</p>
                </div>
                <div class="border border-neutral-200 rounded-xl p-3 bg-neutral-50">
                    <span class="text-[11px] text-neutral-500 block">Sesudah sewa</span>
                    <span class="text-sm font-semibold text-neutral-900">{{ $item->buffer_after }} Hari</span>
                    <p class="text-[10px] text-neutral-500 mt-0.5">Pemeriksaan dan pengiriman</p>
                </div>
            </div>
        </div>

        <!-- Kalender Ketersediaan -->
        <livewire:item-availability-calendar :item="$item" />

        <!-- Formulir Booking Publik -->
        <div id="booking-section">
            <livewire:public.booking-form :item="$item" />
        </div>

        <!-- Deskripsi Barang -->
        @if($item->description)
            <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-2">
                <h3 class="text-sm font-semibold text-neutral-900">Deskripsi</h3>
                <div class="text-xs text-neutral-600 leading-relaxed whitespace-pre-line">
                    {{ $item->description }}
                </div>
            </div>
        @endif

        <!-- Pertanyaan Umum (FAQ Akordeon) -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-3">
            <div class="flex items-center justify-between pb-1 border-b border-neutral-100">
                <h3 class="text-sm font-semibold text-neutral-900">Pertanyaan umum (FAQ)</h3>
                <span class="text-[11px] text-neutral-500">Aturan sewa & keamanan</span>
            </div>

            <div class="divide-y divide-neutral-100">
                <!-- FAQ 1: KTP & Privasi -->
                <details class="group py-3 first:pt-1 last:pb-0">
                    <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-neutral-900 list-none select-none min-h-[44px]">
                        <span>Mengapa diminta foto KTP dan selfie?</span>
                        <svg class="w-4 h-4 text-neutral-400 group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </summary>
                    <div class="pt-2 pb-1 text-xs text-neutral-500 leading-relaxed space-y-1.5">
                        <p>
                            Foto KTP dan selfie hanya digunakan oleh vendor untuk memverifikasi identitas penyewa demi keamanan bersama dan mencegah penipuan.
                        </p>
                        <p>
                            Dokumen Anda disimpan di penyimpanan privat terenkripsi dan <strong>otomatis dihapus permanen dalam 30 hari</strong> setelah transaksi rental selesai, sesuai prinsip UU Perlindungan Data Pribadi (UU PDP).
                        </p>
                    </div>
                </details>

                <!-- FAQ 2: Deposit -->
                <details class="group py-3 first:pt-1 last:pb-0">
                    <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-neutral-900 list-none select-none min-h-[44px]">
                        <span>Bagaimana aturan uang jaminan (deposit)?</span>
                        <svg class="w-4 h-4 text-neutral-400 group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </summary>
                    <div class="pt-2 pb-1 text-xs text-neutral-500 leading-relaxed">
                        <p>
                            Uang jaminan (deposit) dibayarkan langsung ke rekening vendor saat konfirmasi pesanan. Deposit akan dikembalikan penuh setelah barang dikembalikan dan diverifikasi kondisinya oleh vendor tanpa kerusakan atau keterlambatan.
                        </p>
                    </div>
                </details>

                <!-- FAQ 3: Jeda Sewa (Buffer) -->
                <details class="group py-3 first:pt-1 last:pb-0">
                    <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-neutral-900 list-none select-none min-h-[44px]">
                        <span>Apa maksud hari jeda (buffer) di kalender?</span>
                        <svg class="w-4 h-4 text-neutral-400 group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </summary>
                    <div class="pt-2 pb-1 text-xs text-neutral-500 leading-relaxed">
                        <p>
                            Jeda waktu sebelum dan sesudah sewa adalah alokasi steril untuk pembersihan (laundry), pengecekan kelengkapan, dan pengiriman barang. Hari jeda otomatis diblokir di sistem agar barang dijamin siap pakai dan tidak terjadi bentrok jadwal.
                        </p>
                    </div>
                </details>

                <!-- FAQ 4: Pembayaran & Konfirmasi -->
                <details class="group py-3 first:pt-1 last:pb-0">
                    <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-neutral-900 list-none select-none min-h-[44px]">
                        <span>Bagaimana alur pembayaran sewa?</span>
                        <svg class="w-4 h-4 text-neutral-400 group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </summary>
                    <div class="pt-2 pb-1 text-xs text-neutral-500 leading-relaxed">
                        <p>
                            Setelah Anda mengirim formulir booking, vendor akan memeriksa kelengkapan jadwal dan menghubungi Anda langsung melalui WhatsApp resmi untuk konfirmasi pembayaran dan pengiriman barang.
                        </p>
                    </div>
                </details>
            </div>
        </div>

        <!-- Footer Branding (White-Label for Pro Vendors) -->
        @if(!$item->vendor->hasWhiteLabel())
            <footer class="pt-6 pb-12 text-center">
                <p class="text-[11px] text-neutral-400">
                    Didukung oleh <a href="https://jagasewa.web.id" target="_blank" class="font-medium hover:underline">JagaSewa</a> • Platform Penyewaan Aman
                </p>
            </footer>
        @endif

    </main>

    <!-- Bottom Sticky CTA Mobile -->
    <div class="fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-sm border-t border-neutral-200 p-3.5 z-40">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-4">
            <div>
                <span class="text-[11px] text-neutral-500 block">Tarif harian</span>
                <span class="text-base sm:text-lg font-bold text-neutral-900">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</span>
                <span class="text-xs text-neutral-500">/ hari</span>
            </div>
            <a href="#booking-section" 
               class="inline-flex items-center justify-center h-11 px-6 rounded-full bg-[#111111] hover:bg-[#262626] active:bg-black text-white font-semibold text-sm shadow-xs transition duration-150">
                Pilih tanggal & sewa
            </a>
        </div>
    </div>

    <!-- Script Pergantian Foto -->
    <script>
        function switchPhoto(url, index) {
            const mainImg = document.getElementById('main-product-image');
            const indexLabel = document.getElementById('active-photo-index');
            if (mainImg) mainImg.src = url;
            if (indexLabel) indexLabel.innerText = index;

            document.querySelectorAll('.photo-thumb').forEach((thumb, i) => {
                if (i === index - 1) {
                    thumb.classList.add('border-neutral-900');
                    thumb.classList.remove('border-transparent');
                } else {
                    thumb.classList.remove('border-neutral-900');
                    thumb.classList.add('border-transparent');
                }
            });
        }
    </script>
    @livewireScripts
</body>
</html>
