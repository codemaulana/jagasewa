<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modul Konfigurasi Paket & Batas Layanan (Single Source of Truth)
    |--------------------------------------------------------------------------
    |
    | Mendefinisikan paket, batasan numerik, dan fitur yang tersedia.
    | Seluruh pemeriksaan hak akses di backend mengacu pada konfigurasi ini.
    |
    */
    'plans' => [
        'free' => [
            'name' => 'free',
            'label' => 'Paket Free',
            'display_name' => 'Mulai Rental (Free)',
            'price_idr' => 0,
            'description' => 'Paket dasar untuk pemilik rental yang baru memulai katalog online.',
            'limits' => [
                'active_items' => 15,
                'monthly_bookings' => 20,
                'staff_users' => 1,
                'blacklist_checks_per_month' => 10,
                'photos_per_item' => 5,
                'blacklist_entries' => 5,
            ],
            'features' => [
                'basic_calendar' => true,
                'smart_buffer' => true,
                'smart_buffer_calendar' => false,
                'manual_fine' => true,
                'fine_calculator' => false,
                'shared_blacklist_report' => false,
                'pdf_agreement_invoice' => false,
                'white_label' => false,
                'custom_wa_templates' => false,
                'export_reports' => false,
                'unlimited_items' => false,
                'unlimited_blacklist' => false,
                'multi_staff' => false,
                'priority_support' => false,
            ],
        ],
        'pro' => [
            'name' => 'pro',
            'label' => 'Paket Pro',
            'display_name' => 'Tumbuh & Aman (Pro)',
            'description' => 'Kapasitas penuh dan proteksi optimal untuk pemilik rental aktif.',
            'pricing' => [
                'monthly' => 15000,
                'quarterly' => 39000,
                'annually' => 129000,
            ],
            'limits' => [
                'active_items' => -1, // -1 menandakan tidak terbatas (unlimited)
                'monthly_bookings' => -1,
                'staff_users' => 5,
                'blacklist_checks_per_month' => -1,
                'photos_per_item' => 8,
                'blacklist_entries' => -1,
            ],
            'features' => [
                'basic_calendar' => true,
                'smart_buffer' => true,
                'smart_buffer_calendar' => true,
                'manual_fine' => true,
                'fine_calculator' => true,
                'shared_blacklist_report' => true,
                'pdf_agreement_invoice' => true,
                'white_label' => true,
                'custom_wa_templates' => true,
                'export_reports' => true,
                'unlimited_items' => true,
                'unlimited_blacklist' => true,
                'multi_staff' => true,
                'priority_support' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Toleransi Masa Tenggang (Grace Period)
    |--------------------------------------------------------------------------
    |
    | Jumlah hari toleransi setelah masa aktif berakhir sebelum di-downgrade otomatis ke Free.
    |
    */
    'grace_period_days' => 3,

    /*
    |--------------------------------------------------------------------------
    | Hari Peringatan Menjelang Kedaluwarsa
    |--------------------------------------------------------------------------
    */
    'warning_days' => [7, 1],

    /*
    |--------------------------------------------------------------------------
    | Kalimat Nilai Produk (Value Proposition) Resmi
    |--------------------------------------------------------------------------
    */
    'value_proposition' => 'Satu penyewa bermasalah yang tertangkap blacklist, atau satu jadwal bentrok yang terhindari, bisa menutup biaya langganan.',
];
