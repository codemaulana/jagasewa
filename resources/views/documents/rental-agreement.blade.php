<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Perjanjian Sewa - {{ $booking->reference_code }} - {{ $vendor->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #FFFFFF !important; color: #111111 !important; margin: 0; padding: 0; }
            .print-container { border: none !important; box-shadow: none !important; max-width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-bg-base text-text-main antialiased font-sans p-4 sm:p-8 min-h-screen">
    <div class="no-print max-w-3xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 p-4 bg-surface border border-border-subtle rounded-xl shadow-xs">
        <div class="flex items-center gap-2">
            <a href="{{ url('/vendor/bookings/' . $booking->id) }}" class="inline-flex items-center justify-center min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border border-border-subtle bg-surface hover:bg-surface-subtle transition">
                Kembali ke Booking
            </a>
            <span class="text-xs text-text-secondary">Surat Perjanjian Resmi Paket Pro</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" type="button" class="inline-flex items-center justify-center min-h-[44px] px-4 text-xs font-semibold rounded-lg border border-border-subtle bg-surface hover:bg-surface-subtle text-text-main transition shadow-xs">
                Cetak / Simpan PDF
            </button>
            <a href="?download=pdf" class="inline-flex items-center justify-center min-h-[44px] px-4 text-xs font-semibold rounded-lg bg-primary hover:bg-neutral-800 text-white transition shadow-xs">
                Unduh File PDF
            </a>
        </div>
    </div>

    <!-- Lembar Surat Perjanjian -->
    <div class="print-container max-w-3xl mx-auto bg-surface border border-border-subtle rounded-xl p-8 sm:p-12 shadow-sm space-y-6 text-xs text-text-main leading-relaxed">
        <div class="text-center border-b border-border-subtle pb-6 space-y-1">
            <h1 class="text-base sm:text-lg font-bold uppercase tracking-tight text-text-main">
                Surat Perjanjian Sewa Menyewa Barang
            </h1>
            <p class="font-mono text-xs text-text-secondary">
                Nomor Kontrak: {{ $booking->reference_code }}
            </p>
        </div>

        <p>Pada hari ini, disepakati perjanjian sewa-menyewa antara pihak-pihak di bawah ini:</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-surface-subtle border border-border-subtle rounded-lg p-4">
            <div class="space-y-1">
                <span class="font-bold uppercase tracking-wider text-[10px] text-text-secondary">PIHAK PERTAMA (VENDOR / PEMILIK):</span>
                <p class="font-bold text-text-main">{{ $vendor->name }}</p>
                <p class="text-text-secondary">WhatsApp: {{ $vendor->wa_number ?? '-' }}</p>
                @if($vendor->address)
                    <p class="text-text-muted">{{ $vendor->address }}</p>
                @endif
            </div>
            <div class="space-y-1">
                <span class="font-bold uppercase tracking-wider text-[10px] text-text-secondary">PIHAK KEDUA (PENYEWA):</span>
                <p class="font-bold text-text-main">{{ $renter?->name ?? 'Penyewa' }}</p>
                <p class="text-text-secondary">WhatsApp: {{ $renter?->phone ?? '-' }}</p>
                @if($renter?->address)
                    <p class="text-text-muted">{{ $renter->address }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-2">
            <h2 class="font-bold text-xs uppercase tracking-wider text-text-secondary border-b border-border-subtle pb-1">
                PASAL 1: OBJEK SEWA & JANGKA WAKTU
            </h2>
            <p>1. Pihak Pertama menyewakan kepada Pihak Kedua sebuah unit: <strong>{{ $item?->name ?? 'Unit Rental' }}</strong> (Kode: {{ $item?->item_code ?: '-' }}).</p>
            <p>2. Kondisi fisik unit saat penyerahan: <strong>{{ $item?->condition ?? 'Sangat Baik' }}</strong>.</p>
            <p>3. Jangka waktu sewa berlangsung mulai tanggal <strong>{{ $booking->start_date ? $booking->start_date->format('d M Y') : '-' }}</strong> sampai dengan <strong>{{ $booking->end_date ? $booking->end_date->format('d M Y') : '-' }}</strong> (Total: {{ $booking->total_days }} Hari).</p>
        </div>

        <div class="space-y-2">
            <h2 class="font-bold text-xs uppercase tracking-wider text-text-secondary border-b border-border-subtle pb-1">
                PASAL 2: BIAYA SEWA, UANG JAMINAN & KETERLAMBATAN
            </h2>
            <p>1. Total biaya sewa disepakati sebesar <strong>Rp {{ number_format((int) $booking->total_price, 0, ',', '.') }}</strong>.</p>
            <p>2. Pihak Kedua menitipkan uang jaminan (deposit) sebesar <strong>Rp {{ number_format((int) ($booking->deposit_paid ?? $item?->deposit_amount ?? 0), 0, ',', '.') }}</strong> yang akan diselesaikan setelah barang diterima kembali.</p>
            <p>3. Keterlambatan pengembalian unit dikenakan denda sebesar <strong>Rp {{ number_format((int) ($item?->fine_per_day ?? 0), 0, ',', '.') }}</strong> per hari keterlambatan.</p>
        </div>

        <div class="space-y-2">
            <h2 class="font-bold text-xs uppercase tracking-wider text-text-secondary border-b border-border-subtle pb-1">
                PASAL 3: PERAWATAN & KERUSAKAN
            </h2>
            <p>1. Pihak Kedua berkewajiban menjaga keutuhan, kebersihan, dan keselamatan unit sewa serta dilarang menyewakan kembali unit kepada pihak ketiga tanpa izin tertulis.</p>
            <p>2. Apabila unit mengalami kerusakan, noda permanen, atau kehilangan kelengkapan aksesoris selama masa sewa, Pihak Kedua bersedia menanggung biaya perbaikan atau penggantian yang dipotong langsung dari uang jaminan atau ditagihkan kekurangannya.</p>
        </div>

        <div class="pt-8 border-t border-border-subtle grid grid-cols-2 text-center gap-8">
            <div class="space-y-16">
                <p class="font-semibold text-text-secondary">Pihak Pertama (Vendor)</p>
                <div>
                    <p class="font-bold text-text-main underline">( {{ $vendor->name }} )</p>
                    <p class="text-[11px] text-text-muted mt-0.5">Pemilik Rental</p>
                </div>
            </div>
            <div class="space-y-16">
                <p class="font-semibold text-text-secondary">Pihak Kedua (Penyewa)</p>
                <div>
                    <p class="font-bold text-text-main underline">( {{ $renter?->name ?? 'Penyewa' }} )</p>
                    <p class="text-[11px] text-text-muted mt-0.5">Penyewa</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
