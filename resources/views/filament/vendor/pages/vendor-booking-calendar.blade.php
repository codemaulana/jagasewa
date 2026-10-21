<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Toolbar Kontrol: Navigasi Bulan & Filter Barang -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Navigasi Bulan -->
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="previousMonth"
                    class="inline-flex items-center justify-center p-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700/60 transition shadow-2xs"
                    title="Bulan Sebelumnya"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button
                    type="button"
                    wire:click="goToCurrentMonth"
                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700/60 transition shadow-2xs"
                >
                    Bulan Ini
                </button>

                <button
                    type="button"
                    wire:click="nextMonth"
                    class="inline-flex items-center justify-center p-2 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-700/60 transition shadow-2xs"
                    title="Bulan Berikutnya"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white ml-2 tracking-tight">
                    {{ $monthLabel }}
                </h2>
            </div>

            <!-- Filter Barang -->
            <div class="flex items-center gap-3">
                <label for="itemFilter" class="text-xs font-medium text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                    Filter Barang:
                </label>
                <div class="relative min-w-[200px] sm:min-w-[240px]">
                    <select
                        id="itemFilter"
                        wire:model.live="selectedItemId"
                        class="w-full text-xs font-medium rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white py-1.5 pl-3 pr-8 focus:ring-1 focus:ring-zinc-900 dark:focus:ring-white transition"
                    >
                        <option value="all">Semua Barang ({{ $items->count() }})</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Legend / Keterangan Status -->
        <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-600 dark:text-zinc-300 px-1">
            <span class="font-semibold text-zinc-700 dark:text-zinc-200">Keterangan:</span>
            <div class="inline-flex items-center gap-1.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 px-2 py-0.5 rounded-md text-amber-800 dark:text-amber-300">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Menunggu (Pending)</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 px-2 py-0.5 rounded-md text-sky-800 dark:text-sky-300">
                <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                <span>Disetujui</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-md text-emerald-800 dark:text-emerald-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Sedang Disewa</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 px-2 py-0.5 rounded-md text-indigo-800 dark:text-indigo-300">
                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                <span>Dikembalikan</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 px-2 py-0.5 rounded-md text-zinc-700 dark:text-zinc-300">
                <span class="w-2 h-2 rounded-full bg-zinc-400"></span>
                <span>Jeda Buffer</span>
            </div>
        </div>

        <!-- Kisi Kalender Bulanan -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl overflow-hidden shadow-xs">
            <!-- Header Nama Hari (Sen - Min) -->
            <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-950/60 text-center py-2.5 text-xs font-bold text-zinc-600 dark:text-zinc-400">
                @foreach($daysOfWeek as $dow)
                    <div>{{ $dow }}</div>
                @endforeach
            </div>

            <!-- Grid Hari per Minggu -->
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @foreach($weeks as $week)
                    <div class="grid grid-cols-7 divide-x divide-zinc-200 dark:divide-zinc-800 min-h-[90px] sm:min-h-[120px]">
                        @foreach($week as $day)
                            @php
                                $isCurrent = $day['isCurrentMonth'];
                                $isToday = $day['isToday'];
                                $isSelected = $day['isSelected'];
                                $events = $day['events'];
                                $eventCount = count($events);
                            @endphp
                            <div
                                wire:click="selectDate('{{ $day['date'] }}')"
                                class="p-1 sm:p-2 cursor-pointer transition flex flex-col justify-between group
                                    {{ ! $isCurrent ? 'bg-zinc-50/50 dark:bg-zinc-950/30 text-zinc-400 dark:text-zinc-600' : 'bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100' }}
                                    {{ $isSelected ? 'ring-2 ring-inset ring-zinc-900 dark:ring-zinc-100' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/40' }}
                                "
                            >
                                <!-- Tanggal -->
                                <div class="flex items-center justify-between mb-1">
                                    <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold rounded-full
                                        {{ $isToday ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : '' }}
                                    ">
                                        {{ $day['day'] }}
                                    </span>

                                    @if($eventCount > 0)
                                        <span class="sm:hidden w-2 h-2 rounded-full bg-emerald-500"></span>
                                    @endif
                                </div>

                                <!-- Daftar Badges Event (Hidden di layar sangat kecil, muncul di sm ke atas) -->
                                <div class="space-y-1 overflow-hidden flex-1 hidden sm:block">
                                    @foreach(array_slice($events, 0, 3) as $event)
                                        @php
                                            $st = $event['status'];
                                            $badgeClass = match($st) {
                                                'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/70 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                                                'approved' => 'bg-sky-100 text-sky-800 dark:bg-sky-950/70 dark:text-sky-300 border-sky-200 dark:border-sky-800',
                                                'active' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                                                'returned' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/70 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                                                'buffer' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800/80 dark:text-zinc-400 border-dashed border-zinc-300 dark:border-zinc-700',
                                                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
                                            };
                                        @endphp
                                        <a
                                            href="{{ $event['view_url'] }}"
                                            @click.stop
                                            title="{{ $event['status_label'] }} - {{ $event['item_name'] }} ({{ $event['reference_code'] }})"
                                            class="block truncate text-[10px] font-medium px-1.5 py-0.5 rounded border transition hover:opacity-80 {{ $badgeClass }}"
                                        >
                                            @if($event['type'] === 'buffer')
                                                🛡️ {{ $event['item_name'] }} (Buffer)
                                            @else
                                                {{ $event['item_name'] }} · {{ $event['renter_name'] }}
                                            @endif
                                        </a>
                                    @endforeach

                                    @if($eventCount > 3)
                                        <div class="text-[9px] font-bold text-zinc-500 dark:text-zinc-400 px-1">
                                            +{{ $eventCount - 3 }} lainnya
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Rincian Agenda Hari Yang Dipilih -->
        @if($selectedDate)
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">
                            Jadwal Sewa: {{ $selectedDateLabel }}
                        </h3>
                    </div>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ count($selectedDateEvents) }} Agenda Terjadwal
                    </span>
                </div>

                @if(empty($selectedDateEvents))
                    <div class="text-center py-6 text-zinc-500 dark:text-zinc-400 text-xs">
                        <p>Tidak ada booking atau jeda buffer pada tanggal ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($selectedDateEvents as $event)
                            @php
                                $st = $event['status'];
                                $badgeColor = match($st) {
                                    'pending' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
                                    'approved' => 'bg-sky-50 text-sky-800 border-sky-200 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800',
                                    'active' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
                                    'returned' => 'bg-indigo-50 text-indigo-800 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-800',
                                    'buffer' => 'bg-zinc-50 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700',
                                    default => 'bg-zinc-50 text-zinc-700 border-zinc-200',
                                };
                            @endphp
                            <div class="border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 bg-zinc-50/50 dark:bg-zinc-950/40 flex flex-col justify-between gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-zinc-900 dark:text-white">
                                            {{ $event['item_name'] }}
                                        </h4>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            Kode: <span class="font-mono font-medium">{{ $event['reference_code'] }}</span>
                                        </p>
                                    </div>
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border {{ $badgeColor }}">
                                        {{ $event['status_label'] }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-xs">
                                    <span class="text-zinc-600 dark:text-zinc-400 text-[11px]">
                                        Penyewa: <strong class="text-zinc-800 dark:text-zinc-200">{{ $event['renter_name'] }}</strong>
                                    </span>
                                    <a
                                        href="{{ $event['view_url'] }}"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-zinc-900 dark:text-white hover:underline"
                                    >
                                        <span>Rincian</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
