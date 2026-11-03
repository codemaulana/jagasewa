@php
    $vendorId = auth('vendor')->user()?->vendor_id ?? session('vendor_id');
    $bookings = $record->bookings()
        ->where('vendor_id', $vendorId)
        ->with('item')
        ->orderBy('created_at', 'desc')
        ->get();
@endphp

<div class="space-y-3">
    @if($bookings->isEmpty())
        <div class="py-8 text-center text-xs text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
            Belum ada riwayat pesanan sewa dari pelanggan ini di toko Anda.
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <th class="py-3 px-3.5 font-semibold">Kode Booking</th>
                        <th class="py-3 px-3.5 font-semibold">Barang Rental</th>
                        <th class="py-3 px-3.5 font-semibold">Periode Sewa</th>
                        <th class="py-3 px-3.5 font-semibold text-right">Total Biaya</th>
                        <th class="py-3 px-3.5 font-semibold text-center">Status</th>
                        <th class="py-3 px-3.5 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($bookings as $booking)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 px-3.5 font-bold font-mono text-slate-900">
                                {{ $booking->reference_code }}
                            </td>
                            <td class="py-3 px-3.5 text-slate-900 font-medium">
                                {{ $booking->item?->name ?? 'Barang Rental' }}
                            </td>
                            <td class="py-3 px-3.5 text-slate-600 tabular-nums">
                                {{ $booking->start_date ? $booking->start_date->format('d M Y') : '-' }} &ndash; {{ $booking->end_date ? $booking->end_date->format('d M Y') : '-' }}
                                <span class="block text-[11px] text-slate-400">({{ $booking->total_days }} hari)</span>
                            </td>
                            <td class="py-3 px-3.5 text-right font-mono font-bold text-slate-900 tabular-nums">
                                Rp {{ number_format((int) $booking->total_price, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-3.5 text-center">
                                @php
                                    $st = $booking->status;
                                    $badgeClass = match($st) {
                                        'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
                                        'approved' => 'bg-blue-50 text-blue-800 border-blue-200',
                                        'active' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                        'returned' => 'bg-slate-100 text-slate-700 border-slate-200',
                                        'closed' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                        'rejected', 'cancelled_timeout', 'cancelled_renter', 'cancelled_vendor' => 'bg-rose-50 text-rose-800 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
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
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeClass }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="py-3 px-3.5 text-right">
                                <a href="{{ url('/vendor/bookings/' . $booking->id) }}" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline">
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