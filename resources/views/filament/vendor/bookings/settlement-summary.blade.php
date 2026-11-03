<div class="rounded-2xl border border-slate-200/90 bg-white p-4 sm:p-5 space-y-3 text-xs text-slate-600 shadow-xs">
    <div class="flex justify-between items-center py-1 border-b border-slate-100">
        <span class="font-medium text-slate-500">Hari Terlambat:</span>
        <span class="font-bold text-slate-900 tabular-nums">{{ $result->lateDays }} hari</span>
    </div>
    <div class="flex justify-between items-center py-1 border-b border-slate-100">
        <span class="font-medium text-slate-500">Denda Terlambat ({{ $result->lateDays }} &times; Rp {{ number_format($result->finePerDay, 0, ',', '.') }}):</span>
        <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($result->lateFine, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center py-1 border-b border-slate-100">
        <span class="font-medium text-slate-500">Biaya Kerusakan Barang:</span>
        <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($result->damageDeduction, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center py-1.5 font-medium text-slate-700 bg-slate-50 px-3 rounded-lg">
        <span>Total Seluruh Potongan:</span>
        <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($result->totalDeduction, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center py-1 border-b border-slate-100">
        <span class="font-medium text-slate-500">Uang Jaminan (Deposit) Awal:</span>
        <span class="font-bold text-slate-900 font-mono tabular-nums">Rp {{ number_format($result->deposit, 0, ',', '.') }}</span>
    </div>

    <div class="pt-2">
        @if($result->hasRefund)
            <div class="p-3.5 bg-emerald-50/70 border border-emerald-200 rounded-xl flex justify-between items-center text-emerald-900">
                <div>
                    <div class="font-bold text-xs flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Sisa Uang Jaminan (Refund)
                    </div>
                    <div class="text-[11px] text-emerald-700 mt-0.5">Dikembalikan ke rekening penyewa</div>
                </div>
                <span class="font-bold text-sm sm:text-base font-mono tabular-nums text-emerald-800">
                    Rp {{ number_format($result->refund, 0, ',', '.') }}
                </span>
            </div>
        @elseif($result->hasShortfall)
            <div class="p-3.5 bg-rose-50/70 border border-rose-200 rounded-xl flex justify-between items-center text-rose-900">
                <div>
                    <div class="font-bold text-xs flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Kekurangan Bayar Penyewa
                    </div>
                    <div class="text-[11px] text-rose-700 mt-0.5">Biaya denda & kerusakan melebihi deposit</div>
                </div>
                <span class="font-bold text-sm sm:text-base font-mono tabular-nums text-rose-800">
                    -Rp {{ number_format($result->shortfall, 0, ',', '.') }}
                </span>
            </div>
        @else
            <div class="p-3.5 bg-slate-100/70 border border-slate-200 rounded-xl flex justify-between items-center text-slate-800">
                <div>
                    <div class="font-bold text-xs flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        Deposit Habis Pas
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5">Tidak ada sisa refund atau kekurangan</div>
                </div>
                <span class="font-bold text-sm sm:text-base font-mono tabular-nums text-slate-900">
                    Rp 0
                </span>
            </div>
        @endif
    </div>
</div>