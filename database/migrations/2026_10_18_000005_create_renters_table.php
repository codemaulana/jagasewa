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
        Schema::create('renters', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->string('phone_hash')->index(); // Blind index HMAC-SHA256 for fast lookup
            $table->text('phone_encrypted'); // Laravel encrypted string
            $table->string('nik_hash')->nullable()->index(); // Blind index HMAC-SHA256
            $table->text('nik_encrypted')->nullable(); // Laravel encrypted string
            $table->string('instagram_handle_hash')->nullable()->index(); // Blind index
            $table->text('address_encrypted')->nullable(); // Laravel encrypted string
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renters');
    }
};
