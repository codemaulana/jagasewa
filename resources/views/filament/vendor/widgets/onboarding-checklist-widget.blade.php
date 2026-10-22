<x-filament-widgets::widget>
    @if($vendor)
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-700 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Panduan Memulai Toko Rental</span>
                        @if($completedSteps === 3)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Siap Beroperasi!
                            </span>
                        @endif
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Selesaikan 3 langkah penting ini agar toko Anda siap menerima pesanan sewa secara maksimal.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">{{ $completedSteps }}/3 Selesai</span>
                    <div class="w-20 bg-zinc-100 dark:bg-zinc-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-300 {{ $completedSteps === 3 ? 'bg-emerald-500' : 'bg-zinc-900 dark:bg-white' }}" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Langkah 1: Profil Toko -->
                <div class="p-3.5 rounded-lg border {{ $hasProfile ? 'bg-emerald-50/40 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-zinc-50 border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold {{ $hasProfile ? 'text-emerald-800 dark:text-emerald-300' : 'text-zinc-900 dark:text-white' }}">1. Lengkapi Profil Toko</span>
                            @if($hasProfile)
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                            Atur deskripsi, alamat, dan nomor WhatsApp toko Anda.
                        </p>
                    </div>
                    <div class="pt-2 mt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                        <a href="{{ \App\Filament\Vendor\Pages\StoreSettings::getUrl() }}" class="text-[11px] font-semibold text-zinc-800 dark:text-zinc-200 hover:underline inline-flex items-center gap-1">
                            <span>{{ $hasProfile ? 'Ubah Pengaturan' : 'Atur Sekarang' }}</span> &rarr;
                        </a>
                    </div>
                </div>

                <!-- Langkah 2: Tambah Barang -->
                <div class="p-3.5 rounded-lg border {{ $hasItems ? 'bg-emerald-50/40 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-zinc-50 border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold {{ $hasItems ? 'text-emerald-800 dark:text-emerald-300' : 'text-zinc-900 dark:text-white' }}">2. Tambah Barang Rental</span>
                            @if($hasItems)
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                            Unggah foto barang, harga sewa per hari, dan deposit.
                        </p>
                    </div>
                    <div class="pt-2 mt-2 border-t border-zinc-100 dark:border-zinc-800/80">
                        <a href="{{ url('/vendor/items/create') }}" class="text-[11px] font-semibold text-zinc-800 dark:text-zinc-200 hover:underline inline-flex items-center gap-1">
                            <span>{{ $hasItems ? 'Lihat Katalog' : 'Tambah Barang' }}</span> &rarr;
                        </a>
                    </div>
                </div>

                <!-- Langkah 3: Bagikan Link -->
                <div class="p-3.5 rounded-lg border {{ $hasBookings ? 'bg-emerald-50/40 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800' : 'bg-zinc-50 border-zinc-200 dark:bg-zinc-950 dark:border-zinc-800' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold {{ $hasBookings ? 'text-emerald-800 dark:text-emerald-300' : 'text-zinc-900 dark:text-white' }}">3. Bagikan ke Pelanggan</span>
                            @if($hasBookings)
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                            Pasang link di bio media sosial agar penyewa bisa booking.
                        </p>
                    </div>
                    <div class="pt-2 mt-2 border-t border-zinc-100 dark:border-zinc-800/80" x-data="{ copied: false }">
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $storeUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-[11px] font-semibold text-zinc-800 dark:text-zinc-200 hover:underline inline-flex items-center gap-1"
                        >
                            <span x-text="copied ? 'Tautan Tersalin!' : 'Salin Tautan Toko'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
