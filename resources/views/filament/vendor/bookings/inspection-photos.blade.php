@php
    $handoverPhotos = $record->handover_photo_urls ?? [];
    $returnPhotos = $record->return_photo_urls ?? [];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <!-- Foto Sebelum Sewa (Serah Terima) -->
    <div class="space-y-3 p-4 sm:p-5 bg-white border border-slate-200/90 rounded-2xl shadow-xs">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                <span class="text-xs sm:text-sm font-bold text-slate-900">Dokumentasi Sebelum Sewa</span>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                {{ count($handoverPhotos) }} Foto
            </span>
        </div>

        @if(empty($handoverPhotos))
            <div class="py-8 text-center bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
                <svg class="w-6 h-6 text-slate-400 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-xs text-slate-500 font-medium">Belum ada foto dokumentasi sebelum sewa.</p>
            </div>
        @else
            <div class="grid grid-cols-3 gap-2.5">
                @foreach($handoverPhotos as $photoUrl)
                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="group relative aspect-square rounded-xl overflow-hidden border border-slate-200/90 bg-slate-100 shadow-2xs hover:shadow-md transition-all">
                        <img src="{{ $photoUrl }}" alt="Foto Serah Terima" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                            <span class="p-1.5 rounded-lg bg-white/90 text-slate-900 shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Foto Sesudah Sewa (Pengembalian & Bukti Kerusakan) -->
    <div class="space-y-3 p-4 sm:p-5 bg-white border border-slate-200/90 rounded-2xl shadow-xs">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span class="text-xs sm:text-sm font-bold text-slate-900">Dokumentasi Saat Pengembalian</span>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700">
                {{ count($returnPhotos) }} Foto
            </span>
        </div>

        @if(empty($returnPhotos))
            <div class="py-8 text-center bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
                <svg class="w-6 h-6 text-slate-400 mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-xs text-slate-500 font-medium">Belum ada foto dokumentasi saat pengembalian.</p>
            </div>
        @else
            <div class="grid grid-cols-3 gap-2.5">
                @foreach($returnPhotos as $photoUrl)
                    <a href="{{ $photoUrl }}" target="_blank" rel="noopener noreferrer" class="group relative aspect-square rounded-xl overflow-hidden border border-slate-200/90 bg-slate-100 shadow-2xs hover:shadow-md transition-all">
                        <img src="{{ $photoUrl }}" alt="Foto Pengembalian" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                            <span class="p-1.5 rounded-lg bg-white/90 text-slate-900 shadow-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>