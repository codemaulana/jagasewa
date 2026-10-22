<x-layouts.renter 
    :title="'Permintaan Sewa Berhasil — ' . $booking->reference_code . ' | JagaSewa'"
    :vendor="$booking->vendor"
    headerTag="Konfirmasi"
    :backUrl="route('public.items.show', ['vendor_slug' => $booking->vendor->slug, 'item_slug' => $booking->item->slug])"
    backText="Kembali ke barang"
>
    <!-- Kartu Status Utama -->
    <x-ui.card class="text-center space-y-3 p-5 sm:p-6">
        <div class="w-12 h-12 mx-auto rounded-full badge-status-success border flex items-center justify-center text-success">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <div>
            <div class="mb-2">
                <x-ui.badge status="pending" size="sm" />
            </div>
            <h1 class="text-lg font-bold text-text-main">Permintaan Sewa Berhasil Dibuat!</h1>
            <p class="text-xs text-text-secondary max-w-sm mx-auto mt-1 leading-relaxed">
                Pesanan tercatat dalam sistem. Jadwal telah diblokir sementara menunggu persetujuan vendor.
            </p>
        </div>

        <!-- Kotak Kode Booking -->
        <div class="bg-surface-subtle border border-border-subtle rounded-lg p-3.5 my-2">
            <span class="text-[11px] text-text-secondary block mb-0.5">Kode referensi pesanan</span>
            <span class="text-lg font-bold font-mono tracking-wider text-text-main select-all">{{ $booking->reference_code }}</span>
            <p class="text-[11px] text-text-muted mt-1">Gunakan kode ini saat berkomunikasi dengan vendor.</p>
        </div>
    </x-ui.card>

    <!-- Tombol WhatsApp Konfirmasi -->
    <x-ui.card class="space-y-3">
        <div>
            <h2 class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Konfirmasi ke Vendor</h2>
            <p class="text-xs text-text-secondary leading-relaxed mt-0.5">
                Kirim pesan konfirmasi langsung ke nomor WhatsApp vendor untuk memverifikasi pesanan Anda.
            </p>
        </div>

        @if(!empty($waUrl))
            <x-ui.button 
                variant="whatsapp" 
                size="lg" 
                href="{{ $waUrl }}" 
                target="_blank" 
                rel="noopener noreferrer" 
                pill 
                fullWidth
            >
                <svg class="w-4 h-4 fill-current mr-1" viewBox="0 0 24 24">
                    <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                </svg>
                <span>Hubungi Vendor via WhatsApp</span>
            </x-ui.button>
        @else
            <div class="bg-surface-subtle border border-border-subtle p-3 rounded-lg text-xs text-text-main">
                <span class="font-medium block mb-1">Kontak Vendor:</span>
                <span class="select-all font-mono">{{ $booking->vendor->wa_number ?? 'Nomor WhatsApp belum tercantum' }}</span>
            </div>
        @endif
    </x-ui.card>

    <!-- Rincian Pesanan -->
    <x-ui.card class="space-y-3">
        <h2 class="text-xs font-semibold text-text-secondary uppercase tracking-wider">Ringkasan Pesanan</h2>

        <div class="space-y-2 text-xs">
            <div class="flex items-center justify-between py-1 border-b border-border-subtle/50">
                <span class="text-text-secondary">Barang</span>
                <span class="font-medium text-text-main">{{ $booking->item->name }}</span>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-border-subtle/50">
                <span class="text-text-secondary">Vendor</span>
                <span class="font-medium text-text-main">{{ $booking->vendor->name }}</span>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-border-subtle/50">
                <span class="text-text-secondary">Penyewa</span>
                <span class="font-medium text-text-main">{{ $booking->renter->name }}</span>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-border-subtle/50">
                <span class="text-text-secondary">Periode sewa</span>
                <span class="font-medium text-text-main tabular-nums">
                    {{ $booking->start_date->format('d M Y') }} — {{ $booking->end_date->format('d M Y') }} ({{ $booking->total_days }} hari)
                </span>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-border-subtle/50">
                <span class="text-text-secondary">Tarif sewa</span>
                <span class="font-semibold text-text-main tabular-nums">Rp {{ number_format($booking->total_price, 0, ',', '.') }}</span>
            </div>
            <div class="flex items-center justify-between py-1">
                <span class="text-text-secondary">Uang jaminan (deposit)</span>
                <span class="font-semibold text-text-main tabular-nums">
                    {{ $booking->deposit_paid > 0 ? 'Rp ' . number_format($booking->deposit_paid, 0, ',', '.') : 'Tanpa deposit' }}
                </span>
            </div>
        </div>
    </x-ui.card>

    <!-- Langkah Selanjutnya -->
    <x-ui.card class="space-y-2.5">
        <h3 class="text-xs font-semibold text-text-main uppercase tracking-wider">Langkah selanjutnya</h3>
        <ol class="text-xs text-text-secondary space-y-1.5 list-decimal list-inside leading-relaxed">
            <li>Kirim pesan konfirmasi melalui tombol WhatsApp ke vendor.</li>
            <li>Vendor akan memeriksa jadwal dan verifikasi data dalam 24 jam.</li>
            <li>Setelah disetujui, koordinasikan waktu pengambilan atau pengiriman barang.</li>
            <li>Pesanan otomatis dibatalkan sistem jika tidak dikonfirmasi dalam 24 jam.</li>
        </ol>
    </x-ui.card>
</x-layouts.renter>
