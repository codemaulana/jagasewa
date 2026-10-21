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
        // 1. Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('plan', 20)->default('free'); // 'free', 'pro'
            $table->string('status', 30)->default('active'); // 'active', 'past_due', 'cancelled', 'trialing', 'pending_payment', 'expired'
            $table->string('billing_cycle', 20)->default('monthly'); // 'monthly', 'quarterly', 'annually'
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_customer_id')->nullable();
            $table->timestamps();

            // Indexes for fast lookup & tenant filtering
            $table->index('vendor_id', 'idx_subscriptions_vendor_id');
            $table->index('status', 'idx_subscriptions_status');
            $table->index(['vendor_id', 'status'], 'idx_subscriptions_vendor_status');
            $table->index(['status', 'current_period_end'], 'idx_subscriptions_status_end');
        });

        // 2. Subscription Events Table (Webhook idempotency & audit)
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('event_type', 50); // e.g. 'invoice.paid', 'invoice.expired', 'subscription.created'
            $table->string('gateway_event_id', 100)->unique(); // UNIQUE constraint for idempotency
            $table->string('payload_hash', 64)->nullable(); // SHA-256 hash (no raw PII)
            $table->integer('amount_idr')->default(0); // Integer rupiah
            $table->string('status', 30)->default('processed'); // 'processed', 'ignored', 'failed'
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('subscription_id', 'idx_sub_events_subscription_id');
            $table->index('vendor_id', 'idx_sub_events_vendor_id');
            $table->index('gateway_event_id', 'idx_sub_events_gateway_event_id');
        });

        // 3. Manual Activations Table (Bank transfer verification by admin)
        Schema::create('manual_activations', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('activated_by')->constrained('users')->cascadeOnDelete(); // Platform admin user ID
            $table->integer('amount_paid_idr')->default(0); // Integer rupiah
            $table->text('notes')->nullable();
            $table->timestamp('activated_at')->useCurrent();
            $table->timestamps();

            $table->index('subscription_id', 'idx_manual_act_subscription_id');
            $table->index('vendor_id', 'idx_manual_act_vendor_id');
            $table->index('activated_by', 'idx_manual_act_activated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manual_activations');
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('subscriptions');
    }
};
