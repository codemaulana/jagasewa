<div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-4">
    <div class="border-b border-neutral-100 pb-3">
        <h3 class="text-sm font-semibold text-neutral-900">
            Formulir Pengajuan Sewa
        </h3>
        <p class="text-xs text-neutral-500 mt-0.5">
            Lengkapi data diri untuk mengajukan permohonan sewa ke vendor.
        </p>
    </div>

    <!-- Banner Galat Validasi -->
    @if($errorMessage)
        <div class="badge-status-danger border text-xs p-3 rounded-xl flex items-start gap-2.5">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <div class="leading-relaxed">
                <span class="font-semibold">Pengajuan belum dapat diproses:</span> {{ $errorMessage }}
            </div>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <!-- 1. INFORMASI PENYEWA -->
        <div class="space-y-3">
            <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Identitas Kontak</h4>

            <div>
                <label for="name" class="block text-xs font-medium text-neutral-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" id="name" wire:model="name" placeholder="Nama lengkap sesuai KTP"
                       class="w-full text-xs px-3.5 h-10 rounded-xl border @error('name') border-red-300 bg-red-50/20 @else border-neutral-300 bg-white @enderror focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition">
                @error('name')
                    <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-medium text-neutral-700 mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
                <input type="text" id="phone" wire:model="phone" placeholder="Contoh: 081234567890"
                       class="w-full text-xs px-3.5 h-10 rounded-xl border @error('phone') border-red-300 bg-red-50/20 @else border-neutral-300 bg-white @enderror focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition">
                <p class="text-[11px] text-neutral-400 mt-0.5">Digunakan vendor untuk konfirmasi pesanan via WhatsApp.</p>
                @error('phone')
                    <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="address" class="block text-xs font-medium text-neutral-700 mb-1">Alamat domisili atau pengiriman <span class="text-red-500">*</span></label>
                <textarea id="address" wire:model="address" rows="2" placeholder="Alamat lengkap pengiriman barang sewa"
                          class="w-full text-xs p-3 rounded-xl border @error('address') border-red-300 bg-red-50/20 @else border-neutral-300 bg-white @enderror focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition"></textarea>
                @error('address')
                    <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- 2. JADWAL SEWA -->
        <div class="space-y-3 pt-3 border-t border-neutral-100">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Jadwal Sewa</h4>
                <span class="text-[11px] text-neutral-500">Dapat dipilih pada kalender di atas</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="startDate" class="block text-xs font-medium text-neutral-700 mb-1">Tanggal mulai <span class="text-red-500">*</span></label>
                    <input type="date" id="startDate" wire:model.live="startDate"
                           class="w-full text-xs px-3 h-10 rounded-xl border @error('startDate') border-red-300 bg-red-50/20 @else border-neutral-300 bg-white @enderror focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition">
                    @error('startDate')
                        <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="endDate" class="block text-xs font-medium text-neutral-700 mb-1">Tanggal selesai <span class="text-red-500">*</span></label>
                    <input type="date" id="endDate" wire:model.live="endDate"
                           class="w-full text-xs px-3 h-10 rounded-xl border @error('endDate') border-red-300 bg-red-50/20 @else border-neutral-300 bg-white @enderror focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition">
                    @error('endDate')
                        <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Ringkasan Biaya -->
            @if($totalDays > 0)
                <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-3.5 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Durasi sewa:</span>
                        <span class="font-medium text-neutral-900">{{ $totalDays }} hari</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-neutral-500">Tarif sewa ({{ $totalDays }} × Rp {{ number_format($item->price_per_day, 0, ',', '.') }}):</span>
                        <span class="font-semibold text-neutral-900">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-neutral-200 pt-1.5">
                        <span class="text-neutral-500">Uang jaminan (deposit):</span>
                        <span class="font-semibold text-neutral-900">
                            {{ $depositAmount > 0 ? 'Rp ' . number_format($depositAmount, 0, ',', '.') : 'Tanpa deposit' }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- 3. CATATAN TAMBAHAN -->
        <div class="space-y-2 pt-3 border-t border-neutral-100">
            <label for="renterNotes" class="block text-xs font-medium text-neutral-700">Catatan untuk vendor (opsional)</label>
            <textarea id="renterNotes" wire:model="renterNotes" rows="2" placeholder="Catatan ukuran, kelengkapan, atau waktu penyerahan"
                      class="w-full text-xs p-3 rounded-xl border border-neutral-300 bg-white focus:outline-none focus:border-primary-600 focus:ring-1 focus:ring-primary-600 transition"></textarea>
            @error('renterNotes')
                <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- 4. DOKUMEN IDENTITAS (KYC) -->
        <div class="space-y-3 pt-3 border-t border-neutral-100">
            <div>
                <h4 class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Verifikasi Identitas</h4>
                <p class="text-[11px] text-neutral-400 mt-0.5">
                    Data dienkripsi dan dihapus otomatis 30 hari setelah sewa selesai sesuai ketentuan privasi.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-neutral-50 p-3 rounded-xl border border-neutral-200">
                    <label class="block text-xs font-medium text-neutral-700 mb-1.5">Foto KTP atau identitas resmi</label>
                    <input type="file" wire:model="ktpPhoto" accept="image/*"
                           class="w-full text-[11px] text-neutral-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-neutral-300 file:text-xs file:font-medium file:bg-white file:text-neutral-800 hover:file:bg-neutral-50">
                    @error('ktpPhoto')
                        <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-neutral-50 p-3 rounded-xl border border-neutral-200">
                    <label class="block text-xs font-medium text-neutral-700 mb-1.5">Foto selfie dengan identitas</label>
                    <input type="file" wire:model="selfiePhoto" accept="image/*"
                           class="w-full text-[11px] text-neutral-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border file:border-neutral-300 file:text-xs file:font-medium file:bg-white file:text-neutral-800 hover:file:bg-neutral-50">
                    @error('selfiePhoto')
                        <p class="text-[11px] text-red-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 5. CHECKBOX PERSETUJUAN PRIVASI -->
        <div class="pt-3 border-t border-neutral-100">
            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" wire:model.live="agreePrivacy"
                       class="mt-0.5 rounded border-neutral-300 text-primary-600 focus:ring-primary-600 w-4 h-4">
                <span class="text-[11px] text-neutral-600 leading-relaxed">
                    Saya menyetujui data identitas dan kontak ini digunakan oleh <strong class="text-neutral-900 font-semibold">{{ $item->vendor->name }}</strong> untuk verifikasi booking, dan dihapus otomatis sesuai jadwal retensi privasi JagaSewa.
                </span>
            </label>
            @error('agreePrivacy')
                <p class="text-[11px] text-red-600 mt-1 font-medium pl-6">{{ $message }}</p>
            @enderror
        </div>

        <!-- 6. TOMBOL SUBMIT -->
        <div class="pt-2">
            <button type="submit" 
                    wire:loading.attr="disabled"
                    @disabled(! $agreePrivacy)
                    class="w-full h-11 px-4 rounded-xl font-semibold text-sm shadow-xs transition duration-150 flex items-center justify-center gap-2
                           @if($agreePrivacy) bg-primary-600 hover:bg-primary-700 active:bg-primary-800 text-white @else bg-neutral-200 text-neutral-400 cursor-not-allowed @endif">
                <span wire:loading.remove>Kirim permohonan sewa</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Memproses...
                </span>
            </button>
            @if(! $agreePrivacy)
                <p class="text-[11px] text-neutral-400 text-center mt-1.5">Centang persetujuan privasi untuk mengaktifkan tombol pengajuan.</p>
            @endif
        </div>
    </form>
</div>
