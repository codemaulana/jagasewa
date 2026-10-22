<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $booking->reference_code }} - {{ $vendor->name }}</title>
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
    <!-- Toolbar Aksi Dokumen (Disembunyikan saat dicetak) -->
    <div class="no-print max-w-3xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 p-4 bg-surface border border-border-subtle rounded-xl shadow-xs">
        <div class="flex items-center gap-2">
            <a href="{{ url('/vendor/bookings/' . $booking->id) }}" class="inline-flex items-center justify-center min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border border-border-subtle bg-surface hover:bg-surface-subtle transition">
                Kembali ke Booking
            </a>
            <span class="text-xs text-text-secondary">Dokumen Resmi Paket Pro</span>
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

    <!-- Kontainer Lembar Dokumen A4 -->
    <div class="print-container max-w-3xl mx-auto bg-surface border border-border-subtle rounded-xl p-8 sm:p-12 shadow-sm space-y-8">
        <!-- Header Dokumen -->
        <div class="flex items-start justify-between border-b border-border-subtle pb-6 gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight uppercase">
                    {{ $vendor->name }}
                </h1>
                <p class="text-xs text-text-secondary mt-1">
                    Layanan Rental Profesional &middot; Bukti Pembayaran Resmi
                </p>
                @if($vendor->address)
                    <p class="text-xs text-text-muted mt-0.5">{{ $vendor->address }}</p>
                @endif
                @if($vendor->wa_number)
                    <p class="text-xs text-text-muted">WhatsApp: {{ $vendor->wa_number }}</p>
                @endif
            </div>
            <div class="text-right">
                <span class="inline-block text-xs font-mono font-bold px-2.5 py-1 rounded border border-border-subtle bg-surface-subtle text-text-main">
                    INVOICE
                </span>
                <p class="text-xs font-mono font-semibold text-text-main mt-2">
                    Ref: {{ $booking->reference_code }}
                </p>
                <p class="text-[11px] text-text-muted mt-0.5">
                    Tanggal: {{ $booking->created_at ? $booking->created_at->format('d/m/Y') : '-' }}
                </p>
            </div>
        </div>

        <!-- Rincian Penyewa & Pesanan -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
            <div class="space-y-1">
                <span class="font-bold text-text-secondary uppercase tracking-wider text-[10px]">Ditagihkan Kepada:</span>
                <p class="text-sm font-bold text-text-main">{{ $renter?->name ?? 'Penyewa' }}</p>
                <p class="text-text-secondary">WhatsApp: {{ $renter?->phone ?? '-' }}</p>
                @if($renter?->address)
                    <p class="text-text-muted">{{ $renter->address }}</p>
                @endif
            </div>
            <div class="space-y-1 sm:text-right">
                <span class="font-bold text-text-secondary uppercase tracking-wider text-[10px]">Informasi Jadwal Sewa:</span>
                <p class="text-text-main font-semibold">
                    {{ $booking->start_date ? $booking->start_date->format('d M Y') : '-' }} s/d {{ $booking->end_date ? $booking->end_date->format('d M Y') : '-' }}
                </p>
                <p class="text-text-secondary">Durasi: {{ $booking->total_days }} Hari</p>
                <p class="text-text-muted">Metode: {{ $booking->handover_method === 'pickup' ? 'Ambil di Toko' : ($booking->handover_method === 'instant_courier' ? 'Kurir Instan' : 'Ekspedisi') }}</p>
            </div>
        </div>

        <!-- Tabel Rincian Biaya -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-border-subtle bg-surface-subtle text-text-secondary font-semibold">
                        <th class="p-3">Deskripsi Barang</th>
                        <th class="p-3 text-center">Durasi</th>
                        <th class="p-3 text-right">Tarif</th>
                        <th class="p-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    <tr>
                        <td class="p-3 text-text-main font-medium">
                            {{ $item?->name ?? 'Unit Rental' }}
                            @if($item?->item_code)
                                <span class="block text-[11px] text-text-muted font-mono">Kode: {{ $item->item_code }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-center text-text-secondary tabular-nums">
                            {{ $booking->total_days }} hari
                        </td>
                        <td class="p-3 text-right text-text-secondary tabular-nums font-mono">
                            Rp {{ number_format((int) ($booking->item?->price_per_day ?? 0), 0, ',', '.') }}/hari
                        </td>
                        <td class="p-3 text-right font-bold text-text-main tabular-nums font-mono">
                            Rp {{ number_format((int) $booking->total_price, 0, ',', '.') }}
                        </td>
                    </tr>
                    @if(($booking->deposit_paid ?? $item?->deposit_amount) > 0)
                        <tr>
                            <td colspan="3" class="p-3 text-text-secondary">
                                Uang Jaminan Sewa (Deposit Dikembalikan Pasca Verifikasi)
                            </td>
                            <td class="p-3 text-right font-bold text-text-main tabular-nums font-mono">
                                Rp {{ number_format((int) ($booking->deposit_paid ?? $item?->deposit_amount ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-border-subtle">
                        <td colspan="3" class="p-3 text-right font-bold text-text-main text-sm">
                            Total Tagihan
                        </td>
                        <td class="p-3 text-right font-bold text-text-main text-sm tabular-nums font-mono">
                            Rp {{ number_format((int) ($booking->total_price + ($booking->deposit_paid ?? $item?->deposit_amount ?? 0)), 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="p-2 text-right text-xs text-text-secondary">
                            Status Pelunasan:
                        </td>
                        <td class="p-2 text-right font-bold text-xs">
                            @if($booking->is_paid_in_full)
                                <span class="text-success font-semibold">LUNAS</span>
                            @elseif($booking->is_dp_paid)
                                <span class="text-info font-semibold">DP Rp {{ number_format((int) $booking->down_payment_amount, 0, ',', '.') }}</span>
                            @else
                                <span class="text-warning font-semibold">MENUNGGU PEMBAYARAN</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Ketentuan & Catatan -->
        <div class="border-t border-border-subtle pt-6 text-[11px] text-text-muted space-y-1.5">
            <span class="font-bold text-text-secondary text-xs uppercase tracking-wider">Ketentuan Transaksi:</span>
            <p>1. Uang jaminan sewa (deposit) akan disesuaikan atau dikembalikan penuh maksimal 1x24 jam setelah unit rental diterima kembali dalam keadaan baik dan lengkap.</p>
            <p>2. Keterlambatan pengembalian dikenakan denda sesuai tarif harian yang telah disepakati.</p>
            <p>3. Dokumen ini sah dan diterbitkan secara digital oleh platform JagaSewa.</p>
        </div>
    </div>
</body>
</html>
