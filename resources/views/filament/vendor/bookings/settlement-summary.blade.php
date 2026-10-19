<div class="rounded-xl border border-neutral-200 bg-neutral-50 p-4 space-y-2.5 text-xs text-neutral-700">
    <div class="flex justify-between items-center">
        <span>Hari Terlambat:</span>
        <span class="font-semibold text-neutral-900">{{ $result->lateDays }} hari</span>
    </div>
    <div class="flex justify-between items-center">
        <span>Denda Terlambat ({{ $result->lateDays }} × Rp {{ number_format($result->finePerDay, 0, ',', '.') }}):</span>
        <span class="font-semibold text-neutral-900">Rp {{ number_format($result->lateFine, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center">
        <span>Biaya Kerusakan Barang:</span>
        <span class="font-semibold text-neutral-900">Rp {{ number_format($result->damageDeduction, 0, ',', '.') }}</span>
    </div>
    <div class="border-t border-neutral-200 pt-2 flex justify-between items-center font-medium text-neutral-800">
        <span>Total Seluruh Potongan:</span>
        <span class="font-bold text-neutral-900">Rp {{ number_format($result->totalDeduction, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between items-center">
        <span>Uang Jaminan (Deposit) Awal:</span>
        <span class="font-semibold text-neutral-900">Rp {{ number_format($result->deposit, 0, ',', '.') }}</span>
    </div>

    <div class="border-t border-neutral-300 pt-2.5 mt-1">
        @if($result->hasRefund)
            <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-lg flex justify-between items-center text-emerald-800">
                <span class="font-semibold">Sisa Uang Jaminan (Refund):</span>
                <span class="font-bold text-sm">Rp {{ number_format($result->refund, 0, ',', '.') }}</span>
            </div>
        @elseif($result->hasShortfall)
            <div class="p-2.5 bg-red-50 border border-red-200 rounded-lg flex justify-between items-center text-red-800">
                <span class="font-semibold">Kekurangan Bayar Penyewa:</span>
                <span class="font-bold text-sm">-Rp {{ number_format($result->shortfall, 0, ',', '.') }}</span>
            </div>
        @else
            <div class="p-2.5 bg-neutral-100 border border-neutral-200 rounded-lg flex justify-between items-center text-neutral-800">
                <span class="font-semibold">Deposit Habis Pas:</span>
                <span class="font-bold text-sm">Rp 0</span>
            </div>
        @endif
    </div>
</div>
