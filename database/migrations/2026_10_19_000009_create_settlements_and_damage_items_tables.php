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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->date('actual_return_date');
            $table->integer('late_days')->default(0);
            $table->integer('late_fine')->default(0); // IDR integer
            $table->integer('damage_deduction')->default(0); // IDR integer
            $table->integer('total_deduction')->default(0); // IDR integer (late_fine + damage_deduction)
            $table->integer('deposit_remaining')->default(0); // IDR integer (can be negative if shortfall)
            $table->text('damage_notes')->nullable();
            $table->timestamps();

            // Indexes for multi-tenant queries & reporting
            $table->index('vendor_id', 'idx_settlements_vendor_id');
            $table->index(['vendor_id', 'actual_return_date'], 'idx_settlements_vendor_date');
        });

        Schema::create('damage_items', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->string('description');
            $table->integer('repair_cost')->default(0); // IDR integer
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index('settlement_id', 'idx_damage_items_settlement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_items');
        Schema::dropIfExists('settlements');
    }
};
