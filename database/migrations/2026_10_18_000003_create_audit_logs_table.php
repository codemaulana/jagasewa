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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('vendor_users')->nullOnDelete();
            $table->string('event', 100);
            $table->string('entity_type', 100);
            $table->string('entity_id', 100);
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['vendor_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
