@php
    $events = [];

    // 1. Dibuat
    $events[] = [
        'title' => 'Permintaan Booking Dibuat',
        'time' => $record->created_at ? $record->created_at->format('d M Y, H:i') . ' WIB' : null,
        'desc' => 'Penyewa mengajukan sewa untuk ' . ($record->item?->name ?? 'barang') . ' (' . $record->total_days . ' hari).',
        'status' => 'completed',
    ];

    // 2. Dikonfirmasi / Ditolak / Batal
    if ($record->approved_at) {
        $events[] = [
            'title' => 'Booking Disetujui Vendor',
            'time' => $record->approved_at->format('d M Y, H:i') . ' WIB',
            'desc' => 'Permohonan sewa disetujui. Menunggu konfirmasi pembayaran DP atau serah-terima.',
            'status' => 'completed',
        ];
    } elseif ($record->status === 'rejected') {
        $events[] = [
            'title' => 'Booking Ditolak Vendor',
            'time' => $record->updated_at ? $record->updated_at->format('d M Y, H:i') . ' WIB' : null,
            'desc' => $record->vendor_notes ?: 'Permohonan booking ditolak oleh vendor.',
            'status' => 'cancelled',
        ];
    } elseif (str_starts_with($record->status, 'cancelled')) {
        $events[] = [
            'title' => 'Booking Dibatalkan',
            'time' => $record->updated_at ? $record->updated_at->format('d M Y, H:i') . ' WIB' : null,
            'desc' => match($record->status) {
                'cancelled_timeout' => 'Dibatalkan otomatis karena melewati batas waktu 24 jam.',
                'cancelled_renter' => 'Dibatalkan oleh penyewa.',
                default => 'Dibatalkan oleh vendor.',
            },
            'status' => 'cancelled',
        ];
    }

    // 3. Pembayaran
    if ($record->is_paid_in_full) {
        $events[] = [
            'title' => 'Pembayaran Sewa Lunas',
            'time' => $record->paid_in_full_at ? $record->paid_in_full_at->format('d M Y, H:i') . ' WIB' : null,
            'desc' => 'Penyewa telah melunasi total biaya sewa & deposit (Rp ' . number_format($record->total_price + $record->deposit_paid, 0, ',', '.') . ').',
            'status' => 'completed',
        ];
    } elseif ($record->is_dp_paid) {
        $events[] = [
            'title' => 'Uang Muka (DP) Diterima',
            'time' => $record->dp_paid_at ? $record->dp_paid_at->format('d M Y, H:i') . ' WIB' : null,
            'desc' => 'Uang muka sebesar Rp ' . number_format($record->down_payment_amount, 0, ',', '.') . ' telah dicatat oleh vendor.',
            'status' => 'completed',
        ];
    }

    // 4. Serah Terima
    if ($record->activated_at) {
        $methodLabel = match($record->handover_method) {
            'pickup' => 'Ambil Sendiri di Toko',
            'instant_courier' => 'Kurir Instan (GoSend/Grab)',
            'delivery_courier' => 'Ekspedisi (Paxel/JNE/J&T)',
            default => 'Serah Terima Langsung',
        };
        $trackingSuffix = $record->tracking_number ? " (Resi: {$record->tracking_number})" : '';
        $events[] = [
            'title' => 'Barang Diserahkan & Aktif Disewa',
            'time' => $record->activated_at->format('d M Y, H:i') . ' WIB',
            'desc' => "Metode: {$methodLabel}{$trackingSuffix}. Checklist fisik dan kelengkapan telah diverifikasi.",
            'status' => 'completed',
        ];
    }

    // 5. Pengembalian
    if ($record->returned_at) {
        $events[] = [
            'title' => 'Barang Diterima Kembali',
            'time' => $record->returned_at->format('d M Y, H:i') . ' WIB',
            'desc' => 'Barang telah dikembalikan oleh penyewa dan siap untuk pemeriksaan denda/settlement.',
            'status' => 'completed',
        ];
    }

    // 6. Selesai (Closed)
    if ($record->closed_at) {
        $events[] = [
            'title' => 'Penyewaan Selesai (Closed)',
            'time' => $record->closed_at->format('d M Y, H:i') . ' WIB',
            'desc' => 'Settlement selesai diproses. Uang jaminan disesuaikan dan pesanan ditutup.',
            'status' => 'completed',
        ];
    }
@endphp

<div class="relative pl-7 space-y-6 before:absolute before:left-3 before:top-2.5 before:bottom-2.5 before:w-0.5 before:bg-slate-200">
    @foreach($events as $event)
        <div class="relative group">
            <!-- Bullet Indicator -->
            <div class="absolute -left-7 top-0.5 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-transform group-hover:scale-105
                {{ $event['status'] === 'completed' ? 'bg-emerald-600 text-primary-text border-white shadow-xs' : ($event['status'] === 'cancelled' ? 'bg-rose-600 text-primary-text border-white shadow-xs' : 'bg-slate-200 text-slate-600 border-white') }}
            ">
                @if($event['status'] === 'completed')
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                @elseif($event['status'] === 'cancelled')
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                @else
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                @endif
            </div>

            <div class="bg-white border border-slate-200/90 rounded-xl p-3.5 sm:p-4 shadow-xs space-y-1">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <h4 class="text-xs sm:text-sm font-bold text-slate-900">
                        {{ $event['title'] }}
                    </h4>
                    @if($event['time'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[11px] font-medium font-mono tabular-nums">
                            {{ $event['time'] }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">
                    {{ $event['desc'] }}
                </p>
            </div>
        </div>
    @endforeach
</div>