<div class="space-y-6">
    <div class="bg-white border border-neutral-200 rounded-xl p-4 sm:p-6 shadow-xs space-y-2">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Penyelesaian Sewa</span>
                <h2 class="text-lg sm:text-xl font-bold text-neutral-900 tracking-tight">
                    {{ $booking->reference_code }} — {{ $booking->item->name }}
                </h2>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                Dikembalikan (Perlu Settlement)
            </span>
        </div>
        <p class="text-xs sm:text-sm text-neutral-600">
            Jadwal sewa disepakati: <strong>{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</strong> s.d. <strong>{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</strong> ({{ $booking->total_days }} hari).
        </p>
    </div>

    <!-- Form Input Pengembalian & Kerusakan -->
    <form wire:submit.prevent="submit" class="space-y-6">
        <!-- Input Tanggal Aktual Kembali -->
        <div class="bg-white border border-neutral-200 rounded-xl p-4 sm:p-6 shadow-xs space-y-4">
            <h3 class="text-sm sm:text-base font-bold text-neutral-900">
                1. Tanggal Pengembalian Fisik
            </h3>

            <div class="max-w-xs space-y-1">
                <label for="actual_return_date" class="block text-xs font-medium text-neutral-700">
                    Tanggal Aktual Barang Diterima <span class="text-red-600">*</span>
                </label>
                <input 
                    type="date" 
                    id="actual_return_date"
                    wire:model.live="actual_return_date"
                    class="w-full h-11 px-3 text-sm bg-white border border-neutral-300 rounded-xl focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900 transition"
                    required
                >
                @error('actual_return_date')
                    <span class="text-xs text-red-600">{{ $message }}</span>
                @enderror
                <p class="text-[11px] text-neutral-500">
                    Batas akhir sewa: {{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}. Denda telat: Rp {{ number_format($booking->item->fine_per_day, 0, ',', '.') }}/hari.
                </p>
            </div>
        </div>

        <!-- Daftar Kerusakan (Damage Items) -->
        <div class="bg-white border border-neutral-200 rounded-xl p-4 sm:p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm sm:text-base font-bold text-neutral-900">
                        2. Rincian Kerusakan Barang (Opsional)
                    </h3>
                    <p class="text-xs text-neutral-500">
                        Tambahkan item kerusakan jika ada cacat fisik atau kelengkapan yang hilang.
                    </p>
                </div>
                <button 
                    type="button" 
                    wire:click="addDamageItem"
                    class="text-xs font-semibold text-neutral-900 bg-neutral-100 hover:bg-neutral-200 border border-neutral-300 h-9 px-3 rounded-lg transition inline-flex items-center gap-1"
                >
                    + Tambah Item
                </button>
            </div>

            @if(count($damage_items) === 0)
                <div class="p-4 rounded-xl border border-dashed border-neutral-200 text-center bg-neutral-50">
                    <p class="text-xs text-neutral-500">
                        Tidak ada kerusakan barang yang dicatat (kondisi baik dan lengkap).
                    </p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($damage_items as $index => $item)
                        <div class="p-3 bg-neutral-50 border border-neutral-200 rounded-xl flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                            <div class="flex-1 w-full space-y-1">
                                <label class="block text-[11px] font-medium text-neutral-600">Deskripsi Kerusakan</label>
                                <input 
                                    type="text" 
                                    wire:model="damage_items.{{ $index }}.description"
                                    placeholder="Contoh: Kancing copot / noda membandel / lecet"
                                    class="w-full h-10 px-3 text-xs sm:text-sm bg-white border border-neutral-300 rounded-lg focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900"
                                >
                            </div>
                            <div class="w-full sm:w-44 space-y-1">
                                <label class="block text-[11px] font-medium text-neutral-600">Biaya Potongan (Rp)</label>
                                <input 
                                    type="number" 
                                    min="0"
                                    step="1000"
                                    wire:model.live.debounce.300ms="damage_items.{{ $index }}.amount"
                                    class="w-full h-10 px-3 text-xs sm:text-sm bg-white border border-neutral-300 rounded-lg focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900"
                                >
                            </div>
                            <div class="pt-5 sm:pt-4 self-end sm:self-center">
                                <button 
                                    type="button" 
                                    wire:click="removeDamageItem({{ $index }})"
                                    class="text-xs text-red-600 hover:text-red-800 font-medium px-2 py-1 rounded transition"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Ringkasan Perhitungan Real-Time (BR-03) -->
        <div class="bg-white border border-neutral-200 rounded-xl p-4 sm:p-6 shadow-xs space-y-4">
            <h3 class="text-sm sm:text-base font-bold text-neutral-900">
                3. Ringkasan Perhitungan Denda & Deposit (Real-Time)
            </h3>

            <div class="bg-neutral-50 rounded-xl p-4 border border-neutral-200 space-y-2.5 text-xs sm:text-sm">
                <div class="flex justify-between items-center text-neutral-600">
                    <span>Keterlambatan Pengembalian</span>
                    <span class="font-semibold text-neutral-900">{{ $this->calculation->lateDays }} hari</span>
                </div>
                <div class="flex justify-between items-center text-neutral-600">
                    <span>Denda Telat ({{ $this->calculation->lateDays }} × Rp {{ number_format($this->calculation->finePerDay, 0, ',', '.') }})</span>
                    <span class="font-semibold text-neutral-900">Rp {{ number_format($this->calculation->lateFine, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-neutral-600">
                    <span>Total Potongan Kerusakan</span>
                    <span class="font-semibold text-neutral-900">Rp {{ number_format($this->calculation->damageDeduction, 0, ',', '.') }}</span>
                </div>
                <div class="border-t border-neutral-200 pt-2 flex justify-between items-center font-medium text-neutral-800">
                    <span>Total Seluruh Potongan</span>
                    <span class="font-bold text-neutral-900">Rp {{ number_format($this->calculation->totalDeduction, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center text-neutral-600">
                    <span>Uang Jaminan (Deposit) Awal</span>
                    <span class="font-semibold text-neutral-900">Rp {{ number_format($this->calculation->deposit, 0, ',', '.') }}</span>
                </div>
                
                <!-- Status Akhir Deposit -->
                <div class="border-t border-neutral-300 pt-3 mt-1">
                    @if($this->calculation->hasRefund)
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wide">Sisa Uang Jaminan</span>
                                <p class="text-[11px] text-emerald-700">Wajib ditransfer kembali ke rekening penyewa</p>
                            </div>
                            <span class="text-base sm:text-lg font-bold text-emerald-800">
                                Rp {{ number_format($this->calculation->refund, 0, ',', '.') }}
                            </span>
                        </div>
                    @elseif($this->calculation->hasShortfall)
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-red-800 uppercase tracking-wide">Kekurangan Bayar Penyewa</span>
                                <p class="text-[11px] text-red-700">Total denda & kerusakan melebihi deposit awal</p>
                            </div>
                            <span class="text-base sm:text-lg font-bold text-red-800">
                                -Rp {{ number_format($this->calculation->shortfall, 0, ',', '.') }}
                            </span>
                        </div>
                    @else
                        <div class="p-3 bg-neutral-100 border border-neutral-200 rounded-xl flex items-center justify-between">
                            <div>
                                <span class="text-xs font-semibold text-neutral-800 uppercase tracking-wide">Deposit Habis Pas</span>
                                <p class="text-[11px] text-neutral-600">Total denda sama persis dengan uang jaminan</p>
                            </div>
                            <span class="text-base sm:text-lg font-bold text-neutral-900">
                                Rp 0
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Catatan Tambahan Vendor -->
        <div class="bg-white border border-neutral-200 rounded-xl p-4 sm:p-6 shadow-xs space-y-2">
            <label for="damage_notes" class="block text-xs font-medium text-neutral-700">
                Catatan Kondisi Barang / Catatan Vendor (Opsional)
            </label>
            <textarea 
                id="damage_notes"
                wire:model="damage_notes"
                rows="3"
                placeholder="Catatan fisik barang saat diterima, detail kesepakatan pengembalian sisa deposit..."
                class="w-full p-3 text-xs sm:text-sm bg-white border border-neutral-300 rounded-xl focus:border-neutral-900 focus:ring-1 focus:ring-neutral-900"
            ></textarea>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button 
                type="submit"
                class="min-h-[44px] h-11 px-6 text-sm font-semibold text-primary-text bg-primary hover:bg-primary-hover active:bg-primary-active rounded-lg transition shadow-subtle inline-flex items-center justify-center"
            >
                Konfirmasi Penyelesaian Sewa
            </button>
        </div>
    </form>
</div>
