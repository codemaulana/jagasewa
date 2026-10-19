@props([
    'status' => 'pending',
    'size' => 'md',
])

@php
    // Mapping 5 Varian Semantik:
    // 1. Peringatan: pending, returned
    // 2. Info: approved, active
    // 3. Sukses: closed
    // 4. Bahaya: rejected
    // 5. Netral: cancelled_*, expired, default
    $configs = [
        'pending' => [
            'label' => 'Menunggu persetujuan',
            'variant' => 'badge-status-warning',
            'dot' => 'bg-[var(--color-status-warning-text)]',
        ],
        'returned' => [
            'label' => 'Dikembalikan',
            'variant' => 'badge-status-warning',
            'dot' => 'bg-[var(--color-status-warning-text)]',
        ],
        'approved' => [
            'label' => 'Disetujui',
            'variant' => 'badge-status-info',
            'dot' => 'bg-[var(--color-status-info-text)]',
        ],
        'active' => [
            'label' => 'Sedang disewa',
            'variant' => 'badge-status-info',
            'dot' => 'bg-[var(--color-status-info-text)]',
        ],
        'closed' => [
            'label' => 'Selesai',
            'variant' => 'badge-status-success',
            'dot' => 'bg-[var(--color-status-success-text)]',
        ],
        'rejected' => [
            'label' => 'Ditolak',
            'variant' => 'badge-status-danger',
            'dot' => 'bg-[var(--color-status-danger-text)]',
        ],
        'cancelled_timeout' => [
            'label' => 'Batal (batas waktu)',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-[var(--color-status-neutral-text)]',
        ],
        'cancelled_renter' => [
            'label' => 'Batal (penyewa)',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-[var(--color-status-neutral-text)]',
        ],
        'cancelled_vendor' => [
            'label' => 'Batal (vendor)',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-[var(--color-status-neutral-text)]',
        ],
        'cancelled' => [
            'label' => 'Batal',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-[var(--color-status-neutral-text)]',
        ],
        'expired' => [
            'label' => 'Kedaluwarsa',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-[var(--color-status-neutral-text)]',
        ],
    ];

    $config = $configs[$status] ?? (
        str_starts_with($status, 'cancelled') 
            ? $configs['cancelled'] 
            : [
                'label' => ucfirst($status),
                'variant' => 'badge-status-neutral',
                'dot' => 'bg-slate-400',
            ]
    );

    $sizeClasses = $size === 'sm' ? 'px-2 py-0.5 text-[11px] gap-1.5' : 'px-2.5 py-1 text-xs gap-1.5';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full border {$config['variant']} {$sizeClasses} select-none"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }} flex-shrink-0"></span>
    <span>{{ $slot->isNotEmpty() ? $slot : $config['label'] }}</span>
</span>
