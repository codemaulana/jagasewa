<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket & Langganan &mdash; JagaSewa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg-base text-text-main font-sans antialiased min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
        <!-- Top Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-border-subtle">
            <div>
                <a href="/vendor" class="inline-flex items-center gap-1.5 text-xs font-semibold text-text-secondary hover:text-text-main transition py-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Dashboard</span>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight mt-1">Paket & Langganan Toko</h1>
            </div>
            <div>
                @if($is_pro)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-success-bg text-success border border-success-border">
                        <span class="w-2 h-2 rounded-full bg-success"></span>
                        Paket Pro Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-neutral-status-bg text-neutral-status-text border border-neutral-status-border">
                        <span class="w-2 h-2 rounded-full bg-text-muted"></span>
                        Paket Free
                    </span>
                @endif
            </div>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 bg-danger-bg border border-danger-border text-danger rounded-xl text-sm flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        @endif

        @if(session('status'))
            <div class="mb-6 p-4 bg-success-bg border border-success-border text-success rounded-xl text-sm flex items-start gap-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="font-medium">{{ session('status') }}</p>
            </div>
        @endif

        <!-- Grace Period Banner -->
        @if($in_grace_period)
            <div class="mb-6 p-5 bg-warning-bg border border-warning-border rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-surface border border-warning-border text-warning mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-text-main">Masa Tenggang (Grace Period 3 Hari)</h2>
                        <p class="text-xs text-text-secondary mt-0.5">
                            Masa aktif langganan Pro Anda telah selesai. Toko masih dalam masa toleransi 3 hari sebelum diturunkan ke paket Free. Data berlebih tetap aman dan tidak akan dihapus.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('vendor.billing.checkout') }}" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $active_subscription?->billing_cycle ?? 'monthly' }}">
                    <input type="hidden" name="is_renewal" value="1">
                    <button type="submit" class="w-full sm:w-auto min-h-[44px] px-4 py-2.5 bg-primary hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                        Perpanjang Sekarang
                    </button>
                </form>
            </div>
        @endif

        <!-- Near Expiration Banner (H-7 / H-1) -->
        @if($is_pro && $is_near_expiration && !$in_grace_period)
            <div class="mb-6 p-5 bg-warning-bg border border-warning-border rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-surface border border-warning-border text-warning mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-text-main">Langganan Pro Segera Berakhir</h2>
                        <p class="text-xs text-text-secondary mt-0.5">
                            Masa aktif Pro Anda tersisa {{ $days_until_expiration }} hari lagi (hingga {{ $active_subscription?->current_period_end?->translatedFormat('d F Y') }}). Perpanjangan akan menyambung langsung dari tanggal berakhir.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('vendor.billing.checkout') }}" class="w-full sm:w-auto">
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $active_subscription?->billing_cycle ?? 'monthly' }}">
                    <input type="hidden" name="is_renewal" value="1">
                    <button type="submit" class="w-full sm:w-auto min-h-[44px] px-4 py-2.5 bg-primary hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition whitespace-nowrap focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                        Perpanjang Langganan
                    </button>
                </form>
            </div>
        @endif

        <!-- Active Pro Plan Overview -->
        @if($is_pro)
            <div class="mb-8 p-5 sm:p-6 bg-surface border border-border-subtle rounded-xl shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="p-2 rounded-lg bg-success-bg text-success border border-success-border">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-text-main">Status: Paket Pro Aktif</h2>
                            <p class="text-xs text-text-secondary mt-0.5">
                                Berlaku s/d: <span class="font-semibold text-text-main">{{ $active_subscription?->current_period_end ? $active_subscription->current_period_end->translatedFormat('d F Y, H:i') . ' WIB' : 'Aktif' }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <form method="POST" action="{{ route('vendor.billing.checkout') }}" class="w-full sm:w-auto">
                        @csrf
                        <input type="hidden" name="cycle" value="{{ $active_subscription?->billing_cycle ?? 'monthly' }}">
                        <input type="hidden" name="is_renewal" value="1">
                        <button type="submit" class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 bg-primary hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                            Perpanjang Sekarang
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Pending Payment Invoice Banner -->
        @if($pending_subscription)
            <div class="mb-8 p-5 bg-warning-bg border border-warning-border rounded-xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-surface border border-warning-border text-warning mt-0.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-text-main">Tagihan Menunggu Pembayaran</h2>
                        <p class="text-xs text-text-secondary mt-0.5">
                            Tagihan paket Pro <span class="font-semibold">{{ ucfirst($pending_subscription->billing_cycle) }}</span> sebesar <span class="font-bold tabular-nums">Rp {{ number_format(\App\Models\Subscription::getPlanPrice($pending_subscription->billing_cycle), 0, ',', '.') }}</span>.
                        </p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                    @if($pending_subscription->gateway_subscription_id)
                        <a href="https://sandbox.mayar.id/pay/{{ $pending_subscription->gateway_subscription_id }}" target="_blank" rel="noopener noreferrer" class="w-full sm:w-auto min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 bg-primary hover:bg-neutral-800 text-white text-xs font-semibold rounded-lg transition text-center focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                            Lanjutkan Pembayaran
                        </a>
                    @endif
                    <form method="POST" action="{{ route('vendor.billing.cancel-pending') }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" onclick="return confirm('Batalkan tagihan tertunda ini?')" class="w-full sm:w-auto min-h-[44px] px-4 py-2.5 bg-surface border border-border-subtle hover:bg-surface-subtle text-text-secondary hover:text-text-main text-xs font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                            Batalkan Tagihan
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Value Proposition Quote Card -->
        <div class="mb-8 p-4 bg-surface border border-border-subtle rounded-xl shadow-xs">
            <p class="text-xs text-text-secondary italic text-center">
                &ldquo;{{ config('plans.value_proposition') }}&rdquo;
            </p>
        </div>

        <!-- Usage vs Limits (Simple Progress Bars) -->
        <div class="bg-surface rounded-xl border border-border-subtle p-5 sm:p-6 mb-8 shadow-xs">
            <h2 class="text-sm font-bold text-text-main mb-4">Pemakaian vs Batas Paket Toko</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Active Items -->
                <div class="border border-border-subtle rounded-lg p-4 bg-surface-subtle">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-text-secondary">Barang Aktif di Katalog</span>
                        <span class="text-xs font-bold text-text-main tabular-nums">{{ $limits['active_items']['used'] }} / {{ $limits['active_items']['max'] }}</span>
                    </div>
                    <div class="w-full bg-border-subtle rounded-full h-2 overflow-hidden">
                        @php
                            $usedItems = $limits['active_items']['used'];
                            $pctItems = $is_pro ? 20 : min(100, ($usedItems / 15) * 100);
                        @endphp
                        <div class="h-2 rounded-full {{ $is_pro ? 'bg-success' : ($pctItems >= 100 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $pctItems }}%"></div>
                    </div>
                    @if(!$limits['active_items']['can_add'] && !$is_pro)
                        <p class="text-[11px] text-warning mt-2 font-medium">Batas kuota 15 barang tercapai. Upgrade ke Pro untuk menambah barang tanpa batas.</p>
                    @endif
                </div>

                <!-- Blacklist Entries -->
                <div class="border border-border-subtle rounded-lg p-4 bg-surface-subtle">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-text-secondary">Catatan Blacklist Pribadi</span>
                        <span class="text-xs font-bold text-text-main tabular-nums">{{ $limits['blacklist_entries']['used'] }} / {{ $limits['blacklist_entries']['max'] }}</span>
                    </div>
                    <div class="w-full bg-border-subtle rounded-full h-2 overflow-hidden">
                        @php
                            $usedBL = $limits['blacklist_entries']['used'];
                            $pctBL = $is_pro ? 20 : min(100, ($usedBL / 5) * 100);
                        @endphp
                        <div class="h-2 rounded-full {{ $is_pro ? 'bg-success' : ($pctBL >= 100 ? 'bg-warning' : 'bg-primary') }}" style="width: {{ $pctBL }}%"></div>
                    </div>
                    @if(!$limits['blacklist_entries']['can_add'] && !$is_pro)
                        <p class="text-[11px] text-warning mt-2 font-medium">Batas kuota 5 entri blacklist tercapai. Upgrade ke Pro untuk kuota tanpa batas.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pricing Options Grid -->
        <div class="mb-12">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-text-main">Pilihan Siklus Langganan Pro</h2>
                <p class="text-xs text-text-secondary mt-0.5">Pilih siklus pembayaran yang sesuai dengan kebutuhan operasional rental Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Bulanan -->
                <div class="bg-surface rounded-xl border border-border-subtle p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-1">Bulanan</div>
                        <div class="flex items-baseline mb-3">
                            <span class="text-2xl font-bold text-text-main tabular-nums">Rp 15.000</span>
                            <span class="text-xs text-text-secondary ml-1">/ bulan</span>
                        </div>
                        <p class="text-xs text-text-secondary mb-4">Siklus fleksibel per 30 hari untuk toko aktif.</p>
                    </div>
                    <div>
                        @if($is_pro && $active_subscription?->billing_cycle === 'monthly')
                            <div class="w-full min-h-[44px] flex items-center justify-center gap-1.5 py-2.5 px-4 bg-success-bg border border-success-border text-success font-semibold text-center rounded-lg text-xs">
                                <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Paket Aktif Saat Ini</span>
                            </div>
                        @elseif($is_pro)
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="monthly">
                                <input type="hidden" name="is_renewal" value="1">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-surface-subtle hover:bg-neutral-200 border border-border-subtle text-text-main font-semibold rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                                    Ganti ke Bulanan
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="monthly">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-primary hover:bg-neutral-800 text-white font-semibold rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                                    Pilih Bulanan (Rp 15.000)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- 3 Bulan -->
                <div class="bg-surface rounded-xl border-2 border-primary p-5 flex flex-col justify-between shadow-xs relative">
                    <span class="absolute -top-2.5 right-4 bg-primary text-white text-[10px] font-bold py-0.5 px-2.5 rounded-full uppercase tracking-wider">Pilihan Populer</span>
                    <div>
                        <div class="text-xs font-bold text-text-main uppercase tracking-wider mb-1">3 Bulan</div>
                        <div class="flex items-baseline mb-3">
                            <span class="text-2xl font-bold text-text-main tabular-nums">Rp 39.000</span>
                            <span class="text-xs text-text-secondary ml-1">/ 3 bulan</span>
                        </div>
                        <p class="text-xs text-text-secondary mb-4">Siklus 90 hari dengan kenyamanan perpanjangan triwulanan.</p>
                    </div>
                    <div>
                        @if($is_pro && $active_subscription?->billing_cycle === 'quarterly')
                            <div class="w-full min-h-[44px] flex items-center justify-center gap-1.5 py-2.5 px-4 bg-success-bg border border-success-border text-success font-semibold text-center rounded-lg text-xs">
                                <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Paket Aktif Saat Ini</span>
                            </div>
                        @elseif($is_pro)
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="quarterly">
                                <input type="hidden" name="is_renewal" value="1">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-surface-subtle hover:bg-neutral-200 border border-border-subtle text-text-main font-semibold rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                                    Ganti ke 3 Bulan
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="quarterly">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-primary hover:bg-neutral-800 text-white font-semibold rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                                    Pilih 3 Bulan (Rp 39.000)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- 1 Tahun -->
                <div class="bg-surface rounded-xl border border-border-subtle p-5 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="text-xs font-bold text-text-secondary uppercase tracking-wider mb-1">1 Tahun</div>
                        <div class="flex items-baseline mb-3">
                            <span class="text-2xl font-bold text-text-main tabular-nums">Rp 129.000</span>
                            <span class="text-xs text-text-secondary ml-1">/ tahun</span>
                        </div>
                        <p class="text-xs text-text-secondary mb-4">Siklus 365 hari penuh tanpa perlu repot perpanjangan bulanan.</p>
                    </div>
                    <div>
                        @if($is_pro && $active_subscription?->billing_cycle === 'annually')
                            <div class="w-full min-h-[44px] flex items-center justify-center gap-1.5 py-2.5 px-4 bg-success-bg border border-success-border text-success font-semibold text-center rounded-lg text-xs">
                                <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Paket Aktif Saat Ini</span>
                            </div>
                        @elseif($is_pro)
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="annually">
                                <input type="hidden" name="is_renewal" value="1">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-surface-subtle hover:bg-neutral-200 border border-border-subtle text-text-main font-semibold rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                                    Ganti ke 1 Tahun
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="annually">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-primary hover:bg-neutral-800 text-white font-semibold rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                                    Pilih 1 Tahun (Rp 129.000)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Perbandingan Lengkap Free vs Premium (Section 6) -->
        <div class="bg-surface rounded-xl border border-border-subtle overflow-hidden mb-12 shadow-xs">
            <div class="p-5 border-b border-border-subtle">
                <h2 class="text-base font-bold text-text-main">Perbandingan Fitur Paket</h2>
                <p class="text-xs text-text-secondary mt-0.5">Ringkasan hak akses dan batasan operasional untuk setiap paket.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-surface-subtle text-text-main border-b border-border-subtle">
                            <th class="p-3.5 font-bold">Fitur & Layanan</th>
                            <th class="p-3.5 font-bold w-1/3">Paket Free</th>
                            <th class="p-3.5 font-bold w-1/3 text-primary bg-accent-lime-subtle/50">Paket Pro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Jumlah Barang di Katalog</td>
                            <td class="p-3.5 text-text-secondary">Maksimal 15 barang aktif</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Tanpa batas wajar</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Jumlah Booking per Bulan</td>
                            <td class="p-3.5 text-text-secondary">Maksimal 20 booking / bulan</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Tanpa batas wajar</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Foto per Barang</td>
                            <td class="p-3.5 text-text-secondary">Hingga 5 foto</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Hingga 8 foto (multi-foto)</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Kalender Sewa</td>
                            <td class="p-3.5 text-text-secondary">Kalender dasar (tampilan bulanan)</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Smart Buffer Calendar (jeda otomatis)</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Pemeriksaan Blacklist</td>
                            <td class="p-3.5 text-text-secondary">Maksimal 10 pengecekan / bulan</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Tanpa batas & lapor blacklist bersama</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Kalkulasi Denda & Deposit</td>
                            <td class="p-3.5 text-text-secondary">Hitung denda manual</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Kalkulator otomatis (potong deposit)</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Dokumen PDF & Notifikasi</td>
                            <td class="p-3.5 text-text-secondary">Template WhatsApp standar</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Invoice PDF, surat perjanjian, kustom template</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Laporan & Ekspor Data</td>
                            <td class="p-3.5 text-text-secondary">Tampilan ringkasan dashboard</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Ekspor laporan pendapatan CSV/Excel</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Pengguna & Staf Toko</td>
                            <td class="p-3.5 text-text-secondary">1 pengguna (pemilik)</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Beberapa staf dengan peran berbeda</td>
                        </tr>
                        <tr>
                            <td class="p-3.5 font-semibold text-text-main">Dukungan Pelanggan</td>
                            <td class="p-3.5 text-text-secondary">Bantuan standar</td>
                            <td class="p-3.5 text-text-main font-semibold bg-accent-lime-subtle/30">Dukungan prioritas langsung</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Pembayaran & Transaksi (Section 5) -->
        <div class="bg-surface rounded-xl border border-border-subtle overflow-hidden shadow-xs">
            <div class="p-5 border-b border-border-subtle">
                <h2 class="text-base font-bold text-text-main">Riwayat Pembayaran & Transaksi</h2>
                <p class="text-xs text-text-secondary mt-0.5">Catatan seluruh transaksi tagihan langganan toko Anda.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-surface-subtle text-text-main border-b border-border-subtle">
                            <th class="p-3.5 font-semibold">Tanggal</th>
                            <th class="p-3.5 font-semibold">Paket / Siklus</th>
                            <th class="p-3.5 font-semibold">Nominal</th>
                            <th class="p-3.5 font-semibold">Status</th>
                            <th class="p-3.5 font-semibold">Masa Berlaku</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle">
                        @forelse($payment_history as $hist)
                            <tr>
                                <td class="p-3.5 text-text-secondary tabular-nums">{{ $hist->created_at->translatedFormat('d M Y, H:i') }}</td>
                                <td class="p-3.5 font-medium text-text-main">{{ ucfirst($hist->plan) }} ({{ ucfirst($hist->billing_cycle ?? 'Bulanan') }})</td>
                                <td class="p-3.5 font-mono text-text-main tabular-nums">Rp {{ number_format(\App\Models\Subscription::getPlanPrice($hist->billing_cycle ?? 'monthly'), 0, ',', '.') }}</td>
                                <td class="p-3.5">
                                    @if($hist->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold bg-success-bg text-success border border-success-border">Aktif</span>
                                    @elseif($hist->status === 'pending_payment')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold bg-warning-bg text-warning border border-warning-border">Menunggu Pembayaran</span>
                                    @elseif($hist->status === 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold bg-neutral-status-bg text-neutral-status-text border border-neutral-status-border">Dibatalkan</span>
                                    @elseif($hist->status === 'expired')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold bg-neutral-status-bg text-neutral-status-text border border-neutral-status-border">Kedaluwarsa</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-semibold bg-neutral-status-bg text-neutral-status-text border border-neutral-status-border">{{ ucfirst($hist->status) }}</span>
                                    @endif
                                </td>
                                <td class="p-3.5 text-text-secondary tabular-nums">
                                    {{ $hist->current_period_end ? $hist->current_period_end->translatedFormat('d M Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-8 text-center text-xs text-text-muted">
                                    Belum ada catatan riwayat transaksi langganan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
