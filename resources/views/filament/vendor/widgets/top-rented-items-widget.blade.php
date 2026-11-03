<x-filament-widgets::widget>
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">
                        Barang Paling Sering Disewa
                    </h3>
                    <p class="text-xs text-slate-500">
                        Peringkat utilitas inventaris berdasarkan pesanan aktif dan selesai
                    </p>
                </div>
            </div>

            @if($isPro)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Analitik Pro Aktif
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200 self-start sm:self-center">
                    Mode Dasar
                </span>
            @endif
        </div>

        @if($items->isEmpty() || ($topItem && $topItem->bookings_count == 0))
            <div class="py-8 text-center">
                <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-500">Belum ada transaksi sewa selesai untuk menghitung peringkat barang.</p>
            </div>
        @else
            @if($isPro)
                <!-- Tampilan Lengkap Pro: Tabel Peringkat 5 Besar -->
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                <th class="py-3 px-3.5 font-semibold w-12 text-center">No</th>
                                <th class="py-3 px-3.5 font-semibold">Nama Barang</th>
                                <th class="py-3 px-3.5 font-semibold">Kategori</th>
                                <th class="py-3 px-3.5 font-semibold">Kode</th>
                                <th class="py-3 px-3.5 font-semibold text-center">Frekuensi Sewa</th>
                                <th class="py-3 px-3.5 font-semibold text-right">Total Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($items as $index => $item)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="py-3 px-3.5 font-bold text-center text-slate-500 tabular-nums">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full {{ $index === 0 ? 'bg-amber-100 text-amber-800 font-bold' : ($index === 1 ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-600') }} text-[11px]">
                                            {{ $index + 1 }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 font-semibold text-slate-900">
                                        <a href="{{ url('/vendor/items/' . $item->id) }}" class="hover:text-blue-600 hover:underline">
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                    <td class="py-3 px-3.5 text-slate-500">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                            {{ $item->category ?? 'Lainnya' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 font-mono text-slate-500 text-[11px]">
                                        {{ $item->item_code ?: '-' }}
                                    </td>
                                    <td class="py-3 px-3.5 text-center font-bold text-slate-900 tabular-nums">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-semibold border border-blue-100">
                                            {{ $item->bookings_count }}x sewa
                                        </span>
                                    </td>
                                    <td class="py-3 px-3.5 text-right font-bold text-slate-900 tabular-nums font-mono">
                                        Rp {{ number_format((int) ($item->bookings_sum_total_price ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Tampilan Free: Item #1 & Teaser Upgrade Pro -->
                <div class="space-y-3.5">
                    <div class="p-4 bg-slate-50/80 border border-slate-200 rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-900 text-primary-text font-bold text-xs shadow-xs">
                                1
                            </span>
                            <div>
                                <h4 class="text-xs sm:text-sm font-bold text-slate-900">
                                    {{ $topItem->name }}
                                </h4>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Kategori: {{ $topItem->category ?? 'Lainnya' }} &middot; Disewa <strong class="text-slate-800">{{ $topItem->bookings_count }} kali</strong>
                                </p>
                            </div>
                        </div>
                        <span class="text-xs sm:text-sm font-bold text-slate-900 tabular-nums font-mono bg-white px-3 py-1.5 rounded-lg border border-slate-200">
                            Rp {{ number_format((int) ($topItem->bookings_sum_total_price ?? 0), 0, ',', '.') }}
                        </span>
                    </div>

                    <!-- Teaser Fitur Pro Terkunci -->
                    <div class="p-4 rounded-xl border border-blue-100 bg-gradient-to-r from-blue-50/50 to-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-900">
                                <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </span>
                                <span>Detail Analitik & Tren Barang Terpopuler (Fitur Pro)</span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed max-w-xl">
                                Buka peringkat lengkap seluruh barang, kalkulasi durasi sewa terakumulasi, dan analisis utilisasi inventaris untuk menentukan barang yang paling menghasilkan profit.
                            </p>
                        </div>
                        <a href="{{ route('vendor.billing.index') }}" class="min-h-[40px] px-4 py-2 bg-slate-900 hover:bg-slate-800 text-primary-text text-xs font-semibold rounded-lg transition whitespace-nowrap inline-flex items-center justify-center shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                            Lihat Paket Pro &rarr;
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-filament-widgets::widget>