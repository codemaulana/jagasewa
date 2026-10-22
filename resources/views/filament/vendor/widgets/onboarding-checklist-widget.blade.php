<x-filament-widgets::widget>
    @if($vendor)
        <div class="bg-surface border border-border-subtle rounded-xl p-4 sm:p-5 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-text-main flex items-center gap-2">
                        <svg class="w-4 h-4 text-text-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>Panduan Memulai Toko Rental</span>
                        @if($completedSteps === 3)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success-bg text-success border border-success-border">
                                Siap Beroperasi!
                            </span>
                        @endif
                    </h3>
                    <p class="text-xs text-text-muted mt-0.5">
                        Selesaikan 3 langkah penting ini agar toko Anda siap menerima pesanan sewa secara maksimal.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="text-xs font-bold text-text-main tabular-nums">{{ $completedSteps }}/3 Selesai</span>
                    <div class="w-20 bg-surface-subtle border border-border-subtle rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-300 {{ $completedSteps === 3 ? 'bg-success' : 'bg-primary' }}" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Langkah 1: Profil Toko -->
                <div class="p-3.5 rounded-lg border {{ $hasProfile ? 'bg-success-bg/40 border-success-border' : 'bg-surface-subtle border-border-subtle' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold {{ $hasProfile ? 'text-success' : 'text-text-main' }}">1. Lengkapi Profil Toko</span>
                            @if($hasProfile)
                                <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full bg-warning"></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-text-muted">
                            Atur deskripsi, alamat, dan nomor WhatsApp toko Anda.
                        </p>
                    </div>
                    <div class="pt-2 mt-2 border-t border-border-subtle">
                        <a href="{{ \App\Filament\Vendor\Pages\StoreSettings::getUrl() }}" class="text-[11px] font-semibold text-text-main hover:underline inline-flex items-center gap-1">
                            <span>{{ $hasProfile ? 'Ubah Pengaturan' : 'Atur Sekarang' }}</span> &rarr;
                        </a>
                    </div>
                </div>

                <!-- Langkah 2: Tambah Barang -->
                <div class="p-3.5 rounded-lg border {{ $hasItems ? 'bg-success-bg/40 border-success-border' : 'bg-surface-subtle border-border-subtle' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold {{ $hasItems ? 'text-success' : 'text-text-main' }}">2. Tambah Barang Rental</span>
                            @if($hasItems)
                                <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full bg-warning"></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-text-muted">
                            Unggah foto barang, harga sewa per hari, dan deposit.
                        </p>
                    </div>
                    <div class="pt-2 mt-2 border-t border-border-subtle">
                        <a href="{{ url('/vendor/items/create') }}" class="text-[11px] font-semibold text-text-main hover:underline inline-flex items-center gap-1">
                            <span>{{ $hasItems ? 'Lihat Katalog' : 'Tambah Barang' }}</span> &rarr;
                        </a>
                    </div>
                </div>

                <!-- Langkah 3: Bagikan Link -->
                <div class="p-3.5 rounded-lg border {{ $hasBookings ? 'bg-success-bg/40 border-success-border' : 'bg-surface-subtle border-border-subtle' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-bold {{ $hasBookings ? 'text-success' : 'text-text-main' }}">3. Bagikan ke Pelanggan</span>
                            @if($hasBookings)
                                <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="w-2 h-2 rounded-full bg-warning"></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-text-muted">
                            Pasang link di bio media sosial agar penyewa bisa booking.
                        </p>
                    </div>
                    <div class="pt-2 mt-2 border-t border-border-subtle" x-data="{ copied: false }">
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $storeUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-[11px] font-semibold text-text-main hover:underline inline-flex items-center gap-1"
                        >
                            <span x-text="copied ? 'Tautan Tersalin!' : 'Salin Tautan Toko'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
