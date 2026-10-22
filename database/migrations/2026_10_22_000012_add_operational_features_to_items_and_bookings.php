<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ekstensi tabel items untuk operasional katalog (kategori, kode, paket, kondisi, unit status)
        Schema::table('items', function (Blueprint $table) {
            $table->string('category', 60)->default('Lainnya')->after('name');
            $table->string('item_code', 50)->nullable()->after('category');
            $table->unsignedInteger('package_price_3d')->nullable()->after('price_per_day'); // IDR integer harga paket 3 hari
            $table->string('condition', 50)->default('Sangat Baik')->after('size'); // Sangat Baik, Baik, Ada Bekas Pakai, Perlu Perhatian
            $table->string('unit_status', 30)->default('available')->after('is_active'); // available, rented, laundry, maintenance

            $table->index(['vendor_id', 'category']);
            $table->index(['vendor_id', 'unit_status']);
        });

        // 2. Ekstensi tabel bookings untuk pembayaran DP/lunas, serah-terima, checklist fisik, dan foto
        Schema::table('bookings', function (Blueprint $table) {
            // Kolom Pembayaran DP & Pelunasan
            $table->unsignedInteger('down_payment_amount')->default(0)->after('deposit_paid');
            $table->boolean('is_dp_paid')->default(false)->after('down_payment_amount');
            $table->timestamp('dp_paid_at')->nullable()->after('is_dp_paid');
            $table->boolean('is_paid_in_full')->default(false)->after('dp_paid_at');
            $table->timestamp('paid_in_full_at')->nullable()->after('is_paid_in_full');
            $table->string('payment_proof_path')->nullable()->after('paid_in_full_at');
            $table->text('payment_notes')->nullable()->after('payment_proof_path');

            // Kolom Serah-Terima (Handover Sebelum Sewa)
            $table->string('handover_method', 60)->nullable()->after('payment_notes'); // pickup, instant_courier, delivery_courier
            $table->string('tracking_number', 100)->nullable()->after('handover_method'); // nomor resi / tracking link
            $table->json('handover_checklist')->nullable()->after('tracking_number'); // checklist kondisi & kelengkapan awal
            $table->text('handover_notes')->nullable()->after('handover_checklist');
            $table->json('handover_photos')->nullable()->after('handover_notes'); // foto kondisi sebelum sewa

            // Kolom Pengembalian & Inspeksi Kerusakan (Return Sesudah Sewa)
            $table->json('return_checklist')->nullable()->after('handover_photos'); // checklist kondisi saat kembali
            $table->text('return_notes')->nullable()->after('return_checklist');
            $table->json('return_photos')->nullable()->after('return_notes'); // foto kondisi sesudah sewa / bukti kerusakan
        });

        // 3. Ekstensi tabel vendors untuk preferensi jeda buffer per kategori [P]
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('category_buffer_settings')->nullable()->after('address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['category_buffer_settings']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'down_payment_amount',
                'is_dp_paid',
                'dp_paid_at',
                'is_paid_in_full',
                'paid_in_full_at',
                'payment_proof_path',
                'payment_notes',
                'handover_method',
                'tracking_number',
                'handover_checklist',
                'handover_notes',
                'handover_photos',
                'return_checklist',
                'return_notes',
                'return_photos',
            ]);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'category']);
            $table->dropIndex(['vendor_id', 'unit_status']);
            $table->dropColumn([
                'category',
                'item_code',
                'package_price_3d',
                'condition',
                'unit_status',
            ]);
        });
    }
};
