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
        Schema::create('blacklist_entries', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('identity_type', 20); // 'wa' | 'nik' | 'instagram'
            $table->string('identity_hash', 64); // HMAC-SHA256 hex string
            $table->string('scope', 20)->default('private'); // 'private' in MVP, 'shared' in Fase 2
            $table->text('reason');

            // Fase 2 fields (nullable in MVP)
            $table->text('evidence')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('consent_basis', 100)->nullable();

            $table->timestamps();

            // Constraint: one entry per identity_hash per vendor
            $table->unique(['vendor_id', 'identity_hash'], 'uq_blacklist_vendor_hash');

            // Indexes for fast lookup & tenant filtering
            $table->index('vendor_id', 'idx_blacklist_vendor_id');
            $table->index(['vendor_id', 'identity_type'], 'idx_blacklist_vendor_type');
            $table->index(['vendor_id', 'identity_hash'], 'idx_blacklist_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklist_entries');
    }
};
