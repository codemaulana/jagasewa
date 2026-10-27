<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-surface border border-border-subtle rounded-xl p-4 sm:p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="previousMonth"
                    class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-lg border border-border-subtle bg-surface text-text-main hover:bg-surface-subtle transition shadow-xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                    title="Periode Sebelumnya"
                    aria-label="Periode Sebelumnya"
                >
                    <svg class="w-4 h-4 text-text-main" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button
                    type="button"
                    wire:click="goToCurrentMonth"
                    class="inline-flex items-center justify-center min-h-[44px] px-3.5 text-xs font-semibold rounded-lg border border-border-subtle bg-surface text-text-main hover:bg-surface-subtle transition shadow-xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                >
                    Hari Ini
                </button>

                <button
                    type="button"
                    wire:click="nextMonth"
                    class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] rounded-lg border border-border-subtle bg-surface text-text-main hover:bg-surface-subtle transition shadow-xs focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-1"
                    title="Periode Berikutnya"
                    aria-label="Periode Berikutnya"
                >
                    <svg class="w-4 h-4 text-text-main" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Switcher Tampilan Bulan / Minggu -->
                <div class="inline-flex items-center p-1 bg-surface-subtle border border-border-subtle rounded-lg ml-1">
                    <button
                        type="button"
                        wire:click="setViewMode('month')"
                        class="min-h-[36px] px-3 text-xs font-semibold rounded-md transition {{ $viewMode === 'month' ? 'bg-surface text-text-main shadow-xs' : 'text-text-secondary hover:text-text-main' }}"
                    >
                        Bulan
                    </button>
                    <button
                        type="button"
                        wire:click="setViewMode('week')"
                        class="min-h-[36px] px-3 text-xs font-semibold rounded-md transition {{ $viewMode === 'week' ? 'bg-surface text-text-main shadow-xs' : 'text-text-secondary hover:text-text-main' }}"
                    >
                        Minggu
                    </button>
                </div>

                <h2 class="text-base sm:text-lg font-bold text-text-main ml-2 tracking-tight">
                    {{ $monthLabel }}
                </h2>
            </div>

            <!-- Filter Barang -->
            <div class="flex items-center gap-3">
                <label for="itemFilter" class="text-xs font-medium text-text-secondary whitespace-nowrap">
                    Filter Barang:
                </label>
                <div class="relative min-w-[200px] sm:min-w-[240px]">
                    <select
                        id="itemFilter"
                        wire:model.live="selectedItemId"
                        class="w-full min-h-[44px] h-11 text-xs font-medium rounded-lg border border-border-subtle bg-surface text-text-main py-2 pl-3 pr-8 focus:outline-none focus:ring-2 focus:ring-primary transition"
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
            <div class="p-3.5 bg-success-bg border border-success-border rounded-xl flex items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-success font-medium">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span><strong>Smart Buffer Calendar Aktif:</strong> Jeda waktu pembersihan laundry dan pengiriman terkunci otomatis untuk mencegah pesanan bentrok.</span>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-accent text-accent-text">PRO</span>
            </div>
        @else
            <!-- Teaser Smart Buffer (Free) -->
            <div class="p-3.5 bg-surface border border-border-subtle rounded-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs shadow-2xs">
                <div class="flex items-center gap-2 text-text-secondary">
                    <svg class="w-4 h-4 text-warning flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span><strong>Smart Buffer per Kategori:</strong> Upgrade ke Pro untuk mengatur otomatis jeda pengiriman dan laundry berbeda untuk tiap kategori barang.</span>
                </div>
                <a href="{{ route('vendor.billing.index') }}" class="min-h-[36px] px-3 py-1.5 bg-primary hover:opacity-90 text-primary-text text-[11px] font-semibold rounded-lg transition whitespace-nowrap inline-flex items-center justify-center">
                    Lihat Paket Pro
                </a>
            </div>
        @endif

        <!-- Legend / Keterangan Status -->
        <div class="flex flex-wrap items-center gap-2.5 text-xs text-text-secondary px-1">
            <span class="font-semibold text-text-main">Keterangan:</span>
            <div class="inline-flex items-center gap-1.5 bg-warning-bg border border-warning-border px-2.5 py-1 rounded-md text-warning font-medium">
                <span class="w-2 h-2 rounded-full bg-warning"></span>
                <span>Menunggu (Pending)</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-info-bg border border-info-border px-2.5 py-1 rounded-md text-info font-medium">
                <span class="w-2 h-2 rounded-full bg-info"></span>
                <span>Disetujui</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-success-bg border border-success-border px-2.5 py-1 rounded-md text-success font-medium">
                <span class="w-2 h-2 rounded-full bg-success"></span>
                <span>Sedang Disewa</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-neutral-status-bg border border-neutral-status-border px-2.5 py-1 rounded-md text-neutral-status-text font-medium">
                <span class="w-2 h-2 rounded-full bg-text-secondary"></span>
                <span>Dikembalikan</span>
            </div>
            <div class="inline-flex items-center gap-1.5 bg-buffer-bg border border-dashed border-buffer-border px-2.5 py-1 rounded-md text-buffer-text font-medium">
                <span class="w-2 h-2 rounded-full bg-text-muted"></span>
                <span>Jeda Buffer</span>
            </div>
        </div>

        <!-- Petunjuk Geser di Mobile -->
        <div class="sm:hidden flex items-center justify-between text-[11px] text-text-muted px-1">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                Geser ke samping untuk melihat kalender penuh
            </span>
            <span>7 Hari</span>
        </div>

        <!-- Kisi Kalender Bulanan (Bisa discroll horizontal di mobile 375px agar sel tidak terjepit) -->
        <div class="overflow-x-auto -mx-4 sm:mx-0 px-4 sm:px-0">
            <div class="min-w-[640px] sm:min-w-full bg-surface border border-border-subtle rounded-xl overflow-hidden shadow-xs">
                <div class="grid grid-cols-7 border-b border-border-subtle bg-surface-subtle text-center py-2.5 text-xs font-semibold text-text-secondary">
                    @foreach($daysOfWeek as $dow)
                        <div>{{ $dow }}</div>
                    @endforeach
                </div>

                <div class="divide-y divide-border-subtle">
                    @foreach($weeks as $week)
                        <div class="grid grid-cols-7 divide-x divide-border-subtle min-h-[90px] sm:min-h-[120px]">
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
                                    class="p-1.5 sm:p-2 cursor-pointer transition flex flex-col justify-between group
                                        {{ ! $isCurrent ? 'bg-surface-subtle/60 text-text-muted' : 'bg-surface text-text-main' }}
                                        {{ $isSelected ? 'ring-2 ring-inset ring-primary' : 'hover:bg-surface-subtle' }}
                                    "
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="inline-flex items-center justify-center w-6 h-6 text-xs font-semibold rounded-full
                                            {{ $isToday ? 'bg-primary text-primary-text' : '' }}
                                        ">
                                            {{ $day['day'] }}
                                        </span>

                                        @if($eventCount > 0)
                                            <span class="sm:hidden inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-surface-subtle border border-border-subtle text-text-main">
                                                {{ $eventCount }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Daftar Badges Event (Muncul di layar sm ke atas) -->
                                    <div class="space-y-1 overflow-hidden flex-1 hidden sm:block">
                                        @foreach(array_slice($events, 0, 3) as $event)
                                            @php
                                                $st = $event['status'];
                                                $badgeClass = match($st) {
                                                    'pending' => 'bg-warning-bg text-warning border-warning-border',
                                                    'approved' => 'bg-info-bg text-info border-info-border',
                                                    'active' => 'bg-success-bg text-success border-success-border',
                                                    'returned' => 'bg-neutral-status-bg text-text-secondary border-neutral-status-border',
                                                    'buffer' => 'bg-buffer-bg text-buffer-text border-dashed border-buffer-border',
                                                    default => 'bg-surface-subtle text-text-secondary border-border-subtle',
                                                };
                                            @endphp
                                            <a
                                                href="{{ $event['view_url'] }}"
                                                @click.stop
                                                title="{{ $event['status_label'] }} - {{ $event['item_name'] }} ({{ $event['reference_code'] }})"
                                                class="block truncate text-[10px] font-medium px-1.5 py-0.5 rounded border transition hover:opacity-80 {{ $badgeClass }}"
                                            >
                                                @if($event['type'] === 'buffer')
                                                    [Jeda] {{ $event['item_name'] }}
                                                @else
                                                    {{ $event['item_name'] }} &middot; {{ $event['renter_name'] }}
                                                @endif
                                            </a>
                                        @endforeach

                                        @if($eventCount > 3)
                                            <div class="text-[9px] font-semibold text-text-muted px-1">
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
            <div class="bg-surface border border-border-subtle rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between mb-4 border-b border-border-subtle pb-3">
                    <div class="flex items-center gap-2">
                        <span class="p-2 rounded-lg bg-surface-subtle text-text-main border border-border-subtle">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-semibold text-text-main">
                                Agenda Tanggal: {{ $selectedDateLabel }}
                            </h3>
                            <p class="text-xs text-text-secondary">
                                Klik tombol Rincian untuk membuka halaman booking
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-surface-subtle border border-border-subtle text-text-secondary tabular-nums">
                        {{ count($selectedDateEvents) }} Agenda
                    </span>
                </div>

                @if(empty($selectedDateEvents))
                    <div class="text-center py-8 text-text-muted text-xs">
                        <p>Tidak ada booking atau jeda buffer pada tanggal ini.</p>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($selectedDateEvents as $event)
                            @php
                                $st = $event['status'];
                                $badgeColor = match($st) {
                                    'pending' => 'bg-warning-bg text-warning border-warning-border',
                                    'approved' => 'bg-info-bg text-info border-info-border',
                                    'active' => 'bg-success-bg text-success border-success-border',
                                    'returned' => 'bg-neutral-status-bg text-text-secondary border-neutral-status-border',
                                    'buffer' => 'bg-buffer-bg text-buffer-text border-buffer-border border-dashed',
                                    default => 'bg-surface-subtle text-text-secondary border-border-subtle',
                                };
                            @endphp
                            <div class="border border-border-subtle rounded-lg p-3 bg-surface hover:bg-surface-subtle transition flex flex-col justify-between gap-2.5 shadow-2xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold text-text-main">
                                            {{ $event['item_name'] }}
                                        </h4>
                                        <p class="text-[11px] text-text-secondary mt-0.5">
                                            Kode: <span class="font-mono font-medium">{{ $event['reference_code'] }}</span>
                                        </p>
                                    </div>
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full border {{ $badgeColor }}">
                                        {{ $event['status_label'] }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-border-subtle text-xs">
                                    <span class="text-text-secondary text-[11px]">
                                        Penyewa: <strong class="text-text-main font-semibold">{{ $event['renter_name'] }}</strong>
                                    </span>
                                    <a
                                        href="{{ $event['view_url'] }}"
                                        class="inline-flex items-center justify-center min-h-[44px] px-3 gap-1 text-xs font-semibold text-text-main hover:text-primary transition rounded-lg hover:bg-surface-subtle"
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
