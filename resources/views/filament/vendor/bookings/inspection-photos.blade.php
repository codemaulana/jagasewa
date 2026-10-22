@php
    $handoverPhotos = $record->handover_photo_urls ?? [];
    $returnPhotos = $record->return_photo_urls ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Foto Sebelum Sewa (Serah Terima) -->
    <div class="space-y-2 p-3.5 bg-surface-subtle border border-border-subtle rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-text-main">Foto Kondisi Sebelum Sewa</span>
            <span class="text-[11px] text-text-secondary">{{ count($handoverPhotos) }} Foto</span>
        </div>
        @if(empty($handoverPhotos))
            <p class="text-xs text-text-muted py-4 text-center">Belum ada foto dokumentasi sebelum sewa.</p>
        @else
            <div class="grid grid-cols-3 gap-2">
                @foreach($handoverPhotos as $photoUrl)
                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="block aspect-square rounded-md overflow-hidden border border-border-subtle group relative hover:opacity-90">
                        <img src="{{ $photoUrl }}" alt="Foto Serah Terima" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Foto Sesudah Sewa (Pengembalian & Bukti Kerusakan) -->
    <div class="space-y-2 p-3.5 bg-surface-subtle border border-border-subtle rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-text-main">Foto Sesudah Sewa / Bukti Kerusakan</span>
            <span class="text-[11px] text-text-secondary">{{ count($returnPhotos) }} Foto</span>
        </div>
        @if(empty($returnPhotos))
            <p class="text-xs text-text-muted py-4 text-center">Belum ada foto dokumentasi saat pengembalian.</p>
        @else
            <div class="grid grid-cols-3 gap-2">
                @foreach($returnPhotos as $photoUrl)
                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="block aspect-square rounded-md overflow-hidden border border-border-subtle group relative hover:opacity-90">
                        <img src="{{ $photoUrl }}" alt="Foto Pengembalian" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
