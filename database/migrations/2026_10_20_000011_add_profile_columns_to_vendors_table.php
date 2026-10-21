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
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('email');
            $table->string('banner_path')->nullable()->after('logo_path');
            $table->text('description')->nullable()->after('banner_path');
            $table->text('address')->nullable()->after('description');
            $table->text('bank_account_info')->nullable()->after('address');
            $table->json('custom_wa_templates')->nullable()->after('bank_account_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'logo_path',
                'banner_path',
                'description',
                'address',
                'bank_account_info',
                'custom_wa_templates',
            ]);
        });
    }
};
