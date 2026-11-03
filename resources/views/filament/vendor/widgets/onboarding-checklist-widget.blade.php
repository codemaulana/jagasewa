<x-filament-widgets::widget>
    @if($vendor)
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                Panduan Memulai Toko Rental
                            </h3>
                            @if($completedSteps === 3)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Siap Beroperasi!
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    {{ 3 - $completedSteps }} Langkah Lagi
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Selesaikan 3 langkah penting ini agar toko Anda siap menerima pesanan sewa secara mandiri.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 self-end sm:self-center shrink-0">
                    <div class="text-right">
                        <div class="text-xs font-bold text-slate-900 tabular-nums">{{ $completedSteps }}/3 Selesai</div>
                        <div class="text-[11px] text-slate-400 font-medium">{{ $progressPercent }}% Progres</div>
                    </div>
                    <div class="w-24 bg-slate-100 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-200/60">
                        <div class="h-full rounded-full transition-all duration-500 {{ $completedSteps === 3 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $progressPercent }}%"></div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                <!-- Langkah 1: Profil Toko -->
                <div class="p-4 rounded-xl border transition-all duration-200 flex flex-col justify-between {{ $hasProfile ? 'bg-emerald-50/30 border-emerald-200' : 'bg-slate-50/60 border-slate-200 hover:border-slate-300' }}">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold {{ $hasProfile ? 'text-emerald-800' : 'text-slate-900' }}">1. Profil & WhatsApp</span>
                            @if($hasProfile)
                                <div class="w-5 h-5 rounded-full bg-emerald-500 text-primary-text flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Atur deskripsi toko, alamat penjemputan, dan nomor WhatsApp resmi Anda.
                        </p>
                    </div>
                    <div class="pt-3 mt-3 border-t {{ $hasProfile ? 'border-emerald-100' : 'border-slate-200/80' }}">
                        <a href="{{ \App\Filament\Vendor\Pages\StoreSettings::getUrl() }}" class="text-xs font-semibold {{ $hasProfile ? 'text-emerald-700 hover:text-emerald-800' : 'text-blue-600 hover:text-blue-700' }} inline-flex items-center gap-1.5 group">
                            <span>{{ $hasProfile ? 'Ubah Pengaturan' : 'Atur Sekarang' }}</span>
                            <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Langkah 2: Tambah Barang -->
                <div class="p-4 rounded-xl border transition-all duration-200 flex flex-col justify-between {{ $hasItems ? 'bg-emerald-50/30 border-emerald-200' : 'bg-slate-50/60 border-slate-200 hover:border-slate-300' }}">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold {{ $hasItems ? 'text-emerald-800' : 'text-slate-900' }}">2. Tambah Barang Rental</span>
                            @if($hasItems)
                                <div class="w-5 h-5 rounded-full bg-emerald-500 text-primary-text flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Unggah foto produk, tentukan tarif sewa harian, deposit jaminan, dan stok unit.
                        </p>
                    </div>
                    <div class="pt-3 mt-3 border-t {{ $hasItems ? 'border-emerald-100' : 'border-slate-200/80' }}">
                        <a href="{{ url('/vendor/items/create') }}" class="text-xs font-semibold {{ $hasItems ? 'text-emerald-700 hover:text-emerald-800' : 'text-blue-600 hover:text-blue-700' }} inline-flex items-center gap-1.5 group">
                            <span>{{ $hasItems ? 'Lihat Katalog Barang' : 'Tambah Barang Baru' }}</span>
                            <span class="group-hover:translate-x-0.5 transition-transform">&rarr;</span>
                        </a>
                    </div>
                </div>

                <!-- Langkah 3: Bagikan ke Pelanggan -->
                <div class="p-4 rounded-xl border transition-all duration-200 flex flex-col justify-between {{ $hasBookings ? 'bg-emerald-50/30 border-emerald-200' : 'bg-slate-50/60 border-slate-200 hover:border-slate-300' }}">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold {{ $hasBookings ? 'text-emerald-800' : 'text-slate-900' }}">3. Bagikan Tautan Toko</span>
                            @if($hasBookings)
                                <div class="w-5 h-5 rounded-full bg-emerald-500 text-primary-text flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            @else
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Pasang tautan di bio Instagram / TikTok agar penyewa langsung memilih jadwal dan booking.
                        </p>
                    </div>
                    <div class="pt-3 mt-3 border-t {{ $hasBookings ? 'border-emerald-100' : 'border-slate-200/80' }}" x-data="{ copied: false }">
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $storeUrl }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center gap-1.5 transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <span x-text="copied ? 'Tautan Berhasil Disalin!' : 'Salin Tautan Toko'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>