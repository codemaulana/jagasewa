@php
    $bookings = $record->bookings()->with('renter')->orderBy('start_date', 'desc')->take(20)->get();
@endphp

<div class="space-y-3">
    @if($bookings->isEmpty())
        <div class="py-6 text-center text-xs text-text-muted">
            Belum ada riwayat pesanan sewa untuk barang ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-surface-subtle text-text-secondary border-b border-border-subtle">
                        <th class="p-2.5 font-semibold">Kode Booking</th>
                        <th class="p-2.5 font-semibold">Penyewa</th>
                        <th class="p-2.5 font-semibold">Periode Sewa</th>
                        <th class="p-2.5 font-semibold">Total Biaya</th>
                        <th class="p-2.5 font-semibold">Status</th>
                        <th class="p-2.5 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle">
                    @foreach($bookings as $booking)
                        <tr class="hover:bg-surface-subtle/50 transition">
                            <td class="p-2.5 font-bold font-mono text-text-main">
                                {{ $booking->reference_code }}
                            </td>
                            <td class="p-2.5 text-text-main">
                                <span class="font-semibold">{{ $booking->renter?->name ?? 'Penyewa' }}</span>
                                @if($booking->renter?->phone)
                                    <span class="block text-[11px] text-text-secondary">WA: {{ $booking->renter->phone }}</span>
                                @endif
                            </td>
                            <td class="p-2.5 text-text-secondary tabular-nums">
                                {{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }} &ndash; {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}
                                <span class="block text-[11px] text-text-muted">({{ $booking->total_days }} hari)</span>
                            </td>
                            <td class="p-2.5 font-mono font-bold text-text-main tabular-nums">
                                Rp {{ number_format((int) $booking->total_price, 0, ',', '.') }}
                            </td>
                            <td class="p-2.5">
                                @php
                                    $st = $booking->status;
                                    $badgeColor = match($st) {
                                        'pending' => 'bg-warning-bg text-warning border-warning-border',
                                        'approved' => 'bg-info-bg text-info border-info-border',
                                        'active' => 'bg-success-bg text-success border-success-border',
                                        'returned' => 'bg-neutral-status-bg text-text-secondary border-neutral-status-border',
                                        'closed' => 'bg-success-bg text-success border-success-border',
                                        'rejected', 'cancelled_timeout', 'cancelled_renter', 'cancelled_vendor' => 'bg-danger-bg text-danger border-danger-border',
                                        default => 'bg-surface-subtle text-text-secondary border-border-subtle',
                                    };
                                    $label = match($st) {
                                        'pending' => 'Menunggu',
                                        'approved' => 'Disetujui',
                                        'active' => 'Sedang Disewa',
                                        'returned' => 'Dikembalikan',
                                        'closed' => 'Selesai',
                                        'rejected' => 'Ditolak',
                                        'cancelled_timeout' => 'Timeout',
                                        'cancelled_renter' => 'Batal (Penyewa)',
                                        'cancelled_vendor' => 'Batal (Vendor)',
                                        default => ucfirst($st),
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $badgeColor }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="p-2.5 text-right">
                                <a href="{{ url('/vendor/bookings/' . $booking->id) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-text-main hover:underline">
                                    <span>Detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
