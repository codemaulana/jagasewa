@props([
    'status' => 'pending',
    'size' => 'md',
])

@php
    // Mapping 5 Varian Semantik Baku (Anti-Slop: Teks + Titik Warna, Tanpa Emoji)
    $configs = [
        'pending' => [
            'label' => 'Menunggu persetujuan',
            'variant' => 'bg-amber-50 text-amber-800 border-amber-200/80',
            'dot' => 'bg-amber-500',
        ],
        'returned' => [
            'label' => 'Dikembalikan',
            'variant' => 'bg-amber-50 text-amber-800 border-amber-200/80',
            'dot' => 'bg-amber-500',
        ],
        'approved' => [
            'label' => 'Disetujui',
            'variant' => 'bg-sky-50 text-sky-800 border-sky-200/80',
            'dot' => 'bg-sky-500',
        ],
        'active' => [
            'label' => 'Sedang disewa',
            'variant' => 'bg-sky-50 text-sky-800 border-sky-200/80',
            'dot' => 'bg-sky-500',
        ],
        'closed' => [
            'label' => 'Selesai',
            'variant' => 'bg-emerald-50 text-emerald-800 border-emerald-200/80',
            'dot' => 'bg-emerald-500',
        ],
        'rejected' => [
            'label' => 'Ditolak',
            'variant' => 'bg-rose-50 text-rose-800 border-rose-200/80',
            'dot' => 'bg-rose-500',
        ],
        'cancelled_timeout' => [
            'label' => 'Batal (batas waktu)',
            'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
            'dot' => 'bg-slate-400',
        ],
        'cancelled_renter' => [
            'label' => 'Batal (penyewa)',
            'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
            'dot' => 'bg-slate-400',
        ],
        'cancelled_vendor' => [
            'label' => 'Batal (vendor)',
            'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
            'dot' => 'bg-slate-400',
        ],
        'cancelled' => [
            'label' => 'Batal',
            'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
            'dot' => 'bg-slate-400',
        ],
        'expired' => [
            'label' => 'Kedaluwarsa',
            'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
            'dot' => 'bg-slate-400',
        ],
        'pro' => [
            'label' => 'Paket Pro',
            'variant' => 'bg-blue-50 text-blue-700 border-blue-200/80',
            'dot' => 'bg-blue-600',
        ],
        'free' => [
            'label' => 'Paket Free',
            'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
            'dot' => 'bg-slate-400',
        ],
        'accent' => [
            'label' => 'Unggulan',
            'variant' => 'bg-blue-50 text-blue-700 border-blue-200',
            'dot' => 'bg-blue-600',
        ],
    ];

    $config = $configs[$status] ?? (
        str_starts_with($status, 'cancelled') 
            ? $configs['cancelled'] 
            : [
                'label' => ucfirst(str_replace('_', ' ', $status)),
                'variant' => 'bg-slate-100 text-slate-700 border-slate-200/80',
                'dot' => 'bg-slate-400',
            ]
    );

    $sizeClasses = $size === 'sm' ? 'px-2 py-0.5 text-[11px] gap-1.5' : 'px-2.5 py-1 text-xs gap-1.5';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full border {$config['variant']} {$sizeClasses} select-none"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $config['dot'] }} flex-shrink-0"></span>
    <span>{{ $slot->isNotEmpty() ? $slot : $config['label'] }}</span>
</span>
