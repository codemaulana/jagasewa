<x-filament-panels::page>
    <div class="space-y-6">
        @php $data = $this->getViewData(); @endphp

        @if(! $data['isPro'])
            <!-- Teaser Paket Pro untuk Laporan & Analitik (Free Vendor) -->
            <div class="bg-surface border border-border-subtle rounded-xl p-6 sm:p-8 shadow-xs space-y-6 text-center max-w-2xl mx-auto my-4">
                <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-surface-subtle border border-border-subtle text-text-main mx-auto">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <h2 class="text-base sm:text-lg font-bold text-text-main tracking-tight">
                        Laporan Analitik Bisnis & Ekspor CSV (Fitur Pro)
                    </h2>
                    <p class="text-xs text-text-secondary leading-relaxed max-w-lg mx-auto">
                        Tingkatkan akun toko Anda ke paket Pro untuk mengakses rincian pendapatan sewa per periode, analisis utilisasi barang menganggur vs tersewa, daftar penyewa terbaik, dan unduh rekap pembukuan CSV.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-left pt-2">
                    <div class="p-3 bg-surface-subtle border border-border-subtle rounded-lg text-xs space-y-1">
                        <span class="font-bold text-text-main">Analisis Utilisasi</span>
                        <p class="text-[11px] text-text-secondary">Pantau barang mana yang paling produktif dan barang yang menganggur.</p>
                    </div>
                    <div class="p-3 bg-surface-subtle border border-border-subtle rounded-lg text-xs space-y-1">
                        <span class="font-bold text-text-main">Penyewa Terbaik</span>
                        <p class="text-[11px] text-text-secondary">Ketahui pelanggan yang paling sering menyewa dan menghasilkan omzet.</p>
                    </div>
                    <div class="p-3 bg-surface-subtle border border-border-subtle rounded-lg text-xs space-y-1">
                        <span class="font-bold text-text-main">Ekspor CSV</span>
                        <p class="text-[11px] text-text-secondary">Unduh laporan keuangan ke format Excel/CSV tanpa batas.</p>
                    </div>
                </div>

                <div class="pt-4">
                    <a href="{{ route('vendor.billing.index') }}" class="inline-flex items-center justify-center min-h-[44px] px-6 py-2.5 rounded-lg bg-primary hover:opacity-90 text-primary-text font-semibold text-xs transition shadow-xs">
                        Lihat Pilihan Paket Pro
                    </a>
                </div>
            </div>
        @else
            <!-- Toolbar Filter Periode & Tombol Ekspor CSV (Pro Vendor) -->
            <div class="bg-surface border border-border-subtle rounded-xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <!-- Preset Buttons -->
                <div class="flex items-center flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="setPreset('this_month')"
                        class="min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border transition {{ $preset === 'this_month' ? 'bg-primary text-primary-text border-primary' : 'bg-surface text-text-main border-border-subtle hover:bg-surface-subtle' }}"
                    >
                        Bulan Ini
                    </button>
                    <button
                        type="button"
                        wire:click="setPreset('last_30_days')"
                        class="min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border transition {{ $preset === 'last_30_days' ? 'bg-primary text-primary-text border-primary' : 'bg-surface text-text-main border-border-subtle hover:bg-surface-subtle' }}"
                    >
                        30 Hari Terakhir
                    </button>
                    <button
                        type="button"
                        wire:click="setPreset('last_7_days')"
                        class="min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border transition {{ $preset === 'last_7_days' ? 'bg-primary text-primary-text border-primary' : 'bg-surface text-text-main border-border-subtle hover:bg-surface-subtle' }}"
                    >
                        7 Hari Terakhir
                    </button>
                    <button
                        type="button"
                        wire:click="setPreset('last_month')"
                        class="min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border transition {{ $preset === 'last_month' ? 'bg-primary text-primary-text border-primary' : 'bg-surface text-text-main border-border-subtle hover:bg-surface-subtle' }}"
                    >
                        Bulan Lalu
                    </button>
                </div>

                <!-- Custom Date Inputs & Export CSV -->
                <div class="flex items-center flex-wrap gap-2.5">
                    <div class="flex items-center gap-1.5 text-xs text-text-secondary">
                        <input
                            type="date"
                            wire:model.live="startDate"
                            class="min-h-[44px] h-11 px-3 text-xs rounded-lg border border-border-subtle bg-surface text-text-main focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                        <span>s/d</span>
                        <input
                            type="date"
                            wire:model.live="endDate"
                            class="min-h-[44px] h-11 px-3 text-xs rounded-lg border border-border-subtle bg-surface text-text-main focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>

                    <a
                        href="{{ $data['exportUrl'] }}"
                        class="inline-flex items-center justify-center min-h-[44px] px-4 text-xs font-semibold rounded-lg bg-primary hover:opacity-90 text-primary-text transition gap-1.5 shadow-xs"
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
                <div class="bg-surface border border-border-subtle rounded-xl p-5 shadow-xs space-y-1">
                    <span class="text-xs font-medium text-text-secondary">Total Omzet Sewa</span>
                    <p class="text-xl sm:text-2xl font-bold text-text-main tabular-nums">
                        {{ \App\Helpers\FormatHelper::rupiah($data['totalRevenue']) }}
                    </p>
                    <p class="text-[11px] text-text-muted">Total pemasukan transaksi valid dalam periode (Disetujui, Aktif, Selesai)</p>
                </div>

                <div class="bg-surface border border-border-subtle rounded-xl p-5 shadow-xs space-y-1">
                    <span class="text-xs font-medium text-text-secondary">Jumlah Transaksi Disewa</span>
                    <p class="text-xl sm:text-2xl font-bold text-text-main tabular-nums">
                        {{ $data['totalBookingsCount'] }} Pesanan
                    </p>
                    <p class="text-[11px] text-text-muted">Booking berstatus disetujui, aktif, dan selesai</p>
                </div>

                <div class="bg-surface border border-border-subtle rounded-xl p-5 shadow-xs space-y-1">
                    <span class="text-xs font-medium text-text-secondary">Total Hari Tersewa</span>
                    <p class="text-xl sm:text-2xl font-bold text-text-main tabular-nums">
                        {{ \App\Helpers\FormatHelper::days($data['totalRentedDays']) }}
                    </p>
                    <p class="text-[11px] text-text-muted">Akumulasi hari pemakaian dari seluruh unit</p>
                </div>
            </div>

            <!-- Tabel Utilisasi Barang (Rented vs Idle Days) -->
            <div class="bg-surface border border-border-subtle rounded-xl p-5 shadow-xs space-y-4">
                <div class="border-b border-border-subtle pb-3">
                    <h3 class="text-sm font-bold text-text-main">
                        Analisis Utilisasi Barang Rental
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Perbandingan hari unit disewa vs menganggur dalam rentang {{ $data['totalPeriodDays'] }} hari
                    </p>
                </div>

                @if($data['itemStats']->isEmpty())
                    <div class="py-6 text-center text-xs text-text-muted">
                        Belum ada barang di katalog untuk dianalisis.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-surface-subtle text-text-secondary border-b border-border-subtle">
                                    <th class="p-2.5 font-semibold">Nama Unit</th>
                                    <th class="p-2.5 font-semibold">Kategori</th>
                                    <th class="p-2.5 font-semibold text-center">Hari Disewa</th>
                                    <th class="p-2.5 font-semibold text-center">Hari Menganggur</th>
                                    <th class="p-2.5 font-semibold text-center">Tingkat Utilisasi</th>
                                    <th class="p-2.5 font-semibold text-right">Omzet Unit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle">
                                @foreach($data['itemStats'] as $item)
                                    <tr class="hover:bg-surface-subtle/50 transition">
                                        <td class="p-2.5 font-semibold text-text-main">
                                            {{ $item['name'] }}
                                            @if($item['code'])
                                                <span class="block text-[11px] font-mono text-text-muted">Kode: {{ $item['code'] }}</span>
                                            @endif
                                        </td>
                                        <td class="p-2.5 text-text-secondary">
                                            {{ $item['category'] }}
                                        </td>
                                        <td class="p-2.5 text-center font-bold text-text-main tabular-nums">
                                            {{ \App\Helpers\FormatHelper::days($item['rented_days']) }}
                                        </td>
                                        <td class="p-2.5 text-center text-text-muted tabular-nums">
                                            {{ \App\Helpers\FormatHelper::days($item['idle_days']) }}
                                        </td>
                                        <td class="p-2.5 text-center">
                                            <div class="inline-flex items-center gap-1.5">
                                                <span class="inline-block w-12 text-right font-bold text-text-main tabular-nums">
                                                    {{ $item['utilization_rate'] }}%
                                                </span>
                                                <div class="w-16 h-2 rounded-full bg-surface-subtle border border-border-subtle overflow-hidden hidden sm:block">
                                                    <div class="h-full bg-primary" style="width: {{ $item['utilization_rate'] }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-2.5 text-right font-bold text-text-main tabular-nums">
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
            <div class="bg-surface border border-border-subtle rounded-xl p-5 shadow-xs space-y-4">
                <div class="border-b border-border-subtle pb-3">
                    <h3 class="text-sm font-bold text-text-main">
                        Penyewa Terbaik (Top Renters Periode Ini)
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Pelanggan dengan frekuensi dan nilai transaksi sewa tertinggi
                    </p>
                </div>

                @if($data['topRenters']->isEmpty())
                    <div class="py-6 text-center text-xs text-text-muted">
                        Belum ada data penyewa dalam periode yang dipilih.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-surface-subtle text-text-secondary border-b border-border-subtle">
                                    <th class="p-2.5 font-semibold w-12 text-center">No</th>
                                    <th class="p-2.5 font-semibold">Nama Penyewa</th>
                                    <th class="p-2.5 font-semibold">Kontak WhatsApp</th>
                                    <th class="p-2.5 font-semibold text-center">Frekuensi Sewa</th>
                                    <th class="p-2.5 font-semibold text-right">Total Kontribusi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle">
                                @foreach($data['topRenters'] as $idx => $renter)
                                    <tr class="hover:bg-surface-subtle/50 transition">
                                        <td class="p-2.5 text-center font-bold text-text-muted tabular-nums">
                                            #{{ $idx + 1 }}
                                        </td>
                                        <td class="p-2.5 font-semibold text-text-main">
                                            {{ $renter['name'] }}
                                        </td>
                                        <td class="p-2.5 font-mono text-text-secondary">
                                            {{ $renter['phone'] }}
                                        </td>
                                        <td class="p-2.5 text-center font-bold text-text-main tabular-nums">
                                            {{ $renter['bookings_count'] }} kali
                                        </td>
                                        <td class="p-2.5 text-right font-bold text-text-main tabular-nums">
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
