<x-layouts.renter 
    :title="$item->name . ' — ' . $vendor->name . ' | JagaSewa'"
    :vendor="$vendor"
    headerTag="Detail"
    :backUrl="route('public.vendor.catalog', ['vendor_slug' => $vendor->slug])"
    backText="Lihat semua barang"
>
    <!-- Banner Vendor Mini -->
    <x-ui.card class="flex items-center justify-between p-3.5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-surface-subtle border border-border-subtle flex items-center justify-center text-text-main font-bold text-sm flex-shrink-0">
                {{ strtoupper(substr($vendor->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-sm font-semibold text-text-main leading-tight">{{ $vendor->name }}</h2>
                <p class="text-xs text-text-secondary flex items-center gap-1.5 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-text-main"></span>
                    Terverifikasi
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-accent-lime text-accent-lime-text">
                Jaminan Aman
            </span>
            <span class="text-xs bg-surface-subtle text-text-secondary px-2 py-0.5 rounded font-mono text-[11px] border border-border-subtle">@ {{ $vendor->slug }}</span>
        </div>
    </x-ui.card>

    <!-- Galeri Foto Barang -->
    <x-ui.card class="p-0 overflow-hidden">
        @if(!empty($photos))
            <div class="relative aspect-[4/3] bg-surface-subtle">
                <img id="main-product-image" src="{{ $photos[0] }}" alt="{{ $item->name }}" width="600" height="450" fetchpriority="high" class="w-full h-full object-cover">
                @if(count($photos) > 1)
                    <div class="absolute bottom-2.5 right-2.5 bg-text-main/80 backdrop-blur-xs text-white text-[11px] px-2 py-0.5 rounded font-medium">
                        <span id="active-photo-index">1</span> / {{ count($photos) }}
                    </div>
                @endif
            </div>

            @if(count($photos) > 1)
                <div class="p-2.5 flex gap-2 overflow-x-auto border-t border-border-subtle/50">
                    @foreach($photos as $index => $photoUrl)
                        <button type="button" 
                                onclick="switchPhoto('{{ $photoUrl }}', {{ $index + 1 }})" 
                                class="photo-thumb flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 {{ $index === 0 ? 'border-primary' : 'border-transparent' }} focus:outline-none transition min-h-[44px]">
                            <img src="{{ $photoUrl }}" alt="Foto {{ $index + 1 }}" width="56" height="56" loading="lazy" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        @else
            <div class="aspect-[4/3] bg-surface-subtle flex flex-col items-center justify-center text-text-muted p-6 text-center">
                <svg class="w-10 h-10 mb-2 text-border-hover" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
                <p class="text-xs text-text-secondary">Foto barang belum diunggah</p>
            </div>
        @endif
    </x-ui.card>

    <!-- Informasi Utama & Biaya -->
    <x-ui.card class="space-y-4">
        <div>
            <div class="flex items-center gap-2 mb-1.5">
                @if($item->size)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-surface-subtle text-text-main border border-border-subtle">
                        Ukuran: {{ $item->size }}
                    </span>
                @endif
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono text-text-muted bg-surface-subtle border border-border-subtle">
                    {{ substr($item->ulid, 0, 8) }}
                </span>
            </div>
            <h1 class="text-lg sm:text-xl font-bold text-text-main leading-snug">{{ $item->name }}</h1>
        </div>

        <!-- Kartu Tarif Sewa -->
        <div class="bg-surface-subtle border border-border-subtle rounded-lg p-3.5 flex items-baseline justify-between shadow-2xs">
            <div>
                <span class="text-xs text-text-secondary block">Tarif sewa harian</span>
                <span class="text-xl sm:text-2xl font-bold text-text-main tabular-nums">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</span>
                <span class="text-xs text-text-secondary">/ hari</span>
            </div>
            <div class="text-right">
                <span class="inline-block text-xs font-medium badge-status-success border px-2.5 py-1 rounded-full">Tersedia</span>
            </div>
        </div>

        <!-- Grid Deposit & Denda -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-surface-subtle border border-border-subtle rounded-lg p-3">
                <span class="text-xs text-text-secondary block mb-0.5">Uang jaminan (deposit)</span>
                <span class="text-sm font-semibold text-text-main tabular-nums">
                    {{ $item->deposit_amount > 0 ? 'Rp ' . number_format($item->deposit_amount, 0, ',', '.') : 'Tanpa deposit' }}
                </span>
                <p class="text-[11px] text-text-muted mt-0.5">Dikembalikan jika tidak ada denda</p>
            </div>
            <div class="bg-surface-subtle border border-border-subtle rounded-lg p-3">
                <span class="text-xs text-text-secondary block mb-0.5">Denda keterlambatan</span>
                <span class="text-sm font-semibold text-text-main tabular-nums">
                    Rp {{ number_format($item->fine_per_day, 0, ',', '.') }}
                </span>
                <p class="text-[11px] text-text-muted mt-0.5">Dikenakan per 24 jam</p>
            </div>
        </div>
    </x-ui.card>

    <!-- Kelengkapan Set & Petunjuk Perawatan -->
    @if(!empty($item->included_items) || $item->care_instructions)
        <x-ui.card class="space-y-3.5">
            <h3 class="text-sm font-semibold text-text-main">
                Spesifikasi dan kelengkapan
            </h3>

            @php
                $includedList = is_array($item->included_items) 
                    ? $item->included_items 
                    : (is_string($item->included_items) && trim($item->included_items) !== '' ? array_map('trim', explode(',', $item->included_items)) : []);
            @endphp

            @if(!empty($includedList))
                <div>
                    <span class="text-xs text-text-secondary block mb-2">Komponen yang disertakan:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($includedList as $included)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-surface-subtle text-text-main border border-border-subtle">
                                {{ $included }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($item->care_instructions)
                <div class="badge-status-warning border rounded-lg p-3 text-xs">
                    <span class="font-semibold block text-warning mb-0.5">Instruksi perawatan:</span>
                    <p class="leading-relaxed">{{ $item->care_instructions }}</p>
                </div>
            @endif
        </x-ui.card>
    @endif

    <!-- Ketentuan Jeda Sewa (Buffer) -->
    <x-ui.card class="space-y-2.5">
        <h3 class="text-sm font-semibold text-text-main">
            Ketentuan jeda sewa (buffer)
        </h3>
        <p class="text-xs text-text-secondary leading-relaxed">
            Jadwal pembersihan dan pengiriman diterapkan otomatis pada kalender:
        </p>
        <div class="grid grid-cols-2 gap-3 pt-0.5">
            <div class="border border-border-subtle rounded-lg p-3 bg-surface-subtle">
                <span class="text-[11px] text-text-secondary block">Sebelum sewa</span>
                <span class="text-sm font-semibold text-text-main tabular-nums">{{ $item->buffer_before }} Hari</span>
                <p class="text-[10px] text-text-muted mt-0.5">Persiapan dan laundry</p>
            </div>
            <div class="border border-border-subtle rounded-lg p-3 bg-surface-subtle">
                <span class="text-[11px] text-text-secondary block">Sesudah sewa</span>
                <span class="text-sm font-semibold text-text-main tabular-nums">{{ $item->buffer_after }} Hari</span>
                <p class="text-[10px] text-text-muted mt-0.5">Pemeriksaan dan pengiriman</p>
            </div>
        </div>
    </x-ui.card>

    <!-- Kalender Ketersediaan -->
    <livewire:item-availability-calendar :item="$item" />

    <!-- Formulir Booking Publik -->
    <div id="booking-section">
        <livewire:public.booking-form :item="$item" />
    </div>

    <!-- Deskripsi Barang -->
    @if($item->description)
        <x-ui.card class="space-y-2">
            <h3 class="text-sm font-semibold text-text-main">Deskripsi</h3>
            <div class="text-xs text-text-secondary leading-relaxed whitespace-pre-line">
                {{ $item->description }}
            </div>
        </x-ui.card>
    @endif

    <!-- Pertanyaan Umum (FAQ Akordeon) -->
    <x-ui.card class="space-y-3">
        <div class="flex items-center justify-between pb-1 border-b border-border-subtle/50">
            <h3 class="text-sm font-semibold text-text-main">Pertanyaan umum (FAQ)</h3>
            <span class="text-[11px] text-text-secondary">Aturan sewa & keamanan</span>
        </div>

        <div class="divide-y divide-border-subtle/50">
            <!-- FAQ 1: KTP & Privasi -->
            <details class="group py-3 first:pt-1 last:pb-0">
                <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-text-main list-none select-none min-h-[44px]">
                    <span>Mengapa diminta foto KTP dan selfie?</span>
                    <svg class="w-4 h-4 text-text-muted group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>
                <div class="pt-2 pb-1 text-xs text-text-secondary leading-relaxed space-y-1.5">
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
                <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-text-main list-none select-none min-h-[44px]">
                    <span>Bagaimana aturan uang jaminan (deposit)?</span>
                    <svg class="w-4 h-4 text-text-muted group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>
                <div class="pt-2 pb-1 text-xs text-text-secondary leading-relaxed">
                    <p>
                        Uang jaminan (deposit) dibayarkan langsung ke rekening vendor saat konfirmasi pesanan. Deposit akan dikembalikan penuh setelah barang dikembalikan dan diverifikasi kondisinya oleh vendor tanpa kerusakan atau keterlambatan.
                    </p>
                </div>
            </details>

            <!-- FAQ 3: Jeda Sewa (Buffer) -->
            <details class="group py-3 first:pt-1 last:pb-0">
                <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-text-main list-none select-none min-h-[44px]">
                    <span>Apa maksud hari jeda (buffer) di kalender?</span>
                    <svg class="w-4 h-4 text-text-muted group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>
                <div class="pt-2 pb-1 text-xs text-text-secondary leading-relaxed">
                    <p>
                        Jeda waktu sebelum dan sesudah sewa adalah alokasi steril untuk pembersihan (laundry), pengecekan kelengkapan, dan pengiriman barang. Hari jeda otomatis diblokir di sistem agar barang dijamin siap pakai dan tidak terjadi bentrok jadwal.
                    </p>
                </div>
            </details>

            <!-- FAQ 4: Pembayaran & Konfirmasi -->
            <details class="group py-3 first:pt-1 last:pb-0">
                <summary class="flex items-center justify-between cursor-pointer font-medium text-xs sm:text-sm text-text-main list-none select-none min-h-[44px]">
                    <span>Bagaimana alur pembayaran sewa?</span>
                    <svg class="w-4 h-4 text-text-muted group-open:rotate-180 transition-transform duration-200 flex-shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </summary>
                <div class="pt-2 pb-1 text-xs text-text-secondary leading-relaxed">
                    <p>
                        Setelah Anda mengirim formulir booking, vendor akan memeriksa kelengkapan jadwal dan menghubungi Anda langsung melalui WhatsApp resmi untuk konfirmasi pembayaran dan pengiriman barang.
                    </p>
                </div>
            </details>
        </div>
    </x-ui.card>

    <!-- Bottom Sticky CTA Mobile -->
    <div class="fixed bottom-0 inset-x-0 bg-surface/95 backdrop-blur-sm border-t border-border-subtle p-3.5 z-40">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-4">
            <div>
                <span class="text-[11px] text-text-secondary block">Tarif harian</span>
                <span class="text-base sm:text-lg font-bold text-text-main tabular-nums">Rp {{ number_format($item->price_per_day, 0, ',', '.') }}</span>
                <span class="text-xs text-text-secondary">/ hari</span>
            </div>
            <x-ui.button 
                variant="primary" 
                size="md" 
                href="#booking-section" 
                pill
            >
                Pilih tanggal & sewa
            </x-ui.button>
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
                    thumb.classList.add('border-primary');
                    thumb.classList.remove('border-transparent');
                } else {
                    thumb.classList.remove('border-primary');
                    thumb.classList.add('border-transparent');
                }
            });
        }
    </script>
</x-layouts.renter>
