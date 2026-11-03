@php
    $vendor = auth('vendor')->user()?->vendor;
    $isPro = $vendor?->isPro() ?? false;
    $expiresAt = $vendor?->subscription_expires_at;
@endphp

@if($vendor)
    <div class="fi-sidebar-plan-card my-1.5 px-3">
        <!-- Expanded Sidebar Card (Desktop full width 248px & Mobile Drawer) -->
        <div class="fi-sidebar-plan-card-expanded rounded-xl border border-slate-200/90 bg-white p-3.5 shadow-2xs transition">
            <div class="flex items-center justify-between gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $isPro ? 'bg-blue-50 text-blue-700 border-blue-200/80' : 'bg-slate-100 text-slate-700 border-slate-200/80' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isPro ? 'bg-blue-600' : 'bg-slate-400' }}"></span>
                    {{ $isPro ? 'Paket Pro' : 'Paket Free' }}
                </span>
                <a 
                    href="{{ route('vendor.billing.index') }}" 
                    class="text-[11px] font-semibold text-blue-600 hover:text-blue-700 hover:underline transition"
                >
                    Kelola &rarr;
                </a>
            </div>
            <p class="text-[11px] text-slate-500 leading-relaxed">
                @if($isPro)
                    @if($expiresAt)
                        Aktif s/d <span class="font-medium text-slate-700 tabular-nums">{{ $expiresAt->locale('id')->translatedFormat('d M Y') }}</span>
                    @else
                        Akses fitur penuh tanpa batas
                    @endif
                @else
                    Maks. 15 barang aktif. <a href="{{ route('vendor.billing.index') }}" class="font-semibold text-slate-800 hover:underline">Upgrade</a>
                @endif
            </p>
        </div>

        <!-- Collapsed Sidebar Icon/Pill (Desktop collapsed 72px) -->
        <div class="fi-sidebar-plan-card-collapsed hidden justify-center">
            <a 
                href="{{ route('vendor.billing.index') }}" 
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition text-slate-800 shadow-2xs"
                title="{{ $isPro ? 'Paket Pro (Kelola)' : 'Paket Free (Kelola)' }}"
            >
                @if($isPro)
                    <span class="text-[10px] font-bold text-blue-700 bg-blue-50 px-1 py-0.5 rounded">PRO</span>
                @else
                    <span class="text-[9px] font-bold text-slate-500">FREE</span>
                @endif
            </a>
        </div>
    </div>
@endif
