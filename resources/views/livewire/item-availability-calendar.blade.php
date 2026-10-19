<div class="bg-white p-4 sm:p-5 rounded-xl border border-neutral-200 shadow-xs space-y-3.5">
    <!-- Header Bagian Kalender -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-neutral-900">
                Kalender Ketersediaan
            </h3>
            <p class="text-xs text-neutral-500 mt-0.5">Pilih tanggal mulai dan selesai sewa</p>
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button"
                    wire:click="goToPreviousMonth"
                    @disabled(!$canGoPrevious)
                    class="w-8 h-8 rounded-lg border border-neutral-200 flex items-center justify-center text-neutral-600 hover:bg-neutral-50 active:scale-95 transition disabled:opacity-30 disabled:cursor-not-allowed"
                    title="Bulan sebelumnya">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <span class="text-xs font-semibold text-neutral-800 min-w-[110px] text-center px-1">
                {{ $monthLabel }}
            </span>
            <button type="button"
                    wire:click="goToNextMonth"
                    class="w-8 h-8 rounded-lg border border-neutral-200 flex items-center justify-center text-neutral-600 hover:bg-neutral-50 active:scale-95 transition"
                    title="Bulan berikutnya">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Pesan Peringatan Galat -->
    @if($errorMessage)
        <div class="badge-status-danger border text-xs p-3 rounded-xl flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-current" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
            <p class="leading-relaxed flex-1">{{ $errorMessage }}</p>
        </div>
    @endif

    <!-- Grid Kalender -->
    <div class="pt-0.5">
        <!-- Hari dalam seminggu -->
        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-neutral-400 mb-1">
            <div>Sn</div>
            <div>Sl</div>
            <div>Rb</div>
            <div>Km</div>
            <div>Jm</div>
            <div>Sb</div>
            <div>Mg</div>
        </div>

        <!-- Tanggal Grid -->
        <div class="grid grid-cols-7 gap-1.5">
            <!-- Sel Kosong Bulan Sebelumnya -->
            @for($i = 0; $i < $leadingEmptyDays; $i++)
                <div class="aspect-square"></div>
            @endfor

            <!-- Sel Tanggal -->
            @foreach($days as $dayData)
                @php
                    $date = $dayData['date'];
                    $dayNum = $dayData['day'];
                    $status = $dayData['status'];

                    $isSelected = ($selectedStartDate && $date === $selectedStartDate) ||
                                  ($selectedEndDate && $date === $selectedEndDate) ||
                                  ($selectedStartDate && $selectedEndDate && $date >= $selectedStartDate && $date <= $selectedEndDate);
                @endphp

                <button type="button"
                        wire:click="selectDate('{{ $date }}')"
                        @disabled($status !== 'available')
                        class="aspect-square rounded-lg text-xs font-medium flex flex-col items-center justify-center transition relative
                            @if($isSelected)
                                bg-primary-600 text-white font-semibold ring-2 ring-primary-600
                            @elseif($status === 'booked')
                                badge-status-danger border text-[var(--color-status-danger-text)] cursor-not-allowed line-through
                            @elseif($status === 'buffer')
                                bg-buffer-stripes border border-neutral-200 text-neutral-400 cursor-not-allowed
                            @else
                                bg-white hover:bg-primary-50 text-neutral-800 border border-neutral-200 hover:border-primary-600 cursor-pointer active:scale-95
                            @endif"
                        title="{{ $status === 'booked' ? 'Terpesan' : ($status === 'buffer' ? 'Masa Jeda (Buffer)' : 'Tersedia') }}">
                    <span>{{ sprintf('%02d', $dayNum) }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Legenda Status Kalender -->
    <div class="flex items-center justify-between text-[11px] text-neutral-500 pt-2.5 border-t border-neutral-100">
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded bg-white border border-neutral-300 inline-block"></span>
            <span>Tersedia</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded badge-status-danger border inline-block"></span>
            <span>Terpesan</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded bg-buffer-stripes border border-neutral-300 inline-block"></span>
            <span>Buffer Jeda</span>
        </div>
    </div>

    <!-- Rincian Tanggal Sewa yang Dipilih -->
    @if($selectedStartDate)
        <div class="bg-neutral-50 border border-neutral-200 rounded-xl p-3.5 space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-neutral-900">
                    Rentang tanggal sewa
                </span>
                <button type="button"
                        wire:click="resetSelection"
                        class="text-[11px] text-red-600 hover:text-red-700 font-medium">
                    Reset pilihan
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-white p-2.5 rounded-lg border border-neutral-200">
                    <span class="text-[10px] text-neutral-500 block">Mulai Sewa</span>
                    <span class="font-semibold text-neutral-900">{{ $this->formatIndonesianDate($selectedStartDate) }}</span>
                </div>
                <div class="bg-white p-2.5 rounded-lg border border-neutral-200">
                    <span class="text-[10px] text-neutral-500 block">Selesai</span>
                    <span class="font-semibold text-neutral-900">
                        @if($selectedEndDate)
                            {{ $this->formatIndonesianDate($selectedEndDate) }}
                        @else
                            <span class="text-neutral-400 font-normal italic">Pilih tanggal akhir</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1 border-t border-neutral-200 text-neutral-700">
                <span>Durasi: <strong class="text-neutral-900 font-semibold">{{ $totalDays }} Hari</strong></span>
                <span>Estimasi sewa: <strong class="text-neutral-900 font-semibold">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong></span>
            </div>
        </div>
    @endif
</div>
