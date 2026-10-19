<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permintaan Sewa Terkirim — {{ $booking->reference_code }} | JagaSewa</title>
    <link rel="icon" type="image/png" href="{{ asset('jagasewa_logo3.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('public.partials.design-tokens')
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased pb-16">

    <!-- Header Navigasi -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-neutral-200">
        <div class="max-w-xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ route('public.vendor.catalog', ['vendor_slug' => $booking->vendor->slug]) }}" class="flex items-center gap-2 text-neutral-900 font-bold text-base tracking-tight hover:text-neutral-700 transition">
                    <img src="{{ asset('jagasewa_logo1.png') }}" alt="JagaSewa" class="h-8 sm:h-9 w-auto object-contain">
                </a>
                <span class="text-[11px] font-medium bg-neutral-100 text-neutral-600 px-2 py-0.5 rounded-full border border-neutral-200">Konfirmasi</span>
            </div>
            <a href="{{ route('public.items.show', ['vendor_slug' => $booking->vendor->slug, 'item_slug' => $booking->item->slug]) }}" class="text-xs text-neutral-500 hover:text-neutral-900 font-medium transition min-h-[44px] inline-flex items-center">
                Kembali ke barang
            </a>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 pt-6 space-y-4">

        <!-- Kartu Status Utama -->
        <div class="bg-white p-5 sm:p-6 rounded-xl border border-neutral-200 shadow-xs text-center space-y-3">
            <div class="w-12 h-12 mx-auto rounded-full badge-status-success border flex items-center justify-center text-[#166534]">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <div>
                <div class="mb-2">
                    <x-ui.badge status="pending" size="sm" />
                </div>
                <h1 class="text-lg font-bold text-neutral-900">Permintaan Sewa Berhasil Dibuat!</h1>
                <p class="text-xs text-neutral-500 max-w-sm mx-auto mt-1 leading-relaxed">
                    Pesanan tercatat dalam sistem. Jadwal telah diblokir sementara menunggu persetujuan vendor.
                </p>
            </div>

            <!-- Kotak Kode Booking -->
            <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-3.5 my-2">
                <span class="text-[11px] text-neutral-500 block mb-0.5">Kode referensi pesanan</span>
                <span class="text-lg font-bold font-mono tracking-wider text-neutral-900 select-all">{{ $booking->reference_code }}</span>
                <p class="text-[11px] text-neutral-500 mt-1">Gunakan kode ini saat berkomunikasi dengan vendor.</p>
            </div>
        </div>

        <!-- Tombol WhatsApp Konfirmasi -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-3">
            <div>
                <h2 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Konfirmasi ke Vendor</h2>
                <p class="text-xs text-neutral-500 leading-relaxed mt-0.5">
                    Kirim pesan konfirmasi langsung ke nomor WhatsApp vendor untuk memverifikasi pesanan Anda.
                </p>
            </div>

            @if(!empty($waUrl))
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                   class="w-full h-11 bg-[#25D366] hover:bg-[#20ba5a] active:bg-[#1da851] text-white font-semibold text-sm rounded-full shadow-xs flex items-center justify-center gap-2 transition duration-150">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span>Hubungi Vendor via WhatsApp</span>
                </a>
            @else
                <div class="bg-neutral-50 border border-neutral-200 p-3 rounded-xl text-xs text-neutral-700">
                    <span class="font-medium block mb-1">Kontak Vendor:</span>
                    <span class="select-all font-mono text-neutral-900">{{ $booking->vendor->wa_number ?? 'Nomor WhatsApp belum tercantum' }}</span>
                </div>
            @endif
        </div>

        <!-- Rincian Pesanan -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-3">
            <h2 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Ringkasan Pesanan</h2>

            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between py-1 border-b border-neutral-100">
                    <span class="text-neutral-500">Barang</span>
                    <span class="font-medium text-neutral-900">{{ $booking->item->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-neutral-100">
                    <span class="text-neutral-500">Vendor</span>
                    <span class="font-medium text-neutral-900">{{ $booking->vendor->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-neutral-100">
                    <span class="text-neutral-500">Penyewa</span>
                    <span class="font-medium text-neutral-900">{{ $booking->renter->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-neutral-100">
                    <span class="text-neutral-500">Periode sewa</span>
                    <span class="font-medium text-neutral-900">
                        {{ $booking->start_date->format('d M Y') }} — {{ $booking->end_date->format('d M Y') }} ({{ $booking->total_days }} hari)
                    </span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-neutral-100">
                    <span class="text-neutral-500">Tarif sewa</span>
                    <span class="font-semibold text-neutral-900">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-neutral-500">Uang jaminan (deposit)</span>
                    <span class="font-semibold text-neutral-900">
                        {{ $booking->deposit_paid > 0 ? 'Rp ' . number_format($booking->deposit_paid, 0, ',', '.') : 'Tanpa deposit' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Langkah Selanjutnya -->
        <div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-2.5">
            <h3 class="text-xs font-semibold text-neutral-900 uppercase tracking-wider">Langkah selanjutnya</h3>
            <ol class="text-xs text-neutral-600 space-y-1.5 list-decimal list-inside leading-relaxed">
                <li>Kirim pesan konfirmasi melalui tombol WhatsApp ke vendor.</li>
                <li>Vendor akan memeriksa jadwal dan verifikasi data dalam 24 jam.</li>
                <li>Setelah disetujui, koordinasikan waktu pengambilan atau pengiriman barang.</li>
                <li>Pesanan otomatis dibatalkan sistem jika tidak dikonfirmasi dalam 24 jam.</li>
            </ol>
        </div>

    </main>

</body>
</html>
