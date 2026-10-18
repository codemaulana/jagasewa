<div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-5">
    <div class="border-b border-gray-100 pb-3">
        <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
            <span>📝</span> Formulir Pengajuan Sewa
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">
            Lengkapi data diri Anda untuk memesan barang ini secara langsung ke vendor.
        </p>
    </div>

    <!-- Error Alert Banner -->
    @if($errorMessage)
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-3.5 rounded-2xl text-xs flex items-start gap-2.5">
            <span class="text-base leading-none">⚠️</span>
            <div class="leading-relaxed">
                <span class="font-bold">Pengajuan Gagal:</span> {{ $errorMessage }}
            </div>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <!-- 1. INFORMASI PENYEWA -->
        <div class="space-y-3">
            <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Informasi Penyewa</h4>

            <div>
                <label for="name" class="block text-xs font-semibold text-gray-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" id="name" wire:model="name" placeholder="contoh: Budi Santoso"
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border @error('name') border-rose-300 bg-rose-50/30 @else border-gray-200 bg-gray-50/50 @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                @error('name')
                    <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-gray-700 mb-1">Nomor WhatsApp <span class="text-rose-500">*</span></label>
                <input type="text" id="phone" wire:model="phone" placeholder="contoh: 081234567890 atau +62812..."
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border @error('phone') border-rose-300 bg-rose-50/30 @else border-gray-200 bg-gray-50/50 @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                <p class="text-[10px] text-gray-400 mt-0.5">Digunakan untuk konfirmasi pesanan via WhatsApp oleh vendor.</p>
                @error('phone')
                    <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="address" class="block text-xs font-semibold text-gray-700 mb-1">Alamat Domisili / Pengiriman <span class="text-rose-500">*</span></label>
                <textarea id="address" wire:model="address" rows="2" placeholder="Alamat lengkap tujuan pengiriman barang sewa"
                          class="w-full text-xs px-3.5 py-2.5 rounded-xl border @error('address') border-rose-300 bg-rose-50/30 @else border-gray-200 bg-gray-50/50 @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"></textarea>
                @error('address')
                    <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- 2. JADWAL SEWA -->
        <div class="space-y-3 pt-2 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Jadwal Sewa</h4>
                <span class="text-[10px] text-emerald-600 font-medium">Bisa klik langsung di kalender</span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="startDate" class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                    <input type="date" id="startDate" wire:model.live="startDate"
                           class="w-full text-xs px-3 py-2.5 rounded-xl border @error('startDate') border-rose-300 bg-rose-50/30 @else border-gray-200 bg-gray-50/50 @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @error('startDate')
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="endDate" class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                    <input type="date" id="endDate" wire:model.live="endDate"
                           class="w-full text-xs px-3 py-2.5 rounded-xl border @error('endDate') border-rose-300 bg-rose-50/30 @else border-gray-200 bg-gray-50/50 @enderror focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition">
                    @error('endDate')
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Price & Duration Summary Box -->
            @if($totalDays > 0)
                <div class="bg-emerald-50/80 border border-emerald-200/80 rounded-2xl p-3.5 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">Durasi Sewa:</span>
                        <span class="font-bold text-gray-900">{{ $totalDays }} Hari</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-600">Tarif Sewa ({{ $totalDays }} × Rp {{ number_format($item->price_per_day, 0, ',', '.') }}):</span>
                        <span class="font-bold text-emerald-800">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs border-t border-emerald-200/50 pt-1.5">
                        <span class="text-gray-600">Jaminan Deposit (Dikembalikan):</span>
                        <span class="font-bold text-gray-800">
                            {{ $depositAmount > 0 ? 'Rp ' . number_format($depositAmount, 0, ',', '.') : 'Bebas Deposit' }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- 3. CATATAN TAMBAHAN -->
        <div class="space-y-2 pt-2 border-t border-gray-100">
            <label for="renterNotes" class="block text-xs font-semibold text-gray-700">Catatan untuk Vendor (Opsional)</label>
            <textarea id="renterNotes" wire:model="renterNotes" rows="2" placeholder="Permintaan khusus ukuran, waktu pengiriman, atau kelengkapan tertentu..."
                      class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 transition"></textarea>
            @error('renterNotes')
                <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
            @enderror
        </div>

        <!-- 4. DOKUMEN IDENTITAS (KYC) -->
        <div class="space-y-3 pt-2 border-t border-gray-100">
            <div>
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Verifikasi Identitas</h4>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    Data dienkripsi secara aman dan akan dihapus otomatis dalam 30 hari setelah sewa selesai.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Foto KTP / Kartu Identitas</label>
                    <input type="file" wire:model="ktpPhoto" accept="image/*"
                           class="w-full text-[11px] text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    @error('ktpPhoto')
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Selfie dengan Identitas</label>
                    <input type="file" wire:model="selfiePhoto" accept="image/*"
                           class="w-full text-[11px] text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    @error('selfiePhoto')
                        <p class="text-[11px] text-rose-600 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- 5. CHECKBOX CONSENT PRIVASI (UU PDP) -->
        <div class="pt-2 border-t border-gray-100 space-y-1">
            <label class="flex items-start gap-2.5 cursor-pointer">
                <input type="checkbox" wire:model.live="agreePrivacy"
                       class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4">
                <span class="text-[11px] text-gray-600 leading-relaxed">
                    Saya menyetujui bahwa data identitas dan kontak saya digunakan oleh <strong class="text-gray-800">{{ $item->vendor->name }}</strong> untuk verifikasi booking ini, dan akan dihapus otomatis dalam 30 hari sesuai ketentuan privasi JagaSewa.
                </span>
            </label>
            @error('agreePrivacy')
                <p class="text-[11px] text-rose-600 mt-1 font-medium pl-6">{{ $message }}</p>
            @enderror
        </div>

        <!-- 6. SUBMIT BUTTON -->
        <div class="pt-2">
            <button type="submit" 
                    wire:loading.attr="disabled"
                    @disabled(! $agreePrivacy)
                    class="w-full py-3.5 px-4 rounded-2xl font-bold text-sm shadow-md transition flex items-center justify-center gap-2
                           @if($agreePrivacy) bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-emerald-600/20 @else bg-gray-200 text-gray-400 cursor-not-allowed shadow-none @endif">
                <span wire:loading.remove>Kirim Permintaan Booking →</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Memproses Booking...
                </span>
            </button>
            @if(! $agreePrivacy)
                <p class="text-[10px] text-gray-400 text-center mt-1.5">Centang persetujuan privasi di atas untuk mengaktifkan tombol pesan.</p>
            @endif
        </div>
    </form>
</div>
