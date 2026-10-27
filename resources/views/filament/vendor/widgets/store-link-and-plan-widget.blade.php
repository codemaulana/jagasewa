<x-filament-widgets::widget>
    @if($vendor)
        <div @if($pendingSubscription) wire:poll.3s @endif class="space-y-3">
            @if($pendingSubscription)
                <div class="p-3 bg-warning-bg border border-warning-border rounded-xl flex items-center justify-between text-xs text-warning">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-warning animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Menunggu konfirmasi pembayaran paket Pro ({{ ucfirst($pendingSubscription->billing_cycle) }})... Status akan terbarui otomatis.</span>
                    </div>
                    <a href="{{ route('vendor.billing.index') }}" class="font-semibold text-warning hover:underline">
                        Lihat Tagihan &rarr;
                    </a>
                </div>
            @elseif($inGracePeriod)
                <div class="p-3 bg-warning-bg border border-warning-border rounded-xl flex items-center justify-between text-xs text-warning">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Masa tenggang 3 hari: Langganan Pro Anda telah selesai. Perpanjang sekarang agar akses Pro tidak terhenti.</span>
                    </div>
                    <a href="{{ route('vendor.billing.index') }}" class="font-semibold text-warning hover:underline">
                        Perpanjang &rarr;
                    </a>
                </div>
            @elseif($isPro && $isNearExpiration)
                <div class="p-3 bg-surface-subtle border border-border-subtle rounded-xl flex items-center justify-between text-xs text-text-main">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Masa aktif Pro tersisa {{ $daysUntilExpiration }} hari lagi. Perpanjang untuk mempertahankan fitur tanpa batas.</span>
                    </div>
                    <a href="{{ route('vendor.billing.index') }}" class="font-semibold text-text-main hover:underline">
                        Perpanjang &rarr;
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-surface border border-border-subtle rounded-xl p-4 sm:p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-surface-subtle text-text-main border border-border-subtle">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </span>
                            <h3 class="text-sm font-semibold text-text-main">Tautan Etalase Toko Publik</h3>
                        </div>
                        <span class="text-[11px] font-medium text-success bg-success-bg px-2.5 py-0.5 rounded-full border border-success-border">
                            Aktif & Terverifikasi
                        </span>
                    </div>
                    <p class="text-xs text-text-secondary mb-3">
                        Bagikan link ini di bio Instagram, postingan X, atau chat WhatsApp agar penyewa dapat langsung memesan tanpa login.
                    </p>
                </div>

                <div x-data="{ copied: false, url: '{{ $storeUrl }}' }" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-2 border-t border-border-subtle">
                    <div class="flex-1 bg-surface-subtle border border-border-subtle rounded-lg px-3 py-1.5 font-mono text-xs text-text-main truncate select-all flex items-center justify-between">
                        <span class="truncate">{{ $storeUrl }}</span>
                    </div>

                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText(url); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition shadow-2xs"
                            :class="copied ? 'bg-success-bg text-success border-success-border' : 'bg-primary text-primary-text hover:opacity-90 border-primary'"
                        >
                            <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span x-text="copied ? 'Tersalin!' : 'Salin Link'"></span>
                        </button>

                        <a
                            href="{{ $storeUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg border border-border-subtle bg-surface text-text-main hover:bg-surface-subtle transition shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            <span>Buka</span>
                        </a>

                        <a
                            href="{{ \App\Filament\Vendor\Pages\StoreSettings::getUrl() }}"
                            class="inline-flex items-center justify-center p-1.5 text-xs font-semibold rounded-lg border border-border-subtle bg-surface text-text-secondary hover:text-text-main hover:bg-surface-subtle transition shadow-2xs"
                            title="Pengaturan Toko"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-surface border border-border-subtle rounded-xl p-4 sm:p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-xs font-semibold text-text-secondary">Status Langganan</span>
                        @if($isPro)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-accent text-accent-text border border-transparent">
                                PRO
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-surface-subtle text-text-secondary border border-border-subtle">
                                FREE
                            </span>
                        @endif
                    </div>

                    <div class="space-y-2 mt-3">
                        @if($isPro)
                            <div class="text-xs py-1">
                                <span class="font-bold text-text-main tabular-nums">{{ $activeItems }} barang aktif</span>
                                <span class="text-text-muted"> · Tanpa Batas</span>
                            </div>
                            <div class="text-xs">
                                <span class="font-medium text-text-main tabular-nums">{{ $blacklistCount }} catatan blacklist</span>
                                <span class="text-text-muted"> · Tanpa Batas</span>
                            </div>
                        @else
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-text-secondary">Barang Aktif</span>
                                <span class="font-bold text-text-main tabular-nums">{{ $activeItems }} / 15</span>
                            </div>
                            <div class="w-full bg-surface-subtle border border-border-subtle rounded-full h-1.5 overflow-hidden">
                                @php
                                    $percentItems = min(100, ($activeItems / 15) * 100);
                                @endphp
                                <div class="h-1.5 rounded-full {{ $percentItems >= 100 ? 'bg-warning' : 'bg-primary' }}" style="width: {{ $percentItems }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-xs pt-1">
                                <span class="text-text-secondary">Catatan Blacklist</span>
                                <span class="font-medium text-text-main tabular-nums">{{ $blacklistCount }} / 10</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="pt-3 mt-3 border-t border-border-subtle flex items-center justify-between text-xs">
                    @if($isPro)
                        <span class="text-[11px] text-text-secondary truncate">
                            Aktif s/d {{ $activeSubscription?->current_period_end ? $activeSubscription->current_period_end->format('d M Y') : 'Aktif' }}
                        </span>
                        <a href="{{ route('vendor.billing.index') }}" class="font-semibold text-primary hover:underline text-xs flex-shrink-0">
                            Kelola
                        </a>
                    @else
                        <span class="text-[11px] text-text-secondary truncate">Upgrade kuota tanpa batas</span>
                        <a href="{{ route('vendor.billing.index') }}" class="font-semibold text-primary hover:underline text-xs flex-shrink-0">
                            Upgrade Pro &rarr;
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-widgets::widget>
