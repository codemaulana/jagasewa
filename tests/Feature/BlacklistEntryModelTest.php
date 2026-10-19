<?php

namespace Tests\Feature;

use App\Models\BlacklistEntry;
use App\Models\Vendor;
use App\Models\VendorUser;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BlacklistEntryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_blacklist_entry_with_factory(): void
    {
        $vendor = Vendor::factory()->create();
        $hash = hash_hmac('sha256', '+628123456789', 'test-secret');

        $entry = BlacklistEntry::factory()->create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $hash,
            'reason' => 'Penyewa merusak kostum dan memblokir kontak.',
        ]);

        $this->assertDatabaseHas('blacklist_entries', [
            'id' => $entry->id,
            'vendor_id' => $vendor->id,
            'identity_type' => 'wa',
            'identity_hash' => $hash,
            'scope' => 'private',
            'reason' => 'Penyewa merusak kostum dan memblokir kontak.',
        ]);

        $this->assertNotNull($entry->ulid);
        $this->assertEquals(26, strlen($entry->ulid));
        $this->assertEquals('private', $entry->scope);
        $this->assertTrue($entry->vendor->is($vendor));
    }

    public function test_vendor_blacklist_entries_relationship(): void
    {
        $vendor = Vendor::factory()->create();
        $entries = BlacklistEntry::factory()->count(3)->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->assertCount(3, $vendor->blacklistEntries);
        $this->assertTrue($vendor->blacklistEntries->contains($entries->first()));
    }

    public function test_phase2_columns_are_nullable_in_mvp(): void
    {
        $entry = BlacklistEntry::factory()->create();

        $this->assertNull($entry->evidence);
        $this->assertNull($entry->expires_at);
        $this->assertNull($entry->consent_basis);
    }

    public function test_phase2_columns_can_be_stored_when_provided(): void
    {
        $entry = BlacklistEntry::factory()->shared()->create();

        $this->assertEquals(BlacklistEntry::SCOPE_SHARED, $entry->scope);
        $this->assertNotNull($entry->evidence);
        $this->assertNotNull($entry->expires_at);
        $this->assertNotNull($entry->consent_basis);
        $this->assertInstanceOf(Carbon::class, $entry->expires_at);
    }

    public function test_unique_constraint_per_vendor_and_identity_hash(): void
    {
        $vendorA = Vendor::factory()->create();
        $vendorB = Vendor::factory()->create();
        $hash = hash_hmac('sha256', '+628123456789', 'secret');

        // First entry for vendor A succeeds
        BlacklistEntry::factory()->create([
            'vendor_id' => $vendorA->id,
            'identity_hash' => $hash,
        ]);

        // Same hash for different vendor B also succeeds
        $entryVendorB = BlacklistEntry::factory()->create([
            'vendor_id' => $vendorB->id,
            'identity_hash' => $hash,
        ]);
        $this->assertNotNull($entryVendorB->id);

        // Duplicate entry for vendor A must throw QueryException
        $this->expectException(QueryException::class);
        BlacklistEntry::factory()->create([
            'vendor_id' => $vendorA->id,
            'identity_hash' => $hash,
        ]);
    }

    public function test_belongs_to_vendor_isolation(): void
    {
        $vendorA = Vendor::factory()->create();
        $vendorB = Vendor::factory()->create();

        $userA = VendorUser::factory()->create([
            'vendor_id' => $vendorA->id,
        ]);

        $entryA = BlacklistEntry::factory()->create(['vendor_id' => $vendorA->id]);
        $entryB = BlacklistEntry::factory()->create(['vendor_id' => $vendorB->id]);

        $this->actingAs($userA, 'vendor');

        $scopedEntries = BlacklistEntry::forCurrentVendor()->get();

        $this->assertTrue($scopedEntries->contains($entryA));
        $this->assertFalse($scopedEntries->contains($entryB));
    }

    public function test_blacklist_entries_migration_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('blacklist_entries'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_19_000008_create_blacklist_entries_table.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('blacklist_entries'));

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_19_000008_create_blacklist_entries_table.php',
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('blacklist_entries'));
    }
}
