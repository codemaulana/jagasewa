<x-layouts.renter 
    :title="'Katalog ' . $vendor->name . ' | JagaSewa'"
    :vendor="$vendor"
    headerTag="Katalog"
    maxWidth="max-w-xl sm:max-w-2xl"
>
    <!-- Informasi Vendor -->
    <x-ui.card class="overflow-hidden p-0">
        @if($vendor->banner_url)
            <div class="relative w-full aspect-[3/1] bg-surface-subtle overflow-hidden border-b border-border-subtle">
                <img src="{{ $vendor->banner_url }}" alt="Banner {{ $vendor->name }}" width="1200" height="400" loading="eager" class="w-full h-full object-cover">
            </div>
        @endif

        <div class="p-4 sm:p-5 space-y-3">
            <div class="flex items-start gap-3">
                @if($vendor->logo_url)
                    <img src="{{ $vendor->logo_url }}" alt="Logo {{ $vendor->name }}" width="48" height="48" loading="eager" class="w-12 h-12 rounded-lg object-cover border border-border-subtle flex-shrink-0 shadow-subtle">
                @else
                    <div class="w-12 h-12 rounded-lg bg-surface-subtle border border-border-subtle flex items-center justify-center text-text-main font-bold text-lg flex-shrink-0 shadow-subtle">
                        {{ strtoupper(substr($vendor->name, 0, 1)) }}
                    </div>
                @endif

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 mb-0.5 flex-wrap">
                        <h1 class="text-base font-bold text-text-main truncate">{{ $vendor->name }}</h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-accent-lime text-accent-lime-text">Terverifikasi</span>
                    </div>
                    
                    @if(!empty($vendor->description))
                        <p class="text-xs text-text-secondary leading-relaxed line-clamp-3 mt-1">
                            {{ $vendor->description }}
                        </p>
                    @else
                        <p class="text-xs text-text-muted">
                            Penyedia rental terdaftar di JagaSewa
                        </p>
                    @endif

                    @if(!empty($vendor->address))
                        <div class="flex items-center gap-1.5 text-[11px] text-text-secondary mt-1.5">
                            <svg class="w-3.5 h-3.5 text-text-muted flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">{{ $vendor->address }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @php
                $vendorPhone = \App\Helpers\WaNotificationHelper::normalizePhoneNumber($vendor->wa_number);
            @endphp

            <div class="pt-3 border-t border-border-subtle/50 flex items-center justify-between gap-2 flex-wrap text-xs text-text-secondary">
                <div class="flex items-center gap-2">
                    <span class="bg-surface-subtle text-text-main px-2 py-0.5 rounded font-mono text-[11px] border border-border-subtle">@ {{ $vendor->slug }}</span>
                    <span class="font-medium text-text-secondary tabular-nums">
                        {{ $items->total() }} Pilihan Rental
                    </span>
                </div>

                @if($vendorPhone)
                    <a href="https://wa.me/{{ $vendorPhone }}?text={{ rawurlencode('Halo ' . $vendor->name . ', saya ingin bertanya mengenai rental barang di katalog Anda.') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-success-bg text-success hover:bg-success hover:text-white border border-success-border transition shadow-subtle min-h-[36px]">
                        <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                        </svg>
                        <span>Hubungi Toko</span>
                    </a>
                @endif
            </div>
        </div>
    </x-ui.card>

    <!-- Judul Bagian -->
    <div class="flex items-center justify-between px-0.5 pt-1">
        <h2 class="text-sm font-semibold text-text-main">
            Daftar barang rental
        </h2>
        <span class="text-xs text-text-secondary">Pilih untuk melihat jadwal</span>
    </div>

    <!-- Grid Barang -->
    @if($items->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            @foreach($items as $item)
                <x-ui.card class="p-0 overflow-hidden hoverable flex flex-col justify-between">
                    <!-- Foto Utama -->
                    <div class="relative aspect-[4/3] bg-surface-subtle overflow-hidden">
                        @if(!empty($item->primary_photo_url))
                            <img src="{{ $item->primary_photo_url }}" alt="{{ $item->name }}" width="400" height="300" loading="lazy" decoding="async" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex flex-col items-center justify-center text-text-muted p-4 text-center">
                                <svg class="w-8 h-8 mb-1 text-border-hover" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                                <span class="text-[11px]">Foto belum tersedia</span>
                            </div>
                        @endif

                        @if($item->size)
                            <div class="absolute top-2 left-2 bg-text-main/80 backdrop-blur-xs text-white text-[11px] font-medium px-2 py-0.5 rounded-sm">
                                Ukuran: {{ $item->size }}
                            </div>
                        @endif
                    </div>

                    <!-- Isi Kartu -->
                    <div class="p-3.5 flex-1 flex flex-col justify-between space-y-3">
                        <div>
                            <h3 class="text-sm font-semibold text-text-main leading-snug line-clamp-2">
                                {{ $item->name }}
                            </h3>
                            
                            <div class="mt-2 flex items-baseline gap-1">
                                <span class="text-base font-bold text-text-main tabular-nums">
                                    Rp {{ number_format($item->price_per_day, 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-text-secondary">/ hari</span>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-[11px] text-text-secondary border-t border-border-subtle/50 pt-2">
                                <span>
                                    {{ $item->deposit_amount > 0 ? 'Deposit Rp ' . number_format($item->deposit_amount, 0, ',', '.') : 'Tanpa deposit' }}
                                </span>
                                <span class="text-text-main font-medium">
                                    Jeda {{ $item->buffer_before + $item->buffer_after }} hari
                                </span>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <x-ui.button 
                            variant="secondary" 
                            size="md" 
                            href="{{ route('public.items.show', ['vendor_slug' => $vendor->slug, 'item_slug' => $item->slug]) }}" 
                            fullWidth
                        >
                            Detail & ketersediaan
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @endforeach
        </div>

        <!-- Navigasi Halaman -->
        <div class="pt-3">
            <x-ui.pagination :paginator="$items" />
        </div>
    @else
        <!-- State Kosong -->
        <x-ui.empty-state 
            title="Belum Ada Barang Rental"
            description="Katalog barang rental sedang dipersiapkan oleh pemilik rental."
        />
    @endif
</x-layouts.renter>
