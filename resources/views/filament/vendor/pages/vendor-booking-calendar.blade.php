<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Toolbar Kontrol Kalender -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center flex-wrap gap-2.5">
                <div class="inline-flex items-center gap-1">
                    <button
                        type="button"
                        wire:click="previousMonth"
                        class="inline-flex items-center justify-center min-h-[40px] min-w-[40px] rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        title="Periode Sebelumnya"
                        aria-label="Periode Sebelumnya"
                    >
                        <svg class="w-4 h-4 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        wire:click="goToCurrentMonth"
                        class="inline-flex items-center justify-center min-h-[40px] px-3.5 text-xs font-semibold rounded-xl border border-slate-200 bg-white text-slate-800 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                        Hari Ini
                    </button>

                    <button
                        type="button"
                        wire:click="nextMonth"
                        class="inline-flex items-center justify-center min-h-[40px] min-w-[40px] rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        title="Periode Berikutnya"
                        aria-label="Periode Berikutnya"
                    >
                        <svg class="w-4 h-4 text-slate-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- Switcher Tampilan Bulan / Minggu -->
                <div class="inline-flex items-center p-1 bg-slate-100 border border-slate-200/80 rounded-xl">
                    <button
                        type="button"
                        wire:click="setViewMode('month')"
                        class="min-h-[32px] px-3 text-xs font-semibold rounded-lg transition {{ $viewMode === 'month' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Bulan
                    </button>
                    <button
                        type="button"
                        wire:click="setViewMode('week')"
                        class="min-h-[32px] px-3 text-xs font-semibold rounded-lg transition {{ $viewMode === 'week' ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600 hover:text-slate-900' }}"
                    >
                        Minggu
                    </button>
                </div>

                <h2 class="text-base sm:text-lg font-bold text-slate-900 ml-1 sm:ml-2 tracking-tight">
                    {{ $monthLabel }}
                </h2>
            </div>

            <!-- Filter Barang -->
            <div class="flex items-center gap-2.5">
                <label for="itemFilter" class="text-xs font-medium text-slate-500 whitespace-nowrap">
                    Filter Barang:
                </label>
                <div class="relative min-w-[190px] sm:min-w-[240px]">
                    <select
                        id="itemFilter"
                        wire:model.live="selectedItemId"
                        class="w-full min-h-[40px] h-10 text-xs font-medium rounded-xl border border-slate-200 bg-white text-slate-900 py-1.5 pl-3 pr-8 focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 transition shadow-2xs"
                    >
                        <option value="all">Semua Barang ({{ $items->count() }})</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if($isPro)
            <!-- Smart Buffer Banner (Pro) -->
            <div class="p-4 bg-emerald-50/80 border border-emerald-200 rounded-2xl flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2.5 text-emerald-900 font-medium">
                    <span class="w-6 h-6 rounded-lg bg-emerald-600 text-primary-text flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <span><strong>Smart Buffer Calendar Aktif:</strong> Jeda waktu pembersihan laundry dan pengiriman terkunci otomatis untuk mencegah bentrok jadwal booking.</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-700 text-primary-text shrink-0">PRO</span>
            </div>
        @else
            <!-- Teaser Smart Buffer (Free) -->
            <div class="p-4 bg-gradient-to-r from-blue-50/50 to-white border border-blue-100 rounded-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs shadow-2xs">
                <div class="flex items-center gap-2 text-slate-700">
                    <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </span>
                    <span><strong>Smart Buffer per Kategori:</strong> Upgrade ke Pro untuk mengatur otomatis jeda pengiriman dan laundry berbeda untuk tiap kategori barang.</span>
                </div>
                <a href="{{ route('vendor.billing.index') }}" class="min-h-[36px] px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-primary-text text-xs font-semibold rounded-lg transition whitespace-nowrap inline-flex items-center justify-center shadow-xs">
                    Lihat Paket Pro &rarr;
                </a>
            </div>
        @endif

        <!-- Legend / Keterangan Status -->
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 px-1">
            <span class="font-semibold text-slate-700 mr-1">Keterangan:</span>
            <div class="inline-flex items-center gap-1.5 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-lg text-amber-800 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span>Menunggu (Pending)</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-blue-50 border border-blue-200 px-2.5 py-1 rounded-lg text-blue-800 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                <span>Disetujui</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg text-emerald-800 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                <span>Sedang Disewa</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-slate-100 border border-slate-200 px-2.5 py-1 rounded-lg text-slate-700 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                <span>Dikembalikan</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-slate-100 border border-dashed border-slate-300 px-2.5 py-1 rounded-lg text-slate-600 font-medium">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                <span>Jeda Buffer</span>
            </div>
        </div>

        <!-- Petunjuk Geser di Mobile -->
        <div class="sm:hidden flex items-center justify-between text-[11px] text-slate-400 px-1">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Geser ke samping untuk kalender penuh
            </span>
            <span>7 Hari</span>
        </div>

        <!-- Kisi Kalender Bulanan -->
        <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
            <div class="min-w-[640px] sm:min-w-full bg-white border border-slate-200/90 rounded-2xl overflow-hidden shadow-xs">
                <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50/80 text-center py-3 text-xs font-bold text-slate-600 uppercase tracking-wider">
                    @foreach($daysOfWeek as $dow)
                        <div>{{ $dow }}</div>
                    @endforeach
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($weeks as $week)
                        <div class="grid grid-cols-7 divide-x divide-slate-100 min-h-[96px] sm:min-h-[124px]">
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
                                    class="p-2 cursor-pointer transition flex flex-col justify-between group
                                        {{ ! $isCurrent ? 'bg-slate-50/50 text-slate-400' : 'bg-white text-slate-900' }}
                                        {{ $isSelected ? 'ring-2 ring-inset ring-blue-600 bg-blue-50/20' : 'hover:bg-slate-50/80' }}
                                    "
                                >
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-bold rounded-lg
                                            {{ $isToday ? 'bg-slate-900 text-primary-text shadow-2xs' : ($isSelected ? 'text-blue-700 font-extrabold' : '') }}
                                        ">
                                            {{ $day['day'] }}
                                        </span>

                                        @if($eventCount > 0)
                                            <span class="sm:hidden inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-slate-100 border border-slate-200 text-slate-700">
                                                {{ $eventCount }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Daftar Badges Event -->
                                    <div class="space-y-1 overflow-hidden flex-1 hidden sm:block">
                                        @foreach(array_slice($events, 0, 3) as $event)
                                            @php
                                                $st = $event['status'];
                                                $badgeClass = match($st) {
                                                    'pending' => 'bg-amber-50 text-amber-900 border-amber-200 hover:bg-amber-100',
                                                    'approved' => 'bg-blue-50 text-blue-900 border-blue-200 hover:bg-blue-100',
                                                    'active' => 'bg-emerald-50 text-emerald-900 border-emerald-200 hover:bg-emerald-100',
                                                    'returned' => 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200',
                                                    'buffer' => 'bg-slate-50 text-slate-600 border-dashed border-slate-300 hover:bg-slate-100',
                                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                                };
                                            @endphp
                                            <a
                                                href="{{ $event['view_url'] }}"
                                                @click.stop
                                                title="{{ $event['status_label'] }} - {{ $event['item_name'] }} ({{ $event['reference_code'] }})"
                                                class="block truncate text-[10px] font-semibold px-2 py-0.5 rounded-md border transition {{ $badgeClass }}"
                                            >
                                                @if($event['type'] === 'buffer')
                                                    [Jeda] {{ $event['item_name'] }}
                                                @else
                                                    {{ $event['item_name'] }} &middot; {{ $event['renter_name'] }}
                                                @endif
                                            </a>
                                        @endforeach

                                        @if($eventCount > 3)
                                            <div class="text-[10px] font-semibold text-slate-400 px-1">
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
        </div>

        @if($selectedDate)
            <!-- Panel Rincian Agenda Tanggal Terpilih -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 sm:p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-bold text-slate-900">
                                Agenda Tanggal: {{ $selectedDateLabel }}
                            </h3>
                            <p class="text-xs text-slate-500">
                                Daftar pesanan dan aktivitas sewa pada tanggal yang dipilih
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 border border-slate-200 text-slate-700 tabular-nums">
                        {{ count($selectedDateEvents) }} Agenda
                    </span>
                </div>

                @if(empty($selectedDateEvents))
                    <div class="text-center py-8 text-slate-400 text-xs">
                        <svg class="w-8 h-8 mx-auto mb-2 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <p class="font-medium text-slate-500">Tidak ada booking atau jeda buffer pada tanggal ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                        @foreach($selectedDateEvents as $event)
                            @php
                                $st = $event['status'];
                                $badgeColor = match($st) {
                                    'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
                                    'approved' => 'bg-blue-50 text-blue-800 border-blue-200',
                                    'active' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                    'returned' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'buffer' => 'bg-slate-50 text-slate-600 border-dashed border-slate-300',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <div class="border border-slate-200 rounded-xl p-4 bg-white hover:border-slate-300 transition flex flex-col justify-between gap-3 shadow-2xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-900">
                                            {{ $event['item_name'] }}
                                        </h4>
                                        <p class="text-xs text-slate-500 mt-0.5">
                                            Kode: <span class="font-mono font-semibold text-slate-700">{{ $event['reference_code'] }}</span>
                                        </p>
                                    </div>
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full border {{ $badgeColor }}">
                                        {{ $event['status_label'] }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-xs">
                                    <span class="text-slate-500 text-xs">
                                        Penyewa: <strong class="text-slate-800 font-semibold">{{ $event['renter_name'] }}</strong>
                                    </span>
                                    <a
                                        href="{{ $event['view_url'] }}"
                                        class="inline-flex items-center justify-center min-h-[36px] px-3.5 gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition"
                                    >
                                        <span>Lihat Rincian</span>
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