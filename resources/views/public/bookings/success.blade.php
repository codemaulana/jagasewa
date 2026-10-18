<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Berhasil — {{ $booking->reference_code }} | JagaSewa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 antialiased pb-16">

    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-100">
        <div class="max-w-xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-emerald-600 font-bold text-lg tracking-tight">🏷️ JagaSewa</span>
                <span class="text-xs bg-emerald-50 text-emerald-700 font-medium px-2 py-0.5 rounded-full border border-emerald-200">Konfirmasi Booking</span>
            </div>
            <a href="{{ route('public.items.show', ['vendor_slug' => $booking->vendor->slug, 'item_slug' => $booking->item->slug]) }}" class="text-xs text-gray-500 hover:text-gray-800">
                ← Kembali ke Barang
            </a>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 pt-6 space-y-5">

        <!-- Success Banner Card -->
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center text-3xl shadow-sm">
                🎉
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 mb-2">
                    ⏳ Menunggu Persetujuan Vendor
                </span>
                <h1 class="text-xl font-bold text-gray-900">Permintaan Sewa Berhasil Dibuat!</h1>
                <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 leading-relaxed">
                    Pesanan Anda telah tercatat di sistem JagaSewa dan jadwal di kalender telah diamankan untuk Anda.
                </p>
            </div>

            <!-- Booking Reference Code Box -->
            <div class="bg-gray-50 border border-gray-200/80 rounded-2xl p-4 my-2">
                <span class="text-[11px] text-gray-400 block font-medium mb-1">Nomor Referensi Booking:</span>
                <span class="text-lg font-black tracking-wider text-emerald-700 select-all">{{ $booking->reference_code }}</span>
                <p class="text-[10px] text-gray-400 mt-1">Simpan nomor ini untuk verifikasi atau komunikasi dengan vendor.</p>
            </div>
        </div>

        <!-- WhatsApp CTA Button Card -->
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-3">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Konfirmasi Cepat via WhatsApp</h2>
            <p class="text-xs text-gray-600 leading-relaxed">
                Kirim pesan langsung ke WhatsApp vendor untuk mempercepat proses persetujuan dan konfirmasi jadwal sewa Anda.
            </p>

            @if(!empty($waUrl))
                <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer"
                   class="w-full bg-[#25D366] hover:bg-[#20ba5a] active:bg-[#1da851] text-white font-bold py-3.5 px-4 rounded-2xl shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2.5 transition text-sm">
                    <svg class="w-5 h-5 fill-current" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                    </svg>
                    <span>Hubungi Vendor via WhatsApp</span>
                </a>
            @else
                <div class="bg-gray-50 border border-gray-200 p-3.5 rounded-2xl text-xs text-gray-700">
                    <span class="font-semibold block mb-1">Kontak Vendor:</span>
                    <span class="select-all font-mono text-sm text-gray-800">{{ $booking->vendor->wa_number ?? 'Nomor WhatsApp belum tercantum' }}</span>
                </div>
            @endif
        </div>

        <!-- Detail Pesanan Card -->
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-3.5">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Ringkasan Pesanan</h2>

            <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500">Barang:</span>
                    <span class="font-bold text-gray-900">{{ $booking->item->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500">Vendor:</span>
                    <span class="font-semibold text-gray-900">{{ $booking->vendor->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500">Penyewa:</span>
                    <span class="font-semibold text-gray-900">{{ $booking->renter->name }}</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500">Periode Sewa:</span>
                    <span class="font-bold text-gray-900">
                        {{ $booking->start_date->format('d M Y') }} — {{ $booking->end_date->format('d M Y') }} ({{ $booking->total_days }} Hari)
                    </span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-500">Total Tarif Sewa:</span>
                    <span class="font-bold text-emerald-700">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-gray-500">Jaminan Deposit:</span>
                    <span class="font-bold text-gray-900">
                        {{ $booking->deposit_paid > 0 ? 'Rp ' . number_format($booking->deposit_paid, 0, ',', '.') : 'Bebas Deposit' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-3">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Langkah Selanjutnya</h3>
            <ol class="text-xs text-gray-600 space-y-2 list-decimal list-inside leading-relaxed">
                <li>Klik tombol WhatsApp di atas untuk langsung mengirim konfirmasi permohonan ke vendor.</li>
                <li>Vendor akan memeriksa jadwal dan memverifikasi data Anda dalam kurun waktu <strong>24 jam</strong>.</li>
                <li>Setelah pesanan disetujui, koordinasikan jadwal penyerahan atau pengiriman barang dengan vendor.</li>
                <li>Jika dalam 24 jam belum ada konfirmasi, pesanan otomatis dibatalkan sistem.</li>
            </ol>
        </div>

    </main>

</body>
</html>
