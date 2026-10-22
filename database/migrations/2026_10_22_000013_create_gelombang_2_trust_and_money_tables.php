<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Gelombang 2: Kepercayaan dan Uang.
     */
    public function up(): void
    {
        // 1. Tabel Pelanggan / Penyewa Milik Vendor (VendorRenters)
        Schema::create('vendor_renters', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('renters')->cascadeOnDelete();
            $table->text('internal_notes')->nullable();
            $table->string('reliability_tag', 30)->default('normal'); // 'trusted', 'normal', 'caution', 'problematic'
            $table->unsignedInteger('total_completed_rentals')->default(0);
            $table->unsignedBigInteger('total_spent')->default(0); // Integer rupiah
            $table->timestamps();

            $table->unique(['vendor_id', 'renter_id'], 'uq_vendor_renter');
            $table->index('vendor_id', 'idx_vendor_renters_vendor');
            $table->index(['vendor_id', 'reliability_tag'], 'idx_vendor_renters_reliability');
        });

        // 2. Tambahkan Kolom Verifikasi, Masked Identity, & Sanggahan pada Blacklist Entries
        Schema::table('blacklist_entries', function (Blueprint $table) {
            $table->string('masked_identity', 60)->nullable()->after('identity_hash');
            $table->string('verification_status', 30)->default('verified')->after('scope'); // 'verified', 'pending_review', 'disputed', 'rejected'
            $table->string('evidence_file_path', 255)->nullable()->after('evidence');
            $table->boolean('is_disputed')->default(false)->after('evidence_file_path');
            $table->text('dispute_reason')->nullable()->after('is_disputed');
            $table->timestamp('disputed_at')->nullable()->after('dispute_reason');
            $table->string('dispute_status', 30)->nullable()->after('disputed_at'); // 'pending', 'accepted', 'rejected'

            $table->index('scope', 'idx_blacklist_scope');
            $table->index(['scope', 'identity_hash'], 'idx_blacklist_scope_hash');
        });

        // 3. Tabel Pelacak Kuota Cek Blacklist per Bulan (Free Kuota 10/Bulan)
        Schema::create('vendor_blacklist_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('month_year', 7); // Format: 'YYYY-MM' (contoh: '2026-10')
            $table->unsignedInteger('checks_count')->default(0);
            $table->timestamps();

            $table->unique(['vendor_id', 'month_year'], 'uq_vendor_blacklist_checks_month');
            $table->index('vendor_id', 'idx_vbc_vendor');
        });

        // 4. Tambahkan Aturan Denda Vendor pada Tabel Vendors
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('fine_rules')->nullable()->after('category_buffer_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('fine_rules');
        });

        Schema::dropIfExists('vendor_blacklist_checks');

        Schema::table('blacklist_entries', function (Blueprint $table) {
            $table->dropIndex('idx_blacklist_scope_hash');
            $table->dropIndex('idx_blacklist_scope');
            $table->dropColumn([
                'masked_identity',
                'verification_status',
                'evidence_file_path',
                'is_disputed',
                'dispute_reason',
                'disputed_at',
                'dispute_status',
            ]);
        });

        Schema::dropIfExists('vendor_renters');
    }
};
