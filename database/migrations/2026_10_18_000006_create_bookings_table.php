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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('reference_code')->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->unsignedInteger('lock_version')->default(1);
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('renters')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_days');
            $table->unsignedInteger('total_price'); // IDR integer rupiah
            $table->unsignedInteger('deposit_paid')->default(0); // IDR integer rupiah
            $table->unsignedInteger('fine_amount')->default(0); // IDR integer rupiah
            $table->string('status', 30)->default('pending');
            $table->timestamp('pending_expires_at')->nullable();
            $table->text('vendor_notes')->nullable();
            $table->text('renter_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Indexes for fast lookups & calendar availability queries
            $table->index(['vendor_id', 'status']);
            $table->index(['item_id', 'status', 'start_date', 'end_date'], 'idx_bookings_item_status_dates');
            $table->index('pending_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
