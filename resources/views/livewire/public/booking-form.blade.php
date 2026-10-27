<x-ui.card class="space-y-4">
    <div class="border-b border-border-subtle/60 pb-3">
        <h3 class="text-sm font-semibold text-text-main">
            Formulir Pengajuan Sewa
        </h3>
        <p class="text-xs text-text-secondary mt-0.5">
            Lengkapi data diri untuk mengajukan permohonan sewa ke vendor.
        </p>
    </div>

    <!-- Banner Galat Validasi -->
    @if($errorMessage)
        <x-ui.alert type="danger" title="Pengajuan belum dapat diproses:">
            {{ $errorMessage }}
        </x-ui.alert>
    @endif

    <form wire:submit="submit" class="space-y-4">
        <div class="space-y-3">
            <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Identitas Kontak</h4>

            <div>
                <x-ui.input 
                    label="Nama Lengkap" 
                    name="name" 
                    id="name"
                    wire:model="name" 
                    placeholder="Nama lengkap sesuai KTP" 
                    required 
                />
            </div>

            <div>
                <x-ui.input 
                    label="Nomor WhatsApp" 
                    name="phone" 
                    id="phone"
                    wire:model="phone" 
                    placeholder="Contoh: 081234567890" 
                    hint="Digunakan vendor untuk konfirmasi pesanan via WhatsApp." 
                    required 
                />
            </div>

            <div>
                <x-ui.textarea 
                    label="Alamat domisili atau pengiriman" 
                    name="address" 
                    id="address"
                    wire:model="address" 
                    rows="2" 
                    placeholder="Alamat lengkap pengiriman barang sewa" 
                    required 
                />
            </div>
        </div>

        <div class="space-y-3 pt-3 border-t border-border-subtle/50">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Jadwal Sewa</h4>
                <span class="text-[11px] text-text-secondary">Dapat dipilih pada kalender di atas</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-ui.date-picker 
                        label="Tanggal mulai" 
                        name="startDate" 
                        id="startDate"
                        wire:model.live="startDate" 
                        required 
                    />
                </div>
                <div>
                    <x-ui.date-picker 
                        label="Tanggal selesai" 
                        name="endDate" 
                        id="endDate"
                        wire:model.live="endDate" 
                        required 
                    />
                </div>
            </div>

            <!-- Ringkasan Biaya -->
            @if($totalDays > 0)
                <div class="bg-surface-subtle border border-border-subtle rounded-lg p-3.5 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Durasi sewa:</span>
                        <span class="font-semibold text-text-main tabular-nums">{{ $totalDays }} hari</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Tarif sewa ({{ $totalDays }} × Rp {{ number_format($item->price_per_day, 0, ',', '.') }}):</span>
                        <span class="font-bold text-text-main tabular-nums">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-border-subtle pt-1.5">
                        <span class="text-text-secondary">Uang jaminan (deposit):</span>
                        <span class="font-semibold text-text-main tabular-nums">
                            {{ $depositAmount > 0 ? 'Rp ' . number_format($depositAmount, 0, ',', '.') : 'Tanpa deposit' }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <div class="space-y-2 pt-3 border-t border-border-subtle/50">
            <x-ui.textarea 
                label="Catatan untuk vendor (opsional)" 
                name="renterNotes" 
                id="renterNotes"
                wire:model="renterNotes" 
                rows="2" 
                placeholder="Catatan ukuran, kelengkapan, atau waktu penyerahan" 
            />
        </div>

        <div class="space-y-3 pt-3 border-t border-border-subtle/50">
            <div>
                <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Verifikasi Identitas</h4>
                <p class="text-[11px] text-text-secondary mt-0.5">
                    Data dienkripsi dan dihapus otomatis 30 hari setelah sewa selesai sesuai ketentuan privasi.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-surface-subtle p-3 rounded-lg border border-border-subtle space-y-1.5">
                    <label for="ktpPhoto" class="block text-xs font-semibold text-text-main">Foto KTP atau identitas resmi</label>
                    <input 
                        type="file" 
                        id="ktpPhoto"
                        wire:model="ktpPhoto" 
                        accept="image/jpeg,image/png,image/webp"
                        class="w-full text-xs text-text-secondary file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border file:border-border-subtle file:text-xs file:font-semibold file:bg-surface file:text-text-main hover:file:bg-surface-subtle cursor-pointer focus:outline-none"
                    >
                    @error('ktpPhoto')
                        <p class="text-xs text-danger font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="bg-surface-subtle p-3 rounded-lg border border-border-subtle space-y-1.5">
                    <label for="selfiePhoto" class="block text-xs font-semibold text-text-main">Foto selfie dengan identitas</label>
                    <input 
                        type="file" 
                        id="selfiePhoto"
                        wire:model="selfiePhoto" 
                        accept="image/jpeg,image/png,image/webp"
                        class="w-full text-xs text-text-secondary file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border file:border-border-subtle file:text-xs file:font-semibold file:bg-surface file:text-text-main hover:file:bg-surface-subtle cursor-pointer focus:outline-none"
                    >
                    @error('selfiePhoto')
                        <p class="text-xs text-danger font-medium mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <div class="pt-3 border-t border-border-subtle/50">
            <label class="flex items-start gap-2.5 cursor-pointer min-h-[44px]">
                <input 
                    type="checkbox" 
                    wire:model.live="agreePrivacy"
                    class="mt-0.5 rounded border-border-subtle text-primary focus:ring-primary w-4 h-4 cursor-pointer"
                >
                <span class="text-xs text-text-secondary leading-relaxed">
                    Saya menyetujui data identitas dan kontak ini digunakan oleh <strong class="text-text-main font-semibold">{{ $item->vendor->name }}</strong> untuk verifikasi booking, dan dihapus otomatis sesuai jadwal retensi privasi JagaSewa.
                </span>
            </label>
            @error('agreePrivacy')
                <p class="text-xs text-danger font-medium mt-1 pl-6">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2">
            <x-ui.button 
                type="submit" 
                variant="primary" 
                size="lg" 
                pill 
                fullWidth
                wire:loading.attr="disabled"
                :disabled="! $agreePrivacy"
            >
                <span wire:loading.remove>Kirim permohonan sewa</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Memproses...
                </span>
            </x-ui.button>
            @if(! $agreePrivacy)
                <p class="text-[11px] text-text-muted text-center mt-1.5">Centang persetujuan privasi untuk mengaktifkan tombol pengajuan.</p>
            @endif
        </div>
    </form>
</x-ui.card>
