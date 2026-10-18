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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('size', 50)->nullable(); // e.g. S, M, L, XL, Free Size (cosplay / apparel)
            $table->json('included_items')->nullable(); // e.g. ['Kostum', 'Wig', 'Aksesoris', 'Prop']
            $table->text('care_instructions')->nullable(); // e.g. Petunjuk pencucian / dry clean
            $table->json('photo_paths')->nullable(); // Array of S3 storage paths
            $table->unsignedInteger('price_per_day'); // IDR integer rupiah
            $table->unsignedInteger('deposit_amount')->default(0); // IDR integer rupiah
            $table->unsignedInteger('fine_per_day')->default(0); // IDR integer rupiah per hari telat
            $table->unsignedSmallInteger('buffer_before')->default(0); // Hari jeda sebelum sewa
            $table->unsignedSmallInteger('buffer_after')->default(0); // Hari jeda sesudah sewa
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vendor_id', 'slug']);
            $table->index(['vendor_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
