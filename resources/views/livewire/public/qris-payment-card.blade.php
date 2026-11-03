<div @if(!$isPaid) wire:poll.2s="checkStatus" @endif class="w-full">
    @if($isPaid)
        <div class="bg-emerald-50/80 border border-emerald-200 rounded-2xl p-6 text-center space-y-3 transition-all duration-300 shadow-xs">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                    LUNAS VIA QRIS
                </span>
                <h3 class="text-base sm:text-lg font-bold text-emerald-950 mt-1.5">Pembayaran Telah Diterima!</h3>
                <p class="text-xs text-emerald-700 mt-0.5">
                    Transaksi lunas secara instan. Pesanan Anda kini telah terverifikasi oleh sistem JagaSewa.
                </p>
            </div>
            @if($booking->paid_in_full_at)
                <p class="text-[11px] text-emerald-600 font-mono">
                    Waktu Bayar: {{ $booking->paid_in_full_at->translatedFormat('d M Y, H:i') }} WIB
                </p>
            @endif
        </div>
    @else
        <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 text-center space-y-4 shadow-xs">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="text-left">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pembayaran Instan</span>
                    <span class="text-xs font-bold text-slate-900">QRIS Dinamis</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-900 text-white tracking-widest uppercase">
                        QRIS
                    </span>
                </div>
            </div>

            <!-- Nominal yang harus dibayar -->
            <div>
                <span class="text-xs text-slate-500 block">Total Tagihan</span>
                <span class="text-2xl sm:text-3xl font-black text-slate-900 tabular-nums tracking-tight font-mono">
                    Rp {{ number_format($totalPayable, 0, ',', '.') }}
                </span>
            </div>

            <!-- Tampilan QR Code -->
            @if(!empty($qrisData['qr_image_data_uri']))
                <div class="inline-block p-3.5 bg-white rounded-2xl border border-slate-200 shadow-xs">
                    <img 
                        src="{{ $qrisData['qr_image_data_uri'] }}" 
                        alt="QRIS Pembayaran {{ $booking->reference_code }}" 
                        class="w-52 h-52 sm:w-56 sm:h-56 mx-auto object-contain"
                    />
                </div>
            @endif

            <div class="space-y-1.5 max-w-xs mx-auto">
                <p class="text-xs font-semibold text-slate-800">
                    Pindai via BCA, Mandiri, GoPay, OVO, Dana, ShopeePay
                </p>
                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Halaman akan otomatis diperbarui begitu pembayaran Anda terdeteksi lunas.
                </p>
            </div>

            <!-- Indikator Menunggu Pembayaran -->
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-100 border border-slate-200 text-xs text-slate-600">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Menunggu pembayaran masuk...</span>
            </div>

            <!-- Tombol Simulasi Pembayaran untuk Demo / Testing -->
            <div class="pt-2 border-t border-slate-100">
                <button 
                    type="button" 
                    wire:click="simulatePayment"
                    class="text-xs text-slate-400 hover:text-slate-700 transition underline cursor-pointer"
                >
                    Klik di sini untuk simulasi bayar (Demo/Testing)
                </button>
            </div>
        </div>
    @endif
</div>