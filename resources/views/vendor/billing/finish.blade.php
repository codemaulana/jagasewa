<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran &mdash; JagaSewa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg-base text-text-main font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-xl w-full bg-surface border border-border-subtle rounded-2xl shadow-subtle p-6 sm:p-8" x-data="paymentConfirmation({{ $is_pro ? 'true' : 'false' }})">
        <div class="text-center">
            <template x-if="isPro">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-success-bg text-success border border-success-border mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </template>
            <template x-if="!isPro">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-warning-bg text-warning border border-warning-border mb-4 animate-pulse">
                    <svg class="w-7 h-7 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
            </template>

            <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight" x-text="isPro ? 'Selamat, Paket Pro Aktif!' : 'Menunggu Konfirmasi Pembayaran...'"></h1>
            <p class="text-xs sm:text-sm text-text-secondary mt-2" x-text="isPro ? 'Semua batas kuota telah ditingkatkan dan seluruh fitur Pro siap digunakan untuk toko Anda.' : 'Kami sedang memverifikasi konfirmasi pembayaran dari payment gateway. Halaman akan diperbarui otomatis.'"></p>
        </div>

        <!-- Unlocked Features Summary -->
        <div class="mt-6 pt-6 border-t border-border-subtle">
            <h2 class="text-xs font-semibold text-text-secondary uppercase tracking-wider mb-3">Fitur Pro Terbuka</h2>
            <div class="space-y-2.5">
                <div class="flex items-start gap-3 p-3.5 rounded-lg bg-surface-subtle border border-border-subtle">
                    <div class="p-1 rounded bg-success-bg text-success border border-success-border mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-text-main">Barang & Booking Tanpa Batas</div>
                        <div class="text-xs text-text-secondary mt-0.5">Kapasitas katalog dan penerimaan pesanan sewa tanpa limit bulanan.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3.5 rounded-lg bg-surface-subtle border border-border-subtle">
                    <div class="p-1 rounded bg-success-bg text-success border border-success-border mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-text-main">Smart Buffer Calendar</div>
                        <div class="text-xs text-text-secondary mt-0.5">Jeda otomatis pengiriman & laundry untuk mencegah jadwal bentrok.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3.5 rounded-lg bg-surface-subtle border border-border-subtle">
                    <div class="p-1 rounded bg-success-bg text-success border border-success-border mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-text-main">Blacklist Tanpa Batas</div>
                        <div class="text-xs text-text-secondary mt-0.5">Cek identitas penyewa bermasalah secara bebas untuk perlindungan barang.</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3.5 rounded-lg bg-surface-subtle border border-border-subtle">
                    <div class="p-1 rounded bg-success-bg text-success border border-success-border mt-0.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-text-main">Multi-Foto & Ekspor Laporan CSV</div>
                        <div class="text-xs text-text-secondary mt-0.5">Unggah hingga 8 foto per item dan unduh laporan pembukuan toko.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="mt-8 flex flex-col sm:flex-row gap-3">
            <a href="/vendor" class="flex-1 min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-white bg-primary hover:bg-neutral-800 transition shadow-xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                Masuk ke Dashboard Toko
            </a>
            <a href="{{ route('vendor.billing.index') }}" class="min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-text-secondary hover:text-text-main bg-surface-subtle hover:bg-neutral-200 border border-border-subtle transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
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
