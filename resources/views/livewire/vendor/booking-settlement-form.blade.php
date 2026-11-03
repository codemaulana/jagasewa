<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header Ringkasan Sewa -->
    <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-2">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <span class="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">Penyelesaian Sewa (Settlement)</span>
                <h2 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight mt-0.5">
                    {{ $booking->reference_code }} &mdash; {{ $booking->item->name }}
                </h2>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Dikembalikan (Perlu Settlement)
            </span>
        </div>
        <p class="text-xs sm:text-sm text-slate-500">
            Jadwal sewa disepakati: <strong class="text-slate-800">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</strong> s.d. <strong class="text-slate-800">{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</strong> ({{ $booking->total_days }} hari).
        </p>
    </div>

    <!-- Form Input Pengembalian & Kerusakan -->
    <form wire:submit.prevent="submit" class="space-y-6">
        <!-- 1. Tanggal Aktual Kembali -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">1</span>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">
                    Tanggal Pengembalian Fisik
                </h3>
            </div>

            <div class="max-w-xs space-y-1.5">
                <label for="actual_return_date" class="block text-xs font-semibold text-slate-700">
                    Tanggal Aktual Barang Diterima <span class="text-rose-500">*</span>
                </label>
                <input 
                    type="date" 
                    id="actual_return_date"
                    wire:model.live="actual_return_date"
                    class="w-full h-11 px-3 text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 transition shadow-2xs"
                    required
                >
                @error('actual_return_date')
                    <span class="text-xs text-rose-600 font-medium">{{ $message }}</span>
                @enderror
                <p class="text-[11px] text-slate-500">
                    Batas akhir sewa: {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}. Denda telat: <strong class="text-slate-700">Rp {{ number_format($booking->item->fine_per_day, 0, ',', '.') }}/hari</strong>.
                </p>
            </div>
        </div>

        <!-- 2. Rincian Kerusakan (Damage Items) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">2</span>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">
                            Rincian Kerusakan Barang (Opsional)
                        </h3>
                        <p class="text-xs text-slate-500">
                            Tambahkan catatan potongan jika ada cacat fisik atau kelengkapan yang rusak / hilang.
                        </p>
                    </div>
                </div>
                <button 
                    type="button" 
                    wire:click="addDamageItem"
                    class="text-xs font-semibold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-200 h-9 px-3.5 rounded-xl transition inline-flex items-center gap-1.5 active:scale-95"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Item</span>
                </button>
            </div>

            @if(count($damage_items) === 0)
                <div class="p-6 rounded-xl border border-dashed border-slate-200 text-center bg-slate-50/50">
                    <p class="text-xs text-slate-500 font-medium">
                        Tidak ada kerusakan barang yang dicatat (kondisi baik, bersih, dan lengkap).
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($damage_items as $index => $item)
                        <div class="p-4 bg-slate-50/80 border border-slate-200 rounded-xl flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                            <div class="flex-1 w-full space-y-1">
                                <label class="block text-[11px] font-semibold text-slate-700">Deskripsi Kerusakan</label>
                                <input 
                                    type="text" 
                                    wire:model="damage_items.{{ $index }}.description"
                                    placeholder="Contoh: Kancing copot / noda membandel / lecet / strap putus"
                                    class="w-full h-10 px-3 text-xs sm:text-sm bg-white border border-slate-200 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20"
                                >
                            </div>
                            <div class="w-full sm:w-48 space-y-1">
                                <label class="block text-[11px] font-semibold text-slate-700">Biaya Potongan (Rp)</label>
                                <input 
                                    type="number" 
                                    min="0"
                                    step="1000"
                                    wire:model.live.debounce.300ms="damage_items.{{ $index }}.amount"
                                    class="w-full h-10 px-3 text-xs sm:text-sm bg-white border border-slate-200 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 font-mono"
                                >
                            </div>
                            <div class="pt-5 sm:pt-4 self-end sm:self-center">
                                <button 
                                    type="button" 
                                    wire:click="removeDamageItem({{ $index }})"
                                    class="text-xs text-rose-600 hover:text-rose-700 font-medium px-2.5 py-1.5 rounded-lg hover:bg-rose-50 transition"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- 3. Ringkasan Perhitungan Real-Time (BR-03) -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">3</span>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">
                    Ringkasan Perhitungan Denda & Deposit (Real-Time)
                </h3>
            </div>

            <div class="bg-slate-50/70 rounded-xl p-4 sm:p-5 border border-slate-200 space-y-2.5 text-xs sm:text-sm">
                <div class="flex justify-between items-center text-slate-600">
                    <span>Keterlambatan Pengembalian</span>
                    <span class="font-bold text-slate-900 tabular-nums">{{ $this->calculation->lateDays }} hari</span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span>Denda Telat ({{ $this->calculation->lateDays }} &times; Rp {{ number_format($this->calculation->finePerDay, 0, ',', '.') }})</span>
                    <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($this->calculation->lateFine, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span>Total Potongan Kerusakan</span>
                    <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($this->calculation->damageDeduction, 0, ',', '.') }}</span>
                </div>
                <div class="border-t border-slate-200 pt-2 flex justify-between items-center font-semibold text-slate-800">
                    <span>Total Seluruh Potongan</span>
                    <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($this->calculation->totalDeduction, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span>Uang Jaminan (Deposit) Awal</span>
                    <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($this->calculation->deposit, 0, ',', '.') }}</span>
                </div>
                
                <!-- Status Akhir Deposit -->
                <div class="border-t border-slate-200 pt-3.5 mt-1">
                    @if($this->calculation->hasRefund)
                        <div class="p-4 bg-emerald-50/80 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-emerald-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Sisa Uang Jaminan (Refund)
                                </span>
                                <p class="text-xs text-emerald-700 mt-0.5">Wajib ditransfer kembali ke rekening penyewa</p>
                            </div>
                            <span class="text-base sm:text-xl font-bold text-emerald-900 font-mono tabular-nums">
                                Rp {{ number_format($this->calculation->refund, 0, ',', '.') }}
                            </span>
                        </div>
                    @elseif($this->calculation->hasShortfall)
                        <div class="p-4 bg-rose-50/80 border border-rose-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-rose-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    Kekurangan Bayar Penyewa
                                </span>
                                <p class="text-xs text-rose-700 mt-0.5">Total denda & kerusakan melebihi deposit awal</p>
                            </div>
                            <span class="text-base sm:text-xl font-bold text-rose-900 font-mono tabular-nums">
                                -Rp {{ number_format($this->calculation->shortfall, 0, ',', '.') }}
                            </span>
                        </div>
                    @else
                        <div class="p-4 bg-slate-100/80 border border-slate-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                    Deposit Habis Pas
                                </span>
                                <p class="text-xs text-slate-500 mt-0.5">Total denda sama persis dengan uang jaminan</p>
                            </div>
                            <span class="text-base sm:text-xl font-bold text-slate-900 font-mono tabular-nums">
                                Rp 0
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- 4. Catatan Tambahan Vendor -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-2">
            <label for="damage_notes" class="block text-xs font-semibold text-slate-700">
                Catatan Kondisi Barang / Catatan Tambahan (Opsional)
            </label>
            <textarea 
                id="damage_notes"
                wire:model="damage_notes"
                rows="3"
                placeholder="Catatan fisik barang saat diterima, detail kesepakatan pengembalian sisa deposit..."
                class="w-full p-3.5 text-xs sm:text-sm bg-white border border-slate-200 rounded-xl focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 text-slate-900 placeholder:text-slate-400"
            ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button 
                type="submit"
                class="min-h-[44px] h-11 px-6 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 active:bg-slate-950 rounded-xl transition shadow-xs inline-flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2"
            >
                Konfirmasi Penyelesaian Sewa
            </button>
        </div>
    </form>
</div>