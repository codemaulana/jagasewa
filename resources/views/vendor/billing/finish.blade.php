<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran &mdash; JagaSewa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-xl w-full bg-white border border-slate-200/90 rounded-3xl shadow-sm p-6 sm:p-8" x-data="paymentConfirmation({{ $is_pro ? 'true' : 'false' }})">
        <div class="text-center">
            <template x-if="isPro">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-700 border border-emerald-200 mb-4 shadow-2xs">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </template>
            <template x-if="!isPro">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-amber-50 text-amber-700 border border-amber-200 mb-4 shadow-2xs">
                    <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </template>

            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight" x-text="isPro ? 'Selamat, Paket Pro Aktif!' : 'Menunggu Konfirmasi Pembayaran...'"></h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 leading-relaxed" x-text="isPro ? 'Semua batas kuota telah ditingkatkan dan seluruh fitur Pro siap digunakan untuk operasional toko Anda.' : 'Kami sedang memverifikasi notifikasi pembayaran QRIS Anda. Halaman akan diperbarui otomatis begitu pembayaran terkonfirmasi.'"></p>
        </div>

        <!-- Unlocked Features Summary -->
        <div class="mt-6 pt-6 border-t border-slate-100">
            <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3.5">Fitur Pro Terbuka</h2>
            <div class="space-y-2.5">
                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 border border-slate-200">
                    <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">Barang & Booking Tanpa Batas</div>
                        <div class="text-xs text-slate-500 mt-0.5">Kapasitas katalog dan penerimaan pesanan sewa tanpa limit bulanan.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 border border-slate-200">
                    <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">Smart Buffer Calendar</div>
                        <div class="text-xs text-slate-500 mt-0.5">Jeda otomatis pengiriman & laundry untuk mencegah jadwal bentrok.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 border border-slate-200">
                    <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">Blacklist Tanpa Batas</div>
                        <div class="text-xs text-slate-500 mt-0.5">Cek identitas penyewa bermasalah secara bebas untuk perlindungan barang.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 border border-slate-200">
                    <div class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">Multi-Foto & Ekspor Laporan CSV</div>
                        <div class="text-xs text-slate-500 mt-0.5">Unggah hingga 8 foto per item dan unduh laporan pembukuan toko.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <a href="/vendor" class="flex-1 min-h-[44px] inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 transition shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                Masuk ke Dashboard Toko
            </a>
            <a href="{{ route('vendor.billing.index') }}" class="min-h-[44px] inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-700 hover:text-slate-900 bg-white hover:bg-slate-50 border border-slate-200 transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                Lihat Paket & Kuota
            </a>
        </div>
    </div>

    <script>
        function paymentConfirmation(initialIsPro) {
            return {
                isPro: initialIsPro,
                interval: null,
                init() {
                    if (!this.isPro) {
                        this.interval = setInterval(() => {
                            fetch('{{ route('vendor.entitlements') }}', {
                                headers: { 'Accept': 'application/json' }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data && data.is_pro) {
                                    this.isPro = true;
                                    clearInterval(this.interval);
                                }
                            })
                            .catch(() => {});
                        }, 2500);
                    }
                }
            };
        }
    </script>
</body>
</html>