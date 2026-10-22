<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Kendala Server | JagaSewa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg-base text-text-main font-sans antialiased min-h-screen flex items-center justify-center p-4 sm:p-6">
    <div class="max-w-md w-full bg-surface border border-border-subtle rounded-2xl shadow-subtle p-6 sm:p-8 text-center space-y-5">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-danger-bg border border-danger-border text-danger">
            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <div class="space-y-1.5">
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-mono font-semibold bg-danger-bg text-danger border border-danger-border">
                Galat 500
            </span>
            <h1 class="text-xl sm:text-2xl font-bold text-text-main tracking-tight">
                Terjadi Kendala pada Server
            </h1>
            <p class="text-xs sm:text-sm text-text-secondary leading-relaxed">
                Sistem kami sedang mengalami kendala sementara. Tim teknis telah menerima laporan otomatis dan sedang menanganinya.
            </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-3">
            <button type="button" onclick="window.location.reload()" class="w-full sm:w-auto flex-1 min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-white bg-primary hover:bg-neutral-800 transition shadow-xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                Muat Ulang Halaman
            </button>
            <a href="{{ url('/') }}" class="w-full sm:w-auto min-h-[44px] inline-flex items-center justify-center px-4 py-2.5 rounded-lg text-xs sm:text-sm font-semibold text-text-secondary hover:text-text-main bg-surface-subtle hover:bg-neutral-200 border border-border-subtle transition focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1">
                Ke Beranda
            </a>
        </div>

        <div class="pt-2 border-t border-border-subtle text-[11px] text-text-muted">
            JagaSewa &mdash; Platform Rental Barang Aman
        </div>
    </div>
</body>
</html>
