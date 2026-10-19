<?php

namespace Tests\Unit;

use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Services\BlacklistService;
use App\Services\IdentityHashService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlacklistServiceTest extends TestCase
{
    use RefreshDatabase;

    private BlacklistService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BlacklistService;
    }

    public function test_returns_empty_collection_if_renter_is_not_blacklisted(): void
    {
        $vendor = Vendor::factory()->create();
        $renter = Renter::factory()->create([
            'phone' => '081234567890',
            'nik' => '3271123456789012',
        ]);

        $matches = $this->service->getMatchesForRenter($renter, $vendor->id);

        $this->assertTrue($matches->isEmpty());
        $this->assertFalse($this->service->isBlacklisted($renter, $vendor->id));
    }

    public function test_matches_renter_by_phone_hash_consistently(): void
    {
        $vendor = Vendor::factory()->create();
        $rawPhone = '0812-9876-5432';
        $phoneHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, $rawPhone);

        $entry = BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Sering terlambat dan tidak merespon chat',
        ]);

        $renter = Renter::factory()->create([
            'phone' => '+62 812 9876 5432', // Variation of the same phone number
        ]);

        $matches = $this->service->getMatchesForRenter($renter, $vendor->id);

        $this->assertCount(1, $matches);
        $this->assertEquals($entry->id, $matches->first()->id);
        $this->assertTrue($this->service->isBlacklisted($renter, $vendor->id));
    }

    public function test_matches_renter_by_nik_hash(): void
    {
        $vendor = Vendor::factory()->create();
        $rawNik = '3271 9988 7766 5544';
        $nikHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_NIK, $rawNik);

        $entry = BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_NIK,
            'identity_hash' => $nikHash,
            'reason' => 'KTP palsu',
        ]);

        $renter = Renter::factory()->create([
            'phone' => '081111111111',
            'nik' => '3271998877665544',
        ]);

        $matches = $this->service->getMatchesForRenter($renter, $vendor->id);

        $this->assertCount(1, $matches);
        $this->assertEquals($entry->id, $matches->first()->id);
        $this->assertTrue($this->service->isBlacklisted($renter, $vendor->id));
    }

    public function test_matches_renter_by_instagram_hash(): void
    {
        $vendor = Vendor::factory()->create();
        $rawIg = '@Cosplay_Trouble';
        $igHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_INSTAGRAM, $rawIg);

        $entry = BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_INSTAGRAM,
            'identity_hash' => $igHash,
            'reason' => 'Akun IG sering sewa lalu ghosting',
        ]);

        $renter = Renter::factory()->create([
            'instagram_handle_hash' => $igHash,
        ]);

        $matches = $this->service->getMatchesForRenter($renter, $vendor->id);

        $this->assertCount(1, $matches);
        $this->assertEquals($entry->id, $matches->first()->id);
    }

    public function test_vendor_isolation_other_vendor_blacklist_does_not_match(): void
    {
        $vendorA = Vendor::factory()->create();
        $vendorB = Vendor::factory()->create();

        $rawPhone = '081234567899';
        $phoneHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, $rawPhone);

        // Blacklist belongs to Vendor B
        BlacklistEntry::create([
            'vendor_id' => $vendorB->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Vendor B blacklist entry',
        ]);

        // Renter with matching phone visits Vendor A
        $renter = Renter::factory()->create([
            'phone' => $rawPhone,
        ]);

        // Matches for Vendor A must be empty!
        $matchesA = $this->service->getMatchesForRenter($renter, $vendorA->id);
        $this->assertTrue($matchesA->isEmpty());
        $this->assertFalse($this->service->isBlacklisted($renter, $vendorA->id));

        // Matches for Vendor B must find the entry
        $matchesB = $this->service->getMatchesForRenter($renter, $vendorB->id);
        $this->assertCount(1, $matchesB);
        $this->assertTrue($this->service->isBlacklisted($renter, $vendorB->id));
    }

    public function test_does_not_match_expired_blacklist_entries(): void
    {
        $vendor = Vendor::factory()->create();
        $rawPhone = '081200001111';
        $phoneHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, $rawPhone);

        // Entry expired yesterday
        BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Kasus lama sudah diselesaikan',
            'expires_at' => Carbon::now('Asia/Jakarta')->subDay(),
        ]);

        $renter = Renter::factory()->create([
            'phone' => $rawPhone,
        ]);

        $matches = $this->service->getMatchesForRenter($renter, $vendor->id);
        $this->assertTrue($matches->isEmpty());
        $this->assertFalse($this->service->isBlacklisted($renter, $vendor->id));
    }

    public function test_is_booking_blacklisted_helper(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $rawPhone = '081399998888';
        $phoneHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, $rawPhone);

        BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Merusak properti kamera',
        ]);

        $renter = Renter::factory()->create(['phone' => $rawPhone]);
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
        ]);

        $this->assertTrue($this->service->isBookingBlacklisted($booking));
        $matches = $this->service->getMatchesForBooking($booking);
        $this->assertCount(1, $matches);
        $this->assertEquals('Merusak properti kamera', $matches->first()->reason);
    }
}
