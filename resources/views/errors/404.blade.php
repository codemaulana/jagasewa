<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Halaman Tidak Ditemukan | JagaSewa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg-base text-text-main font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-md w-full bg-surface border border-border-subtle rounded-2xl shadow-subtle p-6 sm:p-8 text-center space-y-5">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-surface-subtle border border-border-subtle text-text-secondary">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div class="space-y-1.5">
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-surface-subtle text-text-secondary border border-border-subtle">
                Galat 404
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight">
                Halaman Tidak Ditemukan
            </h1>
            <p class="text-xs sm:text-sm text-text-secondary leading-relaxed">
                Tautan yang Anda tuju mungkin sudah dipindahkan, diubah namanya, atau barang rental tersebut sudah tidak aktif.
            </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="{{ url('/') }}" class="w-full sm:w-auto flex-1 min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-white bg-primary hover:bg-neutral-800 transition shadow-xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                Kembali ke Beranda
            </a>
            <button type="button" onclick="window.history.back()" class="w-full sm:w-auto min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-text-secondary hover:text-text-main bg-surface-subtle hover:bg-neutral-200 border border-border-subtle transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                Halaman Sebelumnya
            </button>
        </div>

        <div class="pt-2 border-t border-border-subtle text-[11px] text-text-muted">
            JagaSewa &mdash; Platform Rental Barang Aman
        </div>
    </div>
</body>
</html>
