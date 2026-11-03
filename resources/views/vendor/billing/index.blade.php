<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paket & Langganan &mdash; JagaSewa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 font-sans antialiased min-h-screen">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
        <!-- Top Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-slate-200">
            <div>
                <a href="/vendor" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition py-1 group">
                    <svg class="w-4 h-4 text-slate-400 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Kembali ke Dashboard</span>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight mt-1">Paket & Langganan Toko</h1>
            </div>
            <div>
                @if($is_pro)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Paket Pro Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        Paket Free
                    </span>
                @endif
            </div>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs sm:text-sm flex items-start gap-3 shadow-2xs">
                <svg class="w-5 h-5 flex-shrink-0 text-rose-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        @endif

        @if(session('status'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs sm:text-sm flex items-start gap-3 shadow-2xs">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="font-medium">{{ session('status') }}</p>
            </div>
        @endif

        <!-- Grace Period Banner -->
        @if($in_grace_period)
            <div class="mb-6 p-5 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Masa Tenggang (Grace Period 3 Hari)</h2>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                            Masa aktif langganan Pro Anda telah selesai. Toko masih dalam masa toleransi 3 hari sebelum disesuaikan ke paket Free. Data katalog Anda tetap aman.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('vendor.billing.checkout') }}" class="w-full sm:w-auto shrink-0">
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $active_subscription?->billing_cycle ?? 'monthly' }}">
                    <input type="hidden" name="is_renewal" value="1">
                    <button type="submit" class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition whitespace-nowrap shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        Perpanjang Sekarang &rarr;
                    </button>
                </form>
            </div>
        @endif

        <!-- Near Expiration Banner (H-7 / H-1) -->
        @if($is_pro && $is_near_expiration && !$in_grace_period)
            <div class="mb-6 p-5 bg-amber-50 border border-amber-200 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-2xs">
                <div class="flex items-start gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Langganan Pro Segera Berakhir</h2>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                            Masa aktif Pro Anda tersisa <strong class="text-slate-900">{{ $days_until_expiration }} hari lagi</strong> (hingga {{ $active_subscription?->current_period_end?->translatedFormat('d F Y') }}). Perpanjangan akan menyambung otomatis dari tanggal berakhir.
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('vendor.billing.checkout') }}" class="w-full sm:w-auto shrink-0">
                    @csrf
                    <input type="hidden" name="cycle" value="{{ $active_subscription?->billing_cycle ?? 'monthly' }}">
                    <input type="hidden" name="is_renewal" value="1">
                    <button type="submit" class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition whitespace-nowrap shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                        Perpanjang Langganan &rarr;
                    </button>
                </form>
            </div>
        @endif

        <!-- Active Pro Plan Overview -->
        @if($is_pro)
            <div class="mb-8 p-5 sm:p-6 bg-white border border-slate-200/90 rounded-2xl shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Status: Paket Pro Aktif</h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Berlaku s/d: <span class="font-semibold text-slate-800">{{ $active_subscription?->current_period_end ? $active_subscription->current_period_end->translatedFormat('d F Y, H:i') . ' WIB' : 'Aktif' }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <form method="POST" action="{{ route('vendor.billing.checkout') }}" class="w-full sm:w-auto">
                        @csrf
                        <input type="hidden" name="cycle" value="{{ $active_subscription?->billing_cycle ?? 'monthly' }}">
                        <input type="hidden" name="is_renewal" value="1">
                        <button type="submit" class="w-full sm:w-auto min-h-[44px] px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                            Perpanjang Sekarang
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Pending Payment Invoice Banner -->
        @if($pending_subscription)
            <div class="mb-8 p-5 sm:p-6 bg-white border border-amber-200/90 rounded-2xl shadow-xs space-y-4">
                <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-800 border border-amber-200 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm sm:text-base font-bold text-slate-900">Tagihan Menunggu Pembayaran</h2>
                            <p class="text-xs text-slate-600 mt-0.5">
                                Tagihan paket Pro <strong class="text-slate-900">{{ ucfirst($pending_subscription->billing_cycle) }}</strong> sebesar <strong class="text-slate-900 font-mono tabular-nums">Rp {{ number_format(\App\Models\Subscription::getPlanPrice($pending_subscription->billing_cycle), 0, ',', '.') }}</strong>.
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1 font-mono">Kode Tagihan: {{ $pending_subscription->gateway_subscription_id ?: $pending_subscription->ulid }}</p>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2.5 w-full md:w-auto shrink-0">
                        <a href="#qris-payment-box" class="w-full sm:w-auto min-h-[44px] inline-flex items-center justify-center px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold rounded-xl transition text-center shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                            Lanjutkan Pembayaran
                        </a>
                        <form method="POST" action="{{ route('vendor.billing.cancel-pending') }}" class="w-full sm:w-auto">
                            @csrf
                            <button type="submit" onclick="return confirm('Batalkan tagihan tertunda ini?')" class="w-full sm:w-auto min-h-[44px] px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 hover:text-slate-900 text-xs font-medium rounded-xl transition focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                Batalkan Tagihan
                            </button>
                        </form>
                    </div>
                </div>

                @if(!empty($pending_qris['qr_image_data_uri']))
                    <div id="qris-payment-box" class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center gap-6 bg-slate-50/80 p-5 rounded-xl border border-slate-200">
                        <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-xs shrink-0">
                            <img src="{{ $pending_qris['qr_image_data_uri'] }}" alt="QRIS Pembayaran Pro" class="w-40 h-40 mx-auto object-contain" />
                        </div>
                        <div class="text-center sm:text-left space-y-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-900 text-white tracking-widest uppercase">QRIS Dinamis</span>
                            <h3 class="text-xs sm:text-sm font-bold text-slate-900">Pindai QRIS untuk Aktivasi Otomatis</h3>
                            <p class="text-xs text-slate-600 leading-relaxed max-w-md">
                                Buka aplikasi m-Banking (BCA, Mandiri, BRI, BNI) atau e-Wallet (GoPay, OVO, Dana) Anda dan pindai kode QR di atas.
                            </p>
                            <p class="text-xs text-slate-500 pt-1">Total Pembayaran: <span class="font-bold text-slate-900 font-mono text-sm">Rp {{ number_format($pending_qris['amount'] ?? 0, 0, ',', '.') }}</span></p>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Value Proposition Quote Card -->
        <div class="mb-8 p-4 bg-white border border-slate-200/90 rounded-2xl shadow-xs">
            <p class="text-xs text-slate-500 italic text-center">
                &ldquo;{{ config('plans.value_proposition') }}&rdquo;
            </p>
        </div>

        <!-- Usage vs Limits (Simple Progress Bars) -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 mb-8 shadow-xs space-y-4">
            <h2 class="text-sm sm:text-base font-bold text-slate-900">Pemakaian vs Batas Paket Toko</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Active Items -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/60">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-slate-600">Barang Aktif di Katalog</span>
                        <span class="text-xs font-bold text-slate-900 tabular-nums">{{ $limits['active_items']['used'] }} / {{ $limits['active_items']['max'] }}</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                        @php
                            $usedItems = $limits['active_items']['used'];
                            $pctItems = $is_pro ? 20 : min(100, ($usedItems / 15) * 100);
                        @endphp
                        <div class="h-2 rounded-full {{ $is_pro ? 'bg-emerald-500' : ($pctItems >= 100 ? 'bg-amber-500' : 'bg-blue-600') }}" style="width: {{ $pctItems }}%"></div>
                    </div>
                    @if(!$limits['active_items']['can_add'] && !$is_pro)
                        <p class="text-[11px] text-amber-700 mt-2 font-medium">Batas kuota 15 barang tercapai. Upgrade ke Pro untuk menambah barang tanpa batas.</p>
                    @endif
                </div>

                <!-- Blacklist Entries -->
                <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/60">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-slate-600">Catatan Blacklist Pribadi</span>
                        <span class="text-xs font-bold text-slate-900 tabular-nums">{{ $limits['blacklist_entries']['used'] }} / {{ $limits['blacklist_entries']['max'] }}</span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                        @php
                            $usedBL = $limits['blacklist_entries']['used'];
                            $pctBL = $is_pro ? 20 : min(100, ($usedBL / 5) * 100);
                        @endphp
                        <div class="h-2 rounded-full {{ $is_pro ? 'bg-emerald-500' : ($pctBL >= 100 ? 'bg-amber-500' : 'bg-blue-600') }}" style="width: {{ $pctBL }}%"></div>
                    </div>
                    @if(!$limits['blacklist_entries']['can_add'] && !$is_pro)
                        <p class="text-[11px] text-amber-700 mt-2 font-medium">Batas kuota 5 entri blacklist tercapai. Upgrade ke Pro untuk kuota tanpa batas.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pricing Options Grid -->
        <div class="mb-12">
            <div class="mb-6">
                <h2 class="text-base sm:text-lg font-bold text-slate-900">Pilihan Siklus Langganan Pro</h2>
                <p class="text-xs text-slate-500 mt-0.5">Pilih siklus pembayaran yang sesuai dengan kebutuhan operasional rental Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Bulanan -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Bulanan</div>
                        <div class="flex items-baseline mb-3">
                            <span class="text-2xl font-bold text-slate-900 tabular-nums">Rp 15.000</span>
                            <span class="text-xs text-slate-500 ml-1">/ bulan</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-6 leading-relaxed">Siklus fleksibel per 30 hari untuk toko rental aktif.</p>
                    </div>
                    <div>
                        @if($is_pro && $active_subscription?->billing_cycle === 'monthly')
                            <div class="w-full min-h-[44px] flex items-center justify-center gap-1.5 py-2.5 px-4 bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-center rounded-xl text-xs">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Paket Aktif Saat Ini</span>
                            </div>
                        @elseif($is_pro)
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="monthly">
                                <input type="hidden" name="is_renewal" value="1">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl text-xs transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Ganti ke Bulanan
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="monthly">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Pilih Bulanan (Rp 15.000)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- 3 Bulan -->
                <div class="bg-white rounded-2xl border-2 border-blue-600 p-5 sm:p-6 flex flex-col justify-between shadow-xs relative">
                    <span class="absolute -top-3 right-5 bg-blue-600 text-white text-[10px] font-bold py-0.5 px-3 rounded-full uppercase tracking-wider shadow-2xs">Pilihan Populer</span>
                    <div>
                        <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">3 Bulan</div>
                        <div class="flex items-baseline mb-3">
                            <span class="text-2xl font-bold text-slate-900 tabular-nums">Rp 39.000</span>
                            <span class="text-xs text-slate-500 ml-1">/ 3 bulan</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-6 leading-relaxed">Siklus 90 hari dengan kenyamanan perpanjangan triwulanan.</p>
                    </div>
                    <div>
                        @if($is_pro && $active_subscription?->billing_cycle === 'quarterly')
                            <div class="w-full min-h-[44px] flex items-center justify-center gap-1.5 py-2.5 px-4 bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-center rounded-xl text-xs">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Paket Aktif Saat Ini</span>
                            </div>
                        @elseif($is_pro)
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="quarterly">
                                <input type="hidden" name="is_renewal" value="1">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl text-xs transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Ganti ke 3 Bulan
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="quarterly">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                                    Pilih 3 Bulan (Rp 39.000)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- 1 Tahun -->
                <div class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 flex flex-col justify-between shadow-xs">
                    <div>
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">1 Tahun</div>
                        <div class="flex items-baseline mb-3">
                            <span class="text-2xl font-bold text-slate-900 tabular-nums">Rp 129.000</span>
                            <span class="text-xs text-slate-500 ml-1">/ tahun</span>
                        </div>
                        <p class="text-xs text-slate-600 mb-6 leading-relaxed">Siklus 365 hari penuh tanpa perlu repot perpanjangan bulanan.</p>
                    </div>
                    <div>
                        @if($is_pro && $active_subscription?->billing_cycle === 'annually')
                            <div class="w-full min-h-[44px] flex items-center justify-center gap-1.5 py-2.5 px-4 bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold text-center rounded-xl text-xs">
                                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Paket Aktif Saat Ini</span>
                            </div>
                        @elseif($is_pro)
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="annually">
                                <input type="hidden" name="is_renewal" value="1">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-white hover:bg-slate-50 border border-slate-200 text-slate-800 font-semibold rounded-xl text-xs transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Ganti ke 1 Tahun
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                                @csrf
                                <input type="hidden" name="cycle" value="annually">
                                <button type="submit" class="w-full min-h-[44px] py-2.5 px-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition shadow-xs focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                                    Pilih 1 Tahun (Rp 129.000)
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Perbandingan Lengkap Free vs Premium -->
        <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden mb-12 shadow-xs">
            <div class="p-5 sm:p-6 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Perbandingan Fitur Paket</h2>
                <p class="text-xs text-slate-500 mt-0.5">Ringkasan hak akses dan batasan operasional untuk setiap paket.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 border-b border-slate-200">
                            <th class="py-3 px-4 font-bold">Fitur & Layanan</th>
                            <th class="py-3 px-4 font-bold w-1/3">Paket Free</th>
                            <th class="py-3 px-4 font-bold w-1/3 text-blue-700 bg-blue-50/50">Paket Pro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Jumlah Barang di Katalog</td>
                            <td class="py-3.5 px-4 text-slate-600">Maksimal 15 barang aktif</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Tanpa batas wajar</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Jumlah Booking per Bulan</td>
                            <td class="py-3.5 px-4 text-slate-600">Maksimal 20 booking / bulan</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Tanpa batas wajar</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Foto per Barang</td>
                            <td class="py-3.5 px-4 text-slate-600">Hingga 5 foto</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Hingga 8 foto (multi-foto)</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Kalender Sewa</td>
                            <td class="py-3.5 px-4 text-slate-600">Kalender dasar (tampilan bulanan)</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Smart Buffer Calendar (jeda otomatis)</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Pemeriksaan Blacklist</td>
                            <td class="py-3.5 px-4 text-slate-600">Maksimal 10 pengecekan / bulan</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Tanpa batas & lapor blacklist bersama</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Kalkulasi Denda & Deposit</td>
                            <td class="py-3.5 px-4 text-slate-600">Hitung denda manual</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Kalkulator otomatis (potong deposit)</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Dokumen PDF & Notifikasi</td>
                            <td class="py-3.5 px-4 text-slate-600">Template WhatsApp standar</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Invoice PDF, surat perjanjian, kustom template</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Laporan & Ekspor Data</td>
                            <td class="py-3.5 px-4 text-slate-600">Tampilan ringkasan dashboard</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Ekspor laporan pendapatan CSV/Excel</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Pengguna & Staf Toko</td>
                            <td class="py-3.5 px-4 text-slate-600">1 pengguna (pemilik)</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Beberapa staf dengan peran berbeda</td>
                        </tr>
                        <tr>
                            <td class="py-3.5 px-4 font-semibold text-slate-900">Dukungan Pelanggan</td>
                            <td class="py-3.5 px-4 text-slate-600">Bantuan standar</td>
                            <td class="py-3.5 px-4 text-slate-900 font-semibold bg-blue-50/30">Dukungan prioritas langsung</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Riwayat Pembayaran & Transaksi -->
        <div class="bg-white rounded-2xl border border-slate-200/90 overflow-hidden shadow-xs">
            <div class="p-5 sm:p-6 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">Riwayat Pembayaran & Transaksi</h2>
                <p class="text-xs text-slate-500 mt-0.5">Catatan seluruh transaksi tagihan langganan toko Anda.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 border-b border-slate-200">
                            <th class="py-3 px-4 font-semibold">Tanggal</th>
                            <th class="py-3 px-4 font-semibold">Paket / Siklus</th>
                            <th class="py-3 px-4 font-semibold">Nominal</th>
                            <th class="py-3 px-4 font-semibold">Status</th>
                            <th class="py-3 px-4 font-semibold">Masa Berlaku</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payment_history as $hist)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3.5 px-4 text-slate-600 tabular-nums">{{ $hist->created_at->translatedFormat('d M Y, H:i') }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900">{{ ucfirst($hist->plan) }} ({{ ucfirst($hist->billing_cycle ?? 'Bulanan') }})</td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 tabular-nums">Rp {{ number_format(\App\Models\Subscription::getPlanPrice($hist->billing_cycle ?? 'monthly'), 0, ',', '.') }}</td>
                                <td class="py-3.5 px-4">
                                    @if($hist->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">Aktif</span>
                                    @elseif($hist->status === 'pending_payment')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Menunggu Pembayaran</span>
                                    @elseif($hist->status === 'cancelled')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">Dibatalkan</span>
                                    @elseif($hist->status === 'expired')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">Kedaluwarsa</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">{{ ucfirst($hist->status) }}</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 tabular-nums">
                                    {{ $hist->current_period_end ? $hist->current_period_end->translatedFormat('d M Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-slate-400">
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