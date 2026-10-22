<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Gelombang 3: Pertumbuhan.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('custom_domain', 150)->nullable()->unique()->after('fine_rules');
            $table->boolean('custom_domain_verified')->default(false)->after('custom_domain');
            $table->boolean('white_label_enabled')->default(true)->after('custom_domain_verified');

            $table->index('custom_domain', 'idx_vendors_custom_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('idx_vendors_custom_domain');
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'custom_domain',
                'custom_domain_verified',
                'white_label_enabled',
            ]);
        });
    }
};
