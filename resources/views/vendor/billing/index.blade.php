<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Langganan & Paket — JagaSewa</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 antialiased min-h-screen">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Top Navigation -->
        <div class="flex items-center justify-between mb-8 pb-4 border-b">
            <div>
                <a href="/vendor" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">← Kembali ke Dashboard</a>
                <h1 class="text-2xl font-bold text-gray-900 mt-1">Paket & Langganan Toko</h1>
            </div>
            <div>
                @if($is_pro)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-emerald-100 text-emerald-800">
                        💎 Paket Pro Aktif
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-gray-200 text-gray-700">
                        🟢 Paket Free
                    </span>
                @endif
            </div>
        </div>

        @if(session('error'))
            <div class="mb-6 p-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 rounded">
                <p class="font-medium">{{ session('error') }}</p>
            </div>
        @endif

        @if(session('status'))
            <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded">
                <p class="font-medium">{{ session('status') }}</p>
            </div>
        @endif

        <!-- Active Pro Plan Banner -->
        @if($is_pro)
            <div class="mb-6 p-5 bg-gradient-to-r from-emerald-600 to-teal-700 text-white rounded-xl shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">💎</span>
                        <h2 class="text-xl font-bold">Status: Paket Pro Aktif</h2>
                    </div>
                    <p class="text-emerald-100 text-sm mt-1">
                        Masa aktif berlaku hingga: <b>{{ $active_subscription?->current_period_end ? $active_subscription->current_period_end->translatedFormat('d F Y, H:i') . ' WIB' : 'Aktif Tanpa Batas' }}</b>
                    </p>
                </div>
                <a href="/vendor" class="px-4 py-2 bg-white text-emerald-800 text-sm font-semibold rounded-lg shadow hover:bg-emerald-50 transition">
                    Kelola Toko di Dashboard
                </a>
            </div>
        @endif

        <!-- Pending Payment Invoice Banner -->
        @if(!$is_pro && $pending_subscription)
            <div class="mb-6 p-5 bg-amber-50 border-2 border-amber-400 text-amber-900 rounded-xl shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">⏳</span>
                        <h2 class="text-lg font-bold">Tagihan Menunggu Pembayaran</h2>
                    </div>
                    <p class="text-sm text-amber-800 mt-1">
                        Anda memiliki tagihan paket Pro <b>{{ ucfirst($pending_subscription->billing_cycle) }}</b> sebesar <b>Rp {{ number_format(\App\Models\Subscription::getPlanPrice($pending_subscription->billing_cycle), 0, ',', '.') }}</b>.
                    </p>
                </div>
                <div class="flex items-center gap-2 w-full md:w-auto">
                    @if($pending_subscription->gateway_subscription_id)
                        <a href="https://sandbox.mayar.id/pay/{{ $pending_subscription->gateway_subscription_id }}" target="_blank" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-lg shadow transition text-center flex-1 md:flex-initial">
                            💳 Lanjutkan Pembayaran
                        </a>
                    @endif
                    <form method="POST" action="{{ route('vendor.billing.cancel-pending') }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Apakah Anda yakin ingin membatalkan tagihan tertunda ini?')" class="px-4 py-2 bg-white border border-gray-300 hover:bg-gray-100 text-gray-700 text-sm font-medium rounded-lg transition text-center">
                            Batalkan Tagihan
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Current Usage Limits -->
        <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Penggunaan Kuota Toko Anda</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">Barang Aktif</span>
                        <span class="text-sm font-bold text-gray-900">{{ $limits['active_items']['used'] }} / {{ $limits['active_items']['max'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $is_pro ? '20%' : min(100, ($limits['active_items']['used'] / 5) * 100) }}%"></div>
                    </div>
                    @if(!$limits['active_items']['can_add'])
                        <p class="text-xs text-red-600 mt-2">Batas 5 barang aktif tercapai. Upgrade ke Pro untuk kuota tanpa batas.</p>
                    @endif
                </div>

                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium text-gray-600">Blacklist Pribadi</span>
                        <span class="text-sm font-bold text-gray-900">{{ $limits['blacklist_entries']['used'] }} / {{ $limits['blacklist_entries']['max'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: {{ $is_pro ? '20%' : min(100, ($limits['blacklist_entries']['used'] / 5) * 100) }}%"></div>
                    </div>
                    @if(!$limits['blacklist_entries']['can_add'])
                        <p class="text-xs text-red-600 mt-2">Batas 5 entri blacklist tercapai. Upgrade ke Pro untuk kuota tanpa batas.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pricing Cards -->
        <div class="mb-8">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Pilih Paket Terbaik untuk Bisnis Rental Anda</h2>
                <p class="text-gray-600 text-sm mt-1">Hanya seharga segelas es teh, amankan aset rental Anda tanpa rasa khawatir.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Monthly Plan -->
                <div class="bg-white rounded-xl shadow-sm border p-6 flex flex-col justify-between">
                    <div>
                        <div class="text-sm font-semibold text-emerald-600 uppercase tracking-wider mb-2">Bulanan</div>
                        <div class="flex items-baseline mb-4">
                            <span class="text-3xl font-extrabold text-gray-900">Rp 15.000</span>
                            <span class="text-gray-500 text-sm ml-1">/ bulan</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-6">Cocok untuk vendor yang ingin mencoba performa fitur Pro.</p>
                        <ul class="text-sm text-gray-600 space-y-2 mb-6">
                            <li>✓ Unlimited barang aktif</li>
                            <li>✓ White-label (tanpa watermark)</li>
                            <li>✓ Kustom template chat WhatsApp</li>
                            <li>✓ 6–8 foto per barang</li>
                            <li>✓ Export laporan Excel/CSV</li>
                        </ul>
                    </div>
                    @if($is_pro)
                        <div class="w-full py-2.5 px-4 bg-emerald-50 border border-emerald-300 text-emerald-800 font-semibold text-center rounded-lg text-sm">
                            ✓ Paket Aktif Saat Ini
                        </div>
                    @elseif($pending_subscription && $pending_subscription->billing_cycle === 'monthly')
                        <div class="w-full py-2.5 px-4 bg-amber-100 border border-amber-300 text-amber-800 font-semibold text-center rounded-lg text-sm">
                            ⏳ Menunggu Pembayaran
                        </div>
                    @else
                        <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                            @csrf
                            <input type="hidden" name="cycle" value="monthly">
                            <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition cursor-pointer">
                                Pilih Bulanan (Rp 15.000)
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Quarterly Plan (Popular) -->
                <div class="bg-white rounded-xl shadow-md border-2 border-emerald-500 p-6 relative flex flex-col justify-between">
                    <span class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-emerald-500 text-white text-xs font-bold py-1 px-3 rounded-full uppercase">Paling Hemat</span>
                    <div>
                        <div class="text-sm font-semibold text-emerald-600 uppercase tracking-wider mb-2">3 Bulan</div>
                        <div class="flex items-baseline mb-4">
                            <span class="text-3xl font-extrabold text-gray-900">Rp 39.000</span>
                            <span class="text-gray-500 text-sm ml-1">/ 3 bulan</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-6">Setara <b>Rp 13.000/bln</b>. Hemat Rp 6.000 setiap 3 bulan.</p>
                        <ul class="text-sm text-gray-600 space-y-2 mb-6">
                            <li>✓ Semua fitur Pro lengkap</li>
                            <li>✓ Prioritas antrean support</li>
                            <li>✓ Pembayaran via QRIS / E-Wallet</li>
                        </ul>
                    </div>
                    @if($is_pro)
                        <div class="w-full py-2.5 px-4 bg-emerald-50 border border-emerald-300 text-emerald-800 font-semibold text-center rounded-lg text-sm">
                            ✓ Paket Aktif Saat Ini
                        </div>
                    @elseif($pending_subscription && $pending_subscription->billing_cycle === 'quarterly')
                        <div class="w-full py-2.5 px-4 bg-amber-100 border border-amber-300 text-amber-800 font-semibold text-center rounded-lg text-sm">
                            ⏳ Menunggu Pembayaran
                        </div>
                    @else
                        <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                            @csrf
                            <input type="hidden" name="cycle" value="quarterly">
                            <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition cursor-pointer">
                                Pilih 3 Bulan (Rp 39.000)
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Annual Plan -->
                <div class="bg-white rounded-xl shadow-sm border p-6 flex flex-col justify-between">
                    <div>
                        <div class="text-sm font-semibold text-emerald-600 uppercase tracking-wider mb-2">1 Tahun (Super Hemat)</div>
                        <div class="flex items-baseline mb-4">
                            <span class="text-3xl font-extrabold text-gray-900">Rp 129.000</span>
                            <span class="text-gray-500 text-sm ml-1">/ tahun</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-6">Setara <b>Rp 10.750/bln</b>. Bayar sekali, tenang setahun penuh.</p>
                        <ul class="text-sm text-gray-600 space-y-2 mb-6">
                            <li>✓ Semua fitur Pro lengkap</li>
                            <li>✓ Kuota tanpa batas 365 hari</li>
                            <li>✓ WhatsApp Admin support langsung</li>
                        </ul>
                    </div>
                    @if($is_pro)
                        <div class="w-full py-2.5 px-4 bg-emerald-50 border border-emerald-300 text-emerald-800 font-semibold text-center rounded-lg text-sm">
                            ✓ Paket Aktif Saat Ini
                        </div>
                    @elseif($pending_subscription && $pending_subscription->billing_cycle === 'annually')
                        <div class="w-full py-2.5 px-4 bg-amber-100 border border-amber-300 text-amber-800 font-semibold text-center rounded-lg text-sm">
                            ⏳ Menunggu Pembayaran
                        </div>
                    @else
                        <form method="POST" action="{{ route('vendor.billing.checkout') }}">
                            @csrf
                            <input type="hidden" name="cycle" value="annually">
                            <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg shadow-sm transition cursor-pointer">
                                Pilih 1 Tahun (Rp 129.000)
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
