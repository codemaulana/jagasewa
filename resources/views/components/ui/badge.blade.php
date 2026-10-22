@props([
    'status' => 'pending',
    'size' => 'md',
])

@php
    // Mapping 5 Varian Semantik (Anti-Slop: Teks + Titik Warna, Tanpa Emoji):
    // 1. Peringatan: pending, returned
    // 2. Info: approved, active
    // 3. Sukses: closed
    // 4. Bahaya: rejected
    // 5. Netral: cancelled_*, expired, default
    $configs = [
        'pending' => [
            'label' => 'Menunggu persetujuan',
            'variant' => 'badge-status-warning',
            'dot' => 'bg-warning',
        ],
        'returned' => [
            'label' => 'Dikembalikan',
            'variant' => 'badge-status-warning',
            'dot' => 'bg-warning',
        ],
        'approved' => [
            'label' => 'Disetujui',
            'variant' => 'badge-status-info',
            'dot' => 'bg-info',
        ],
        'active' => [
            'label' => 'Sedang disewa',
            'variant' => 'badge-status-info',
            'dot' => 'bg-info',
        ],
        'closed' => [
            'label' => 'Selesai',
            'variant' => 'badge-status-success',
            'dot' => 'bg-success',
        ],
        'rejected' => [
            'label' => 'Ditolak',
            'variant' => 'badge-status-danger',
            'dot' => 'bg-danger',
        ],
        'cancelled_timeout' => [
            'label' => 'Batal (batas waktu)',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-text-secondary',
        ],
        'cancelled_renter' => [
            'label' => 'Batal (penyewa)',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-text-secondary',
        ],
        'cancelled_vendor' => [
            'label' => 'Batal (vendor)',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-text-secondary',
        ],
        'cancelled' => [
            'label' => 'Batal',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-text-secondary',
        ],
        'expired' => [
            'label' => 'Kedaluwarsa',
            'variant' => 'badge-status-neutral',
            'dot' => 'bg-text-secondary',
        ],
        'accent' => [
            'label' => 'Unggulan',
            'variant' => 'badge-accent-lime font-medium border border-transparent',
            'dot' => 'bg-accent-lime-text',
        ],
    ];

    $config = $configs[$status] ?? (
        str_starts_with($status, 'cancelled') 
            ? $configs['cancelled'] 
            : [
                'label' => ucfirst($status),
                'variant' => 'badge-status-neutral',
                'dot' => 'bg-text-secondary',
            ]
    );

    $sizeClasses = $size === 'sm' ? 'px-2 py-0.5 text-[11px] gap-1.5' : 'px-2.5 py-1 text-xs gap-1.5';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full border {$config['variant']} {$sizeClasses} select-none"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }} flex-shrink-0"></span>
    <span>{{ $slot->isNotEmpty() ? $slot : $config['label'] }}</span>
</span>
