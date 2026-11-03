<x-filament-panels::page>
    <div class="space-y-6">
        @php $data = $this->getViewData(); @endphp

        @if(! $data['isPro'])
            <!-- Teaser Paket Pro untuk Laporan & Analitik (Free Vendor) -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-10 shadow-xs space-y-6 text-center max-w-2xl mx-auto my-6">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 mx-auto">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <h2 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight">
                        Laporan Analitik Bisnis & Ekspor CSV (Fitur Pro)
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-lg mx-auto">
                        Tingkatkan akun toko Anda ke paket Pro untuk mengakses rincian omzet per periode, analisis utilisasi barang (hari menganggur vs tersewa), daftar pelanggan terbaik, serta unduh pembukuan CSV tanpa batas.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 text-left pt-2">
                    <div class="p-4 bg-slate-50/80 border border-slate-200 rounded-xl space-y-1">
                        <span class="text-xs font-bold text-slate-900 block">Analisis Utilisasi</span>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Pantau barang mana yang paling produktif dan barang yang sering menganggur.</p>
                    </div>
                    <div class="p-4 bg-slate-50/80 border border-slate-200 rounded-xl space-y-1">
                        <span class="text-xs font-bold text-slate-900 block">Penyewa Terbaik</span>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Ketahui pelanggan yang paling sering menyewa dan kontribusi omzetnya.</p>
                    </div>
                    <div class="p-4 bg-slate-50/80 border border-slate-200 rounded-xl space-y-1">
                        <span class="text-xs font-bold text-slate-900 block">Ekspor CSV</span>
                        <p class="text-[11px] text-slate-500 leading-relaxed">Unduh laporan keuangan ke format Excel/CSV untuk rekap bulanan.</p>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="{{ route('vendor.billing.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-primary-text font-semibold text-xs sm:text-sm transition shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        Lihat Pilihan Paket Pro &rarr;
                    </a>
                </div>
            </div>
        @else
            <!-- Toolbar Filter & Ekspor -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Preset Buttons -->
                <div class="flex items-center flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="setPreset('this_month')"
                        class="min-h-[38px] px-3.5 text-xs font-semibold rounded-xl border transition {{ $preset === 'this_month' ? 'bg-slate-900 text-primary-text border-slate-900 shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}"
                    >
                        Bulan Ini
                    </button>
                    <button
                        type="button"
                        wire:click="setPreset('last_30_days')"
                        class="min-h-[38px] px-3.5 text-xs font-semibold rounded-xl border transition {{ $preset === 'last_30_days' ? 'bg-slate-900 text-primary-text border-slate-900 shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}"
                    >
                        30 Hari Terakhir
                    </button>
                    <button
                        type="button"
                        wire:click="setPreset('last_7_days')"
                        class="min-h-[38px] px-3.5 text-xs font-semibold rounded-xl border transition {{ $preset === 'last_7_days' ? 'bg-slate-900 text-primary-text border-slate-900 shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}"
                    >
                        7 Hari Terakhir
                    </button>
                    <button
                        type="button"
                        wire:click="setPreset('last_month')"
                        class="min-h-[38px] px-3.5 text-xs font-semibold rounded-xl border transition {{ $preset === 'last_month' ? 'bg-slate-900 text-primary-text border-slate-900 shadow-xs' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300' }}"
                    >
                        Bulan Lalu
                    </button>
                </div>

                <!-- Custom Date Inputs & Export CSV -->
                <div class="flex items-center flex-wrap gap-2.5">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <input
                            type="date"
                            wire:model.live="startDate"
                            class="min-h-[38px] h-9 px-3 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 shadow-2xs"
                        >
                        <span class="text-slate-400">s/d</span>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="min-h-[38px] h-9 px-3 text-xs rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 shadow-2xs"
                        >
                    </div>

                    <a
                        href="{{ $data['exportUrl'] }}"
                        class="inline-flex items-center justify-center min-h-[38px] px-4 text-xs font-semibold rounded-xl bg-slate-900 hover:bg-slate-800 text-primary-text transition gap-1.5 shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-1"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Ekspor CSV</span>
                    </a>
                </div>
            </div>

            <!-- Ringkasan Kartu Metrik Periode -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs space-y-1.5">
                    <span class="text-xs font-semibold text-slate-500">Total Omzet Sewa</span>
                    <p class="text-xl sm:text-2xl font-bold text-slate-900 tabular-nums">
                        {{ \App\Helpers\FormatHelper::rupiah($data['totalRevenue']) }}
                    </p>
                    <p class="text-[11px] text-slate-400">Transaksi valid periode terpilih (Disetujui, Aktif, Selesai)</p>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs space-y-1.5">
                    <span class="text-xs font-semibold text-slate-500">Jumlah Transaksi Disewa</span>
                    <p class="text-xl sm:text-2xl font-bold text-slate-900 tabular-nums">
                        {{ $data['totalBookingsCount'] }} Pesanan
                    </p>
                    <p class="text-[11px] text-slate-400">Booking berstatus disetujui, aktif, dan selesai</p>
                </div>

                <div class="bg-white border border-slate-200/90 rounded-2xl p-5 shadow-xs space-y-1.5">
                    <span class="text-xs font-semibold text-slate-500">Total Hari Tersewa</span>
                    <p class="text-xl sm:text-2xl font-bold text-slate-900 tabular-nums">
                        {{ \App\Helpers\FormatHelper::days($data['totalRentedDays']) }}
                    </p>
                    <p class="text-[11px] text-slate-400">Akumulasi durasi sewa seluruh unit inventaris</p>
                </div>
            </div>

            <!-- Tabel Utilisasi Barang (Rented vs Idle Days) -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">
                        Analisis Utilisasi Barang Rental
                    </h3>
                    <p class="text-xs text-slate-500">
                        Perbandingan hari unit disewa vs menganggur dalam rentang {{ $data['totalPeriodDays'] }} hari
                    </p>
                </div>

                @if($data['itemStats']->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400">
                        Belum ada barang di katalog untuk dianalisis.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                    <th class="py-3 px-3.5 font-semibold">Nama Unit</th>
                                    <th class="py-3 px-3.5 font-semibold">Kategori</th>
                                    <th class="py-3 px-3.5 font-semibold text-center">Hari Disewa</th>
                                    <th class="py-3 px-3.5 font-semibold text-center">Hari Menganggur</th>
                                    <th class="py-3 px-3.5 font-semibold text-center">Tingkat Utilisasi</th>
                                    <th class="py-3 px-3.5 font-semibold text-right">Omzet Unit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($data['itemStats'] as $item)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3 px-3.5 font-semibold text-slate-900">
                                            {{ $item['name'] }}
                                            @if($item['code'])
                                                <span class="block text-[11px] font-mono text-slate-400 font-normal">Kode: {{ $item['code'] }}</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3.5 text-slate-600">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-medium">
                                                {{ $item['category'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 text-center font-bold text-slate-900 tabular-nums">
                                            {{ \App\Helpers\FormatHelper::days($item['rented_days']) }}
                                        </td>
                                        <td class="py-3 px-3.5 text-center text-slate-500 tabular-nums">
                                            {{ \App\Helpers\FormatHelper::days($item['idle_days']) }}
                                        </td>
                                        <td class="py-3 px-3.5 text-center">
                                            <div class="inline-flex items-center gap-2">
                                                <span class="inline-block w-10 text-right font-bold text-slate-900 tabular-nums">
                                                    {{ $item['utilization_rate'] }}%
                                                </span>
                                                <div class="w-16 h-2 rounded-full bg-slate-100 border border-slate-200 overflow-hidden hidden sm:block">
                                                    <div class="h-full bg-blue-600 rounded-full" style="width: {{ $item['utilization_rate'] }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3.5 text-right font-bold text-slate-900 tabular-nums font-mono">
                                            {{ \App\Helpers\FormatHelper::rupiah($item['revenue']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Tabel Pelanggan / Penyewa Terbaik -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">
                        Penyewa Terbaik (Top Renters Periode Ini)
                    </h3>
                    <p class="text-xs text-slate-500">
                        Pelanggan dengan frekuensi dan total transaksi sewa tertinggi
                    </p>
                </div>

                @if($data['topRenters']->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-400">
                        Belum ada data penyewa dalam periode yang dipilih.
                    </div>
                @else
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                    <th class="py-3 px-3.5 font-semibold w-12 text-center">No</th>
                                    <th class="py-3 px-3.5 font-semibold">Nama Penyewa</th>
                                    <th class="py-3 px-3.5 font-semibold">Kontak WhatsApp</th>
                                    <th class="py-3 px-3.5 font-semibold text-center">Frekuensi Sewa</th>
                                    <th class="py-3 px-3.5 font-semibold text-right">Total Omzet</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($data['topRenters'] as $idx => $renter)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="py-3 px-3.5 text-center font-bold text-slate-500 tabular-nums">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-[11px]">
                                                {{ $idx + 1 }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 font-semibold text-slate-900">
                                            {{ $renter['name'] }}
                                        </td>
                                        <td class="py-3 px-3.5 font-mono text-slate-600">
                                            {{ $renter['phone'] }}
                                        </td>
                                        <td class="py-3 px-3.5 text-center font-bold text-slate-900 tabular-nums">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[11px] font-semibold border border-blue-100">
                                                {{ $renter['bookings_count'] }}x sewa
                                            </span>
                                        </td>
                                        <td class="py-3 px-3.5 text-right font-bold text-slate-900 tabular-nums font-mono">
                                            {{ \App\Helpers\FormatHelper::rupiah($renter['total_spent']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>