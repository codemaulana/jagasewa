<x-filament-widgets::widget>
    @if($vendor)
        <div @if($pendingSubscription) wire:poll.3s @endif class="space-y-4">
            {{-- Dynamic Status Alerts --}}
            @if($pendingSubscription)
                <div class="p-3.5 bg-amber-50/80 border border-amber-200/90 rounded-xl flex items-center justify-between text-xs text-amber-900 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-amber-600 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Menunggu konfirmasi pembayaran paket Pro ({{ ucfirst($pendingSubscription->billing_cycle) }})... Status akan terbarui otomatis.</span>
                    </div>
                    <a href="{{ route('vendor.billing.index') }}" class="font-bold text-amber-900 hover:text-amber-700 hover:underline flex-shrink-0">
                        Lihat Tagihan &rarr;
                    </a>
                </div>
            @elseif($inGracePeriod)
                <div class="p-3.5 bg-rose-50/80 border border-rose-200/90 rounded-xl flex items-center justify-between text-xs text-rose-900 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Masa tenggang 3 hari: Langganan Pro Anda telah selesai. Perpanjang sekarang agar akses fitur Pro tetap aktif.</span>
                    </div>
                    <a href="{{ route('vendor.billing.index') }}" class="font-bold text-rose-900 hover:text-rose-700 hover:underline flex-shrink-0">
                        Perpanjang &rarr;
                    </a>
                </div>
            @elseif($isPro && $isNearExpiration)
                <div class="p-3.5 bg-amber-50/80 border border-amber-200/90 rounded-xl flex items-center justify-between text-xs text-amber-900 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Masa aktif Pro tersisa {{ $daysUntilExpiration }} hari lagi. Perpanjang untuk mempertahankan fitur tanpa batas.</span>
                    </div>
                    <a href="{{ route('vendor.billing.index') }}" class="font-bold text-amber-900 hover:text-amber-700 hover:underline flex-shrink-0">
                        Perpanjang &rarr;
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                {{-- Store URL Card --}}
                <div class="lg:col-span-2 bg-white border border-slate-200/90 rounded-xl p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600 border border-blue-100">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </span>
                                <h3 class="text-sm font-bold text-slate-900 tracking-tight">Tautan Etalase Toko Publik</h3>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Aktif & Terverifikasi
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed mb-4">
                            Bagikan tautan ini di profil Instagram, bio TikTok, atau pesan WhatsApp. Penyewa dapat melihat ketersediaan barang dan memesan langsung tanpa perlu login.
                        </p>
                    </div>

                    <div x-data="{ copied: false, url: '{{ $storeUrl }}' }" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-3 border-t border-slate-100">
                        <div class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-3.5 py-2 font-mono text-xs text-slate-700 truncate select-all flex items-center justify-between">
                            <span class="truncate">{{ $storeUrl }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 h-10 px-3.5 text-xs font-semibold rounded-lg border transition shadow-2xs select-none"
                                :class="copied ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-slate-900 text-primary-text hover:bg-slate-800 border-slate-900'"
                            >
                                <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span x-text="copied ? 'Tersalin!' : 'Salin Tautan'"></span>
                            </button>

                            <a
                                href="{{ $storeUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center justify-center gap-1 h-10 px-3 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs"
                                title="Buka etalase toko di tab baru"
                            >
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span>Buka</span>
                            </a>

                            <a
                                href="{{ \App\Filament\Vendor\Pages\StoreSettings::getUrl() }}"
                                class="inline-flex items-center justify-center w-10 h-10 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-500 hover:text-slate-800 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs"
                                title="Pengaturan Profil Toko"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Subscription Plan Card --}}
                <div class="bg-white border border-slate-200/90 rounded-xl p-5 shadow-2xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Langganan</span>
                            @if($isPro)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                                    PRO AKTIF
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    FREE
                                </span>
                            @endif
                        </div>

                        <div class="space-y-2.5 mt-2">
                            @if($isPro)
                                <div class="text-xs py-1">
                                    <span class="font-bold text-slate-900 tabular-nums">{{ $activeItems }} barang aktif</span>
                                    <span class="text-slate-400"> · Kuota Tanpa Batas</span>
                                </div>
                                <div class="text-xs">
                                    <span class="font-medium text-slate-700 tabular-nums">{{ $blacklistCount }} catatan risiko</span>
                                    <span class="text-slate-400"> · Kuota Tanpa Batas</span>
                                </div>
                            @else
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-600 font-medium">Barang Aktif</span>
                                    <span class="font-bold text-slate-900 tabular-nums">{{ $activeItems }} / 15</span>
                                </div>
                                <div class="w-full bg-slate-100 border border-slate-200/60 rounded-full h-2 overflow-hidden">
                                    @php
                                        $percentItems = min(100, ($activeItems / 15) * 100);
                                    @endphp
                                    <div class="h-2 rounded-full transition-all duration-300 {{ $percentItems >= 100 ? 'bg-amber-500' : 'bg-slate-900' }}" style="width: {{ $percentItems }}%"></div>
                                </div>

                                <div class="flex items-center justify-between text-xs pt-1">
                                    <span class="text-slate-600 font-medium">Catatan Blacklist</span>
                                    <span class="font-medium text-slate-700 tabular-nums">{{ $blacklistCount }} / 10</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="pt-3.5 mt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        @if($isPro)
                            <span class="text-[11px] text-slate-500 truncate">
                                Berlaku s/d <span class="font-medium text-slate-800">{{ $activeSubscription?->current_period_end ? $activeSubscription->current_period_end->format('d M Y') : 'Aktif' }}</span>
                            </span>
                            <a href="{{ route('vendor.billing.index') }}" class="font-bold text-blue-600 hover:text-blue-700 hover:underline text-xs flex-shrink-0">
                                Kelola
                            </a>
                        @else
                            <span class="text-[11px] text-slate-500 truncate">Perlu kuota lebih banyak?</span>
                            <a href="{{ route('vendor.billing.index') }}" class="font-bold text-blue-600 hover:text-blue-700 hover:underline text-xs flex-shrink-0">
                                Upgrade Pro &rarr;
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>
