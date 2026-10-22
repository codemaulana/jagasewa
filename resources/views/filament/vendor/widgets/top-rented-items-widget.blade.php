<x-filament-widgets::widget>
    <div class="bg-surface border border-border-subtle rounded-xl p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-border-subtle pb-3">
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-surface-subtle text-text-main border border-border-subtle">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-text-main">
                        Barang Paling Sering Disewa
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Peringkat utilitas inventaris berdasarkan pesanan aktif dan selesai
                    </p>
                </div>
            </div>

            @if($isPro)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-success-bg text-success border border-success-border">
                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                    Analitik Pro Aktif
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-subtle text-text-secondary border border-border-subtle">
                    Mode Dasar
                </span>
            @endif
        </div>

        @if($items->isEmpty() || ($topItem && $topItem->bookings_count == 0))
            <div class="py-6 text-center text-xs text-text-muted">
                Belum ada transaksi sewa selesai untuk menghitung peringkat barang.
            </div>
        @else
            @if($isPro)
                <!-- Tampilan Lengkap Pro: Tabel Peringkat 5 Besar -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-surface-subtle text-text-secondary border-b border-border-subtle">
                                <th class="p-2.5 font-semibold w-12 text-center">No</th>
                                <th class="p-2.5 font-semibold">Nama Barang</th>
                                <th class="p-2.5 font-semibold">Kategori</th>
                                <th class="p-2.5 font-semibold">Kode</th>
                                <th class="p-2.5 font-semibold text-center">Frekuensi Sewa</th>
                                <th class="p-2.5 font-semibold text-right">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-subtle">
                            @foreach($items as $index => $item)
                                <tr class="hover:bg-surface-subtle/50 transition">
                                    <td class="p-2.5 font-bold text-center text-text-secondary tabular-nums">
                                        #{{ $index + 1 }}
                                    </td>
                                    <td class="p-2.5 font-semibold text-text-main">
                                        <a href="{{ url('/vendor/items/' . $item->id) }}" class="hover:underline">
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                    <td class="p-2.5 text-text-secondary">
                                        {{ $item->category ?? 'Lainnya' }}
                                    </td>
                                    <td class="p-2.5 font-mono text-text-secondary">
                                        {{ $item->item_code ?: '-' }}
                                    </td>
                                    <td class="p-2.5 text-center font-bold text-text-main tabular-nums">
                                        {{ $item->bookings_count }}x sewa
                                    </td>
                                    <td class="p-2.5 text-right font-bold text-text-main tabular-nums font-mono">
                                        Rp {{ number_format((int) ($item->bookings_sum_total_price ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Tampilan Free: Item #1 & Teaser Upgrade Pro -->
                <div class="space-y-4">
                    <div class="p-3.5 bg-surface-subtle border border-border-subtle rounded-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-text font-bold text-xs">
                                1
                            </span>
                            <div>
                                <h4 class="text-xs font-bold text-text-main">
                                    {{ $topItem->name }}
                                </h4>
                                <p class="text-[11px] text-text-secondary">
                                    Kategori: {{ $topItem->category ?? 'Lainnya' }} &middot; Disewa {{ $topItem->bookings_count }} kali
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-text-main tabular-nums font-mono">
                            Rp {{ number_format((int) ($topItem->bookings_sum_total_price ?? 0), 0, ',', '.') }}
                        </span>
                    </div>

                    <!-- Teaser Fitur Pro Terkunci -->
                    <div class="p-4 rounded-xl border border-border-subtle bg-surface flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-text-main">
                                <svg class="w-4 h-4 text-warning flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Detail Analitik & Tren Barang Paling Disewa (Fitur Pro)</span>
                            </div>
                            <p class="text-[11px] text-text-secondary leading-relaxed">
                                Buka peringkat lengkap seluruh barang, kalkulasi durasi sewa terakumulasi, dan analisis utilisasi inventaris untuk menentukan barang yang paling menghasilkan profit.
                            </p>
                        </div>
                        <a href="{{ route('vendor.billing.index') }}" class="min-h-[44px] px-4 py-2 bg-primary hover:opacity-90 text-primary-text text-xs font-semibold rounded-lg transition whitespace-nowrap inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                            Lihat Paket Pro
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-widgets::widget>
