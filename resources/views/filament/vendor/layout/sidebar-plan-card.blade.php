@php
    $vendor = auth('vendor')->user()?->vendor;
    $isPro = $vendor?->isPro() ?? false;
    $expiresAt = $vendor?->subscription_expires_at;
@endphp

@if($vendor)
    <div class="fi-sidebar-plan-card my-1">
        <!-- Expanded Sidebar Card (Desktop full width 248px & Mobile Drawer) -->
        <div class="fi-sidebar-plan-card-expanded rounded-xl border border-border bg-surface p-3 shadow-xs transition">
            <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold {{ $isPro ? 'bg-accent text-accent-text' : 'bg-surface-subtle text-text-muted border border-border' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $isPro ? 'bg-text' : 'bg-text-muted' }}"></span>
                    {{ $isPro ? 'Paket Pro' : 'Paket Free' }}
                </span>
                <a 
                    href="{{ route('vendor.billing.index') }}" 
                    class="text-[11px] font-semibold text-text hover:opacity-80 underline underline-offset-2 transition"
                >
                    Kelola &rarr;
                </a>
            </div>
            <p class="text-[11px] text-text-muted leading-tight">
                @if($isPro)
                    Berlaku s/d {{ $expiresAt ? $expiresAt->format('d M Y') : 'Aktif' }}
                @else
                    Maks. 15 barang aktif
                @endif
            </p>
        </div>

        <!-- Collapsed Sidebar Icon/Pill (Desktop collapsed 72px) -->
        <div class="fi-sidebar-plan-card-collapsed hidden justify-center">
            <a 
                href="{{ route('vendor.billing.index') }}" 
                class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-border bg-surface hover:bg-surface-subtle transition text-text"
                title="{{ $isPro ? 'Paket Pro (Kelola)' : 'Paket Free (Kelola)' }}"
            >
                @if($isPro)
                    <span class="text-xs font-bold text-accent-text bg-accent px-1.5 py-0.5 rounded">PRO</span>
                @else
                    <span class="text-[10px] font-semibold text-text-muted">FREE</span>
                @endif
            </a>
        </div>
    </div>
@endif
