<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Renter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RenterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_renter_with_factory(): void
    {
        $renter = Renter::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'nik' => '9999123456789012',
            'address' => 'Jl. Merdeka No. 45, Bandung',
        ]);

        $this->assertDatabaseHas('renters', [
            'id' => $renter->id,
            'name' => 'Budi Santoso',
        ]);

        $this->assertNotNull($renter->ulid);
        $this->assertEquals(26, strlen($renter->ulid));
        $this->assertEquals('081234567890', $renter->phone);
        $this->assertEquals('9999123456789012', $renter->nik);
        $this->assertEquals('Jl. Merdeka No. 45, Bandung', $renter->address);
    }

    public function test_pii_is_encrypted_in_database(): void
    {
        $plaintextPhone = '081987654321';
        $plaintextNik = '9999876543210987';
        $plaintextAddress = 'Jl. Anggrek No. 10, Jakarta';

        $renter = Renter::factory()->create([
            'phone' => $plaintextPhone,
            'nik' => $plaintextNik,
            'address' => $plaintextAddress,
        ]);

        // Direct DB inspection bypassing Eloquent decryption
        $rawRecord = DB::table('renters')->where('id', $renter->id)->first();

        $this->assertNotEquals($plaintextPhone, $rawRecord->phone_encrypted);
        $this->assertNotEquals($plaintextNik, $rawRecord->nik_encrypted);
        $this->assertNotEquals($plaintextAddress, $rawRecord->address_encrypted);

        // Raw encrypted values should be long ciphertexts, not plain numbers
        $this->assertGreaterThan(40, strlen($rawRecord->phone_encrypted));
        $this->assertGreaterThan(40, strlen($rawRecord->nik_encrypted));
        $this->assertGreaterThan(40, strlen($rawRecord->address_encrypted));

        // Eloquent model access decrypts properly
        $freshRenter = Renter::find($renter->id);
        $this->assertEquals($plaintextPhone, $freshRenter->phone);
        $this->assertEquals($plaintextNik, $freshRenter->nik);
        $this->assertEquals($plaintextAddress, $freshRenter->address);
    }

    public function test_blind_index_phone_hash_and_nik_hash_are_automatically_generated(): void
    {
        $renter = Renter::factory()->create([
            'phone' => '0812-3456-7890',
            'nik' => '9999 1234 5678 9012',
        ]);

        $expectedPhoneHash = Renter::hashValue('6281234567890');
        $expectedNikHash = Renter::hashValue('9999123456789012');

        $this->assertEquals($expectedPhoneHash, $renter->phone_hash);
        $this->assertEquals($expectedNikHash, $renter->nik_hash);

        // Formats with +62, 08, dashes, or spaces yield the exact same deterministic hash
        $this->assertEquals($renter->phone_hash, Renter::hashValue(Renter::normalizePhone('+62 812-3456-7890')));
        $this->assertEquals($renter->phone_hash, Renter::hashValue(Renter::normalizePhone('081234567890')));
    }

    public function test_renter_bookings_relationship(): void
    {
        $renter = Renter::factory()->create();
        $booking = Booking::factory()->create(['renter_id' => $renter->id]);

        $this->assertInstanceOf(Booking::class, $renter->bookings->first());
        $this->assertEquals($booking->id, $renter->bookings->first()->id);
        $this->assertTrue($renter->bookings->contains($booking));
    }

    public function test_renters_migration_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('renters'));

        // Rollback identity_documents and bookings first (FK dependents)
        if (Schema::hasTable('identity_documents')) {
            $this->artisan('migrate:rollback', [
                '--path' => 'database/migrations/2026_10_18_000007_create_identity_documents_table.php',
            ])->assertSuccessful();
        }

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_18_000006_create_bookings_table.php',
        ])->assertSuccessful();

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_18_000005_create_renters_table.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('renters'));

        // Re-run migrations for subsequent tests
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000005_create_renters_table.php',
        ])->assertSuccessful();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000006_create_bookings_table.php',
        ])->assertSuccessful();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_18_000007_create_identity_documents_table.php',
        ])->assertSuccessful();
    }
}
