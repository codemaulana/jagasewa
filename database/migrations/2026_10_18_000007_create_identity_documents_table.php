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
        Schema::create('identity_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('renter_id')->constrained('renters')->cascadeOnDelete();
            $table->string('doc_type', 20); // 'ktp' | 'selfie'
            $table->string('storage_path');
            $table->string('storage_disk', 30)->default('s3');
            $table->unsignedInteger('file_size')->nullable(); // bytes
            $table->string('mime_type', 50)->nullable(); // image/jpeg, image/png
            $table->boolean('consent_given')->default(true);
            $table->string('consent_version', 20)->default('v1.0');
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('delete_scheduled_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            // Indexes for retention cleanup & foreign lookups
            $table->index(['delete_scheduled_at', 'deleted_at'], 'idx_identity_docs_retention');
            $table->index(['booking_id', 'doc_type']);
            $table->index('renter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_documents');
    }
};
