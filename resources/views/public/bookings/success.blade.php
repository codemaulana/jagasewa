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
                <li>Vendor akan memeriksa jadwal dan verifikasi data Anda dalam kurun waktu <strong>24 jam</strong>.</li>
                <li>Setelah pesanan disetujui, vendor akan menghubungi Anda melalui WhatsApp untuk instruksi serah-terima/pengiriman barang.</li>
                <li>Jika dalam 24 jam belum ada konfirmasi, pesanan otomatis dibatalkan sistem.</li>
            </ol>
        </div>

    </main>

</body>
</html>
