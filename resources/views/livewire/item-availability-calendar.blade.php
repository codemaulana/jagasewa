<x-ui.card class="space-y-3.5">
    <!-- Header Bagian Kalender -->
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-text-main">
                Kalender Ketersediaan
            </h3>
            <p class="text-xs text-text-secondary mt-0.5">Pilih tanggal mulai dan selesai sewa</p>
        </div>
        <div class="flex items-center gap-1.5">
            <button type="button"
                    wire:click="goToPreviousMonth"
                    @disabled(!$canGoPrevious)
                    class="min-h-[44px] min-w-[44px] rounded-lg border border-border-subtle bg-surface hover:bg-surface-subtle flex items-center justify-center text-text-secondary hover:text-text-main transition shadow-subtle disabled:opacity-30 disabled:cursor-not-allowed select-none"
                    title="Bulan sebelumnya"
                    aria-label="Bulan sebelumnya">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </button>
            <span class="text-xs font-semibold text-text-main min-w-[110px] text-center px-1">
                {{ $monthLabel }}
            </span>
            <button type="button"
                    wire:click="goToNextMonth"
                    class="min-h-[44px] min-w-[44px] rounded-lg border border-border-subtle bg-surface hover:bg-surface-subtle flex items-center justify-center text-text-secondary hover:text-text-main transition shadow-subtle select-none"
                    title="Bulan berikutnya"
                    aria-label="Bulan berikutnya">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Pesan Peringatan Galat -->
    @if($errorMessage)
        <x-ui.alert type="danger">
            {{ $errorMessage }}
        </x-ui.alert>
    @endif

    <!-- Grid Kalender -->
    <div class="pt-0.5">
        <!-- Hari dalam seminggu -->
        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold text-text-muted mb-1 select-none">
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
                <div class="aspect-square" aria-hidden="true"></div>
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
                        class="aspect-square rounded-lg text-xs font-semibold tabular-nums flex flex-col items-center justify-center transition relative min-h-[44px] select-none
                            @if($isSelected)
                                bg-primary text-white font-bold ring-2 ring-primary shadow-subtle
                            @elseif($status === 'booked')
                                badge-status-danger border line-through text-danger cursor-not-allowed
                            @elseif($status === 'buffer')
                                buffer-zone-pattern border border-border-subtle text-text-muted cursor-not-allowed
                            @else
                                bg-surface hover:bg-surface-subtle text-text-main border border-border-subtle hover:border-primary cursor-pointer active:scale-95 shadow-2xs
                            @endif"
                        title="{{ $status === 'booked' ? 'Terpesan' : ($status === 'buffer' ? 'Masa Jeda (Buffer)' : 'Tersedia') }}">
                    <span>{{ sprintf('%02d', $dayNum) }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Legenda Status Kalender -->
    <div class="flex items-center justify-between text-[11px] text-text-secondary pt-2.5 border-t border-border-subtle/50 select-none">
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded bg-surface border border-border-subtle inline-block shadow-2xs"></span>
            <span>Tersedia</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded badge-status-danger border inline-block"></span>
            <span>Terpesan</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3 h-3 rounded buffer-zone-pattern border border-border-subtle inline-block"></span>
            <span>Buffer Jeda</span>
        </div>
    </div>

    <!-- Rincian Tanggal Sewa yang Dipilih -->
    @if($selectedStartDate)
        <div class="bg-surface-subtle border border-border-subtle rounded-lg p-3.5 space-y-2.5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-text-main">
                    Rentang tanggal sewa
                </span>
                <button type="button"
                        wire:click="resetSelection"
                        class="text-[11px] text-danger hover:underline font-semibold min-h-[36px] px-1 flex items-center">
                    Reset pilihan
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-surface p-2.5 rounded-md border border-border-subtle">
                    <span class="text-[10px] text-text-secondary block">Mulai Sewa</span>
                    <span class="font-semibold text-text-main tabular-nums">{{ $this->formatIndonesianDate($selectedStartDate) }}</span>
                </div>
                <div class="bg-surface p-2.5 rounded-md border border-border-subtle">
                    <span class="text-[10px] text-text-secondary block">Selesai</span>
                    <span class="font-semibold text-text-main tabular-nums">
                        @if($selectedEndDate)
                            {{ $this->formatIndonesianDate($selectedEndDate) }}
                        @else
                            <span class="text-text-muted font-normal italic">Pilih tanggal akhir</span>
                        @endif
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-1 border-t border-border-subtle text-text-secondary">
                <span>Durasi: <strong class="text-text-main font-semibold tabular-nums">{{ $totalDays }} Hari</strong></span>
                <span>Estimasi sewa: <strong class="text-text-main font-semibold tabular-nums">Rp {{ number_format($totalPrice, 0, ',', '.') }}</strong></span>
            </div>
        </div>
    @endif
</x-ui.card>
