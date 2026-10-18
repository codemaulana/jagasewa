<div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm space-y-4">
    <!-- Header Bagian Kalender -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                <span>📅</span> Kalender Ketersediaan
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">Pilih tanggal sewa yang tersedia (hijau)</p>
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button"
                    wire:click="goToPreviousMonth"
                    @disabled(!$canGoPrevious)
                    class="w-8 h-8 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 active:scale-95 transition disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:bg-white"
                    title="Bulan sebelumnya">
                ‹
            </button>
            <span class="text-xs font-bold text-gray-800 min-w-[110px] text-center px-1">
                {{ $monthLabel }}
            </span>
            <button type="button"
                    wire:click="goToNextMonth"
                    class="w-8 h-8 rounded-xl border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 active:scale-95 transition"
                    title="Bulan berikutnya">
                ›
            </button>
        </div>
    </div>

    <!-- Error message alert -->
    @if($errorMessage)
        <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs p-3 rounded-2xl flex items-start gap-2 animate-fade-in">
            <span class="text-rose-500 font-bold">⚠️</span>
            <p class="leading-relaxed flex-1">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Calendar Grid -->
    <div class="pt-1">
        <!-- Hari dalam seminggu (Senin s.d. Minggu) -->
        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-gray-400 mb-1.5">
            <div>Sn</div>
            <div>Sl</div>
            <div>Rb</div>
            <div>Km</div>
            <div>Jm</div>
            <div>Sb</div>
            <div>Mg</div>
        </div>

        <!-- Tanggal grid -->
        <div class="grid grid-cols-7 gap-1.5">
            <!-- Empty cells for leading days before day 1 -->
            @for($i = 0; $i < $leadingEmptyDays; $i++)
                <div class="aspect-square"></div>
            @endfor

            <!-- Day cells -->
            @foreach($days as $dayData)
                @php
                    $date = $dayData['date'];
                    $dayNum = $dayData['day'];
                    $status = $dayData['status'];

                    $isSelected = ($selectedStartDate && $date === $selectedStartDate) ||
                                  ($selectedEndDate && $date === $selectedEndDate) ||
                                  ($selectedStartDate && $selectedEndDate && $date >= $selectedStartDate && $date <= $selectedEndDate);

                    $isStartOrEnd = ($date === $selectedStartDate || $date === $selectedEndDate);
                @endphp

                <button type="button"
                        wire:click="selectDate('{{ $date }}')"
                        @disabled($status !== 'available')
                        class="aspect-square rounded-xl text-xs font-medium flex flex-col items-center justify-center transition relative
                            @if($isSelected)
                                bg-emerald-600 text-white font-bold ring-2 ring-emerald-500 shadow-sm
                            @elseif($status === 'booked')
                                bg-rose-50 border border-rose-100 text-rose-400 cursor-not-allowed line-through
                            @elseif($status === 'buffer')
                                bg-gray-100 border border-gray-100 text-gray-400 cursor-not-allowed
                            @else
                                bg-white hover:bg-emerald-50 text-gray-800 border border-gray-200 hover:border-emerald-300 cursor-pointer active:scale-95
                            @endif"
                        title="{{ $status === 'booked' ? 'Terpesan' : ($status === 'buffer' ? 'Masa Jeda (Buffer)' : 'Tersedia') }}">
                    <span>{{ sprintf('%02d', $dayNum) }}</span>
                    @if($status === 'booked')
                        <span class="w-1 h-1 rounded-full bg-rose-400 mt-0.5"></span>
                    @elseif($status === 'buffer')
                        <span class="w-1 h-1 rounded-full bg-gray-400 mt-0.5"></span>
                    @elseif(!$isSelected)
                        <span class="w-1 h-1 rounded-full bg-emerald-500 mt-0.5 opacity-60"></span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    <!-- Legenda Status Kalender -->
    <div class="flex items-center justify-between text-[11px] text-gray-500 pt-2 border-t border-gray-100">
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-md bg-white border border-gray-300 inline-block"></span>
            <span>Tersedia</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-md bg-rose-100 border border-rose-200 inline-block"></span>
            <span class="text-rose-600 font-medium">Terpesan</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-md bg-gray-200 border border-gray-300 inline-block"></span>
            <span>Buffer Jeda</span>
        </div>
    </div>

    <!-- Info Pilihan Tanggal Penyewa -->
    @if($selectedStartDate)
        <div class="bg-emerald-50/80 border border-emerald-200 rounded-2xl p-4 space-y-3 pt-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                    <span>✨</span> Pilihan Tanggal Sewa
                </span>
                <button type="button"
                        wire:click="resetSelection"
                        class="text-[11px] text-rose-600 hover:text-rose-700 font-medium underline">
                    Reset Pilihan
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-white p-2.5 rounded-xl border border-emerald-100">
                    <span class="text-[10px] text-gray-400 block font-medium">Mulai Sewa</span>
                    <span class="font-bold text-gray-900">{{ $this->formatIndonesianDate($selectedStartDate) }}</span>
                </div>
                <div class="bg-white p-2.5 rounded-xl border border-emerald-100">
                    <span class="text-[10px] text-gray-400 block font-medium">Selesai Sewa</span>
                    <span class="font-bold text-gray-900">
                        @if($selectedEndDate)
                            {{ $this->formatIndonesianDate($selectedEndDate) }}
                        @else
                            <span class="text-emerald-600 font-normal italic">Pilih tanggal akhir...</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1 border-t border-emerald-200/60 text-emerald-900">
                <span>Durasi: <strong class="text-emerald-950 font-bold">{{ $totalDays }} Hari</strong></span>
                <span>Estimasi Sewa: <strong class="text-emerald-950 font-bold">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong></span>
            </div>
        </div>
    @endif
</div>
