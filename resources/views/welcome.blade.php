<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JagaSewa — Platform Booking Rental Aman</title>
    <link rel="icon" type="image/png" href="{{ asset('jagasewa_logo3.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    @include('public.partials.design-tokens')
</head>
<body class="bg-neutral-50 text-neutral-900 antialiased min-h-screen flex flex-col justify-between">

    <!-- Header Navigasi -->
    <header class="border-b border-neutral-200 bg-white/95 backdrop-blur-sm sticky top-0 z-30">
        <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-neutral-900 font-bold text-base tracking-tight">
                    <img src="{{ asset('jagasewa_logo1.png') }}" alt="JagaSewa" class="h-9 sm:h-10 w-auto object-contain">
                </a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/vendor/login') }}" class="text-xs font-medium text-neutral-700 hover:text-neutral-900 px-3 py-2 rounded-xl transition min-h-[44px] inline-flex items-center">
                    Masuk Vendor
                </a>
                <a href="{{ url('/vendor/register') }}" class="text-xs font-semibold text-white bg-[#111111] hover:bg-[#262626] active:bg-black h-10 px-4 rounded-full transition inline-flex items-center justify-center shadow-xs">
                    Daftar Rental
                </a>
            </div>
        </div>
    </header>

    <!-- Konten Utama Hero -->
    <main class="max-w-3xl mx-auto px-4 py-16 sm:py-24 text-center space-y-8 flex-1 flex flex-col justify-center">
        <div class="space-y-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-[#86F04C] text-[#0A0A0A]">
                Sistem Manajemen Rental Terpadu
            </span>
            <h1 class="text-3xl sm:text-4xl font-bold text-neutral-900 tracking-tight leading-tight">
                Kelola jadwal sewa dan verifikasi penyewa dengan tertib.
            </h1>
            <p class="text-sm sm:text-base text-neutral-600 max-w-xl mx-auto leading-relaxed">
                Platform booking rental dengan kalender jeda otomatis (buffer laundry & pengiriman), verifikasi identitas terenkripsi, dan konfirmasi langsung via WhatsApp.
            </p>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/vendor/register') }}" class="w-full sm:w-auto h-11 px-6 rounded-full bg-[#111111] hover:bg-[#262626] active:bg-black text-white font-semibold text-sm shadow-xs transition inline-flex items-center justify-center">
                Mulai Kelola Toko Rental
            </a>
            <a href="{{ url('/vendor/login') }}" class="w-full sm:w-auto h-11 px-6 rounded-full bg-white hover:bg-neutral-50 active:bg-neutral-100 text-neutral-800 border border-neutral-200 font-semibold text-sm transition inline-flex items-center justify-center">
                Masuk ke Dashboard
            </a>
        </div>

        <!-- Fitur Inti Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-8 text-left">
            <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-xs space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-neutral-100 flex items-center justify-center text-neutral-700 mb-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900">Smart Buffer Calendar</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">
                    Jeda waktu pengiriman dan laundry otomatis terkunci untuk mencegah jadwal sewa bertabrakan.
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-xs space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-neutral-100 flex items-center justify-center text-neutral-700 mb-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900">Verifikasi Dokumen Aman</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">
                    Watermark otomatis pada KTP dan selfie, penyimpanan privat terenkripsi, serta auto-delete.
                </p>
            </div>

            <div class="bg-white p-4 rounded-xl border border-neutral-200 shadow-xs space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-neutral-100 flex items-center justify-center text-neutral-700 mb-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-neutral-900">Denda & Deposit Jelas</h3>
                <p class="text-xs text-neutral-500 leading-relaxed">
                    Perhitungan otomatis denda keterlambatan dan pemotongan deposit saat barang dikembalikan.
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-neutral-200 bg-white py-6 text-center">
        <p class="text-xs text-neutral-400">
            JagaSewa &copy; {{ date('Y') }}. Hak cipta dilindungi undang-undang.
        </p>
    </footer>

</body>
</html>
