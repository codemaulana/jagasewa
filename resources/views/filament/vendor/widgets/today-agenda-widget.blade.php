@php
    $data = $this->getViewData();
    $items = $data['items'];
    $counts = $data['counts'];
    $tab = $data['activeTab'];
@endphp

<x-filament-widgets::widget>
    <div id="agenda-hari-ini" class="bg-white border border-slate-200/90 rounded-xl p-5 sm:p-6 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
            <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600 border border-blue-100">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </span>
                    <span>Agenda Tugas Hari Ini</span>
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Jadwal serah-terima unit dan penanganan pesanan sewa per hari ini.
                </p>
            </div>

            <!-- Tab Buttons (Segmented Control) -->
            <div class="inline-flex p-1 rounded-lg bg-slate-100 border border-slate-200/70 text-xs gap-1 select-none">
                <button
                    type="button"
                    wire:click="setTab('pengambilan')"
                    class="px-3 py-1.5 rounded-md font-semibold transition {{ $tab === 'pengambilan' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-900' }}"
                >
                    Pengambilan ({{ $counts['pengambilan'] }})
                </button>
                <button
                    type="button"
                    wire:click="setTab('pengembalian')"
                    class="px-3 py-1.5 rounded-md font-semibold transition {{ $tab === 'pengembalian' ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-900' }}"
                >
                    Pengembalian ({{ $counts['pengembalian'] }})
                </button>
                <button
                    type="button"
                    wire:click="setTab('terlambat')"
                    class="px-3 py-1.5 rounded-md font-semibold transition {{ $tab === 'terlambat' ? 'bg-rose-50 text-rose-800 shadow-2xs' : ($counts['terlambat'] > 0 ? 'text-rose-600 font-bold hover:bg-rose-50/50' : 'text-slate-500 hover:text-slate-900') }}"
                >
                    Terlambat ({{ $counts['terlambat'] }})
                </button>
            </div>
        </div>

        <!-- Daftar Agenda -->
        @if($items->isEmpty())
            <div class="py-10 text-center text-xs text-slate-500">
                <div class="w-10 h-10 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center mx-auto mb-2.5 text-slate-400">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-medium text-slate-600 block">Tidak ada jadwal {{ $tab }} untuk hari ini.</span>
                <span class="text-slate-400 text-[11px] mt-0.5 block">Semua agenda operasional berjalan lancar!</span>
            </div>
        @else
            <div class="divide-y divide-slate-100 overflow-hidden">
                @foreach($items as $booking)
                    @php
                        $cleanPhone = preg_replace('/[^0-9]/', '', $booking->renter?->phone ?? '');
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '62' . substr($cleanPhone, 1);
                        }
                        $waText = rawurlencode("Halo Kak {$booking->renter?->name}, kami dari toko rental mengonfirmasi pesanan sewa {$booking->item?->name} (Kode: {$booking->reference_code}).");
                        $waUrl = $cleanPhone ? "https://wa.me/{$cleanPhone}?text={$waText}" : null;
                    @endphp
                    <div class="py-3.5 flex flex-col md:flex-row md:items-center justify-between gap-3 hover:bg-slate-50/80 px-2.5 rounded-lg border border-transparent hover:border-slate-100 transition duration-150">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <a 
                                    href="{{ \App\Filament\Vendor\Resources\Bookings\BookingResource::getUrl('view', ['record' => $booking], panel: 'vendor') }}"
                                    class="font-mono text-xs font-bold text-blue-600 hover:text-blue-800 hover:underline"
                                >
                                    {{ $booking->reference_code }}
                                </a>
                                <span class="text-xs text-slate-300">&bull;</span>
                                <span class="font-bold text-xs text-slate-900">{{ $booking->item?->name }}</span>
                                @if($booking->item?->size)
                                    <span class="text-[11px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                        {{ $booking->item->size }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                <span>Penyewa: <strong class="text-slate-800 font-medium">{{ $booking->renter?->name ?? 'Penyewa' }}</strong></span>
                                <span>WA: <span class="font-mono text-slate-700">{{ $booking->renter?->phone ?? '-' }}</span></span>
                                <span>Total: <strong class="text-slate-900 font-bold tabular-nums">{{ \App\Helpers\FormatHelper::rupiah($booking->total_price) }}</strong></span>
                                @if($tab === 'terlambat')
                                    <span class="text-rose-700 font-bold bg-rose-50 px-2 py-0.5 rounded border border-rose-200/60">Jatuh Tempo: {{ \App\Helpers\FormatHelper::date($booking->end_date) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            @if($waUrl)
                                <a
                                    href="{{ $waUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 h-9 px-3 text-xs font-semibold rounded-lg bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 hover:border-slate-300 transition shadow-2xs"
                                    title="Hubungi penyewa via WhatsApp"
                                >
                                    <svg class="w-3.5 h-3.5 text-brand-whatsapp" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.299.045-.677.063-1.092-.069-.252-.08-.575-.187-.988-.365-1.739-.751-2.874-2.502-2.961-2.617-.087-.116-.708-.94-.708-1.793s.448-1.273.607-1.446c.159-.173.346-.217.462-.217l.332.006c.106.005.249-.04.39.298.144.347.491 1.2.534 1.287.043.087.072.188.014.304-.058.116-.087.188-.173.289l-.26.304c-.087.086-.177.18-.076.354.101.174.449.741.964 1.201.662.591 1.221.774 1.394.86s.275.072.376-.044c.101-.116.433-.506.549-.68.116-.173.231-.145.39-.087s1.011.477 1.184.564.289.13.332.202c.043.073.043.419-.101.824z"/>
                                    </svg>
                                    <span>WhatsApp</span>
                                </a>
                            @endif

                            @if($tab === 'pengambilan')
                                <button
                                    type="button"
                                    wire:click="markAsActive({{ $booking->id }})"
                                    class="inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-primary-text transition shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Tandai Diambil</span>
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="markAsReturned({{ $booking->id }})"
                                    class="inline-flex items-center gap-1.5 h-9 px-3.5 text-xs font-semibold rounded-lg bg-slate-900 hover:bg-slate-800 text-primary-text transition shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>Tandai Dikembalikan</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
