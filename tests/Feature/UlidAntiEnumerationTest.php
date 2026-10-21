<?php

namespace Tests\Feature;

use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\DamageItem;
use App\Models\IdentityDocument;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Settlement;
use App\Models\Vendor;
use App\Models\VendorUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UlidAntiEnumerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to verify string is a valid Crockford Base32 ULID (26 chars, [0-9A-HJKMNP-TV-Z]).
     */
    protected function assertIsValidUlid(string $value): void
    {
        $this->assertEquals(26, strlen($value), "Expected ULID length to be 26, got: {$value}");
        $this->assertMatchesRegularExpression('/^[0-9A-HJ-KM-NP-TV-Z]{26}$/i', $value, "String is not a valid ULID: {$value}");
    }

    /**
     * SEC-10 & IDOR-10: Anonymous renter attempts to enumerate bookings with sequential numeric ID.
     */
    public function test_idor_10_anonymous_renter_cannot_enumerate_bookings_with_numeric_id(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
        ]);

        // Attempting to access booking via sequential numeric ID must return 404
        $responseNumeric = $this->get("/b/success/{$booking->id}");
        $responseNumeric->assertStatus(404);

        // Accessing booking via ULID succeeds with 200
        $responseUlid = $this->get("/b/success/{$booking->ulid}");
        $responseUlid->assertStatus(200);

        // Non-existent arbitrary sequential integer ID also returns 404
        $this->get('/b/success/99999')->assertStatus(404);
        $this->get('/api/public/bookings/12345')->assertStatus(404);
    }

    /**
     * SEC-10: Item availability endpoint only accepts ULID and blocks sequential integer ID.
     */
    public function test_item_availability_endpoint_only_accepts_ulid_and_rejects_numeric_id(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        // Query by numeric ID -> 404
        $resNumeric = $this->getJson("/api/items/{$item->id}/availability");
        $resNumeric->assertStatus(404);

        // Query by ULID -> 200
        $resUlid = $this->getJson("/api/items/{$item->ulid}/availability");
        $resUlid->assertStatus(200);
    }

    /**
     * SEC-10 & IDOR-03: Vendor identity document endpoints reject sequential numeric ID.
     */
    public function test_vendor_document_endpoints_reject_numeric_id_and_only_accept_ulid(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $vendorUser = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);
        $doc = IdentityDocument::factory()->create([
            'booking_id' => $booking->id,
            'renter_id' => $booking->renter_id,
        ]);

        // Attempt with numeric ID -> 404
        $responseNumeric = $this->actingAs($vendorUser, 'vendor')
            ->postJson("/vendor/identity-documents/{$doc->id}/signed-url");
        $responseNumeric->assertStatus(404);

        // Attempt with valid ULID -> 200
        $responseUlid = $this->actingAs($vendorUser, 'vendor')
            ->postJson("/vendor/identity-documents/{$doc->ulid}/signed-url");
        $responseUlid->assertStatus(200);
        $responseUlid->assertJsonPath('data.ulid', $doc->ulid);
        $this->assertArrayNotHasKey('id', $responseUlid->json('data'));
    }

    /**
     * SEC-10: Upload document payload accepts booking_ulid, rejects numeric booking ID.
     */
    public function test_document_upload_rejects_numeric_booking_id(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $booking = Booking::factory()->create(['vendor_id' => $vendor->id]);

        $file = UploadedFile::fake()->image('ktp.jpg', 600, 400);

        // Attempting with numeric booking ID must fail exists:bookings,ulid validation
        $responseNumeric = $this->postJson(route('public.booking.upload'), [
            'booking_ulid' => (string) $booking->id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => true,
        ]);

        $responseNumeric->assertStatus(422);
        $responseNumeric->assertJsonValidationErrors(['booking_ulid']);

        // Attempting with ULID succeeds and response only returns ULID
        $responseUlid = $this->postJson(route('public.booking.upload'), [
            'booking_ulid' => $booking->ulid,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'file' => $file,
            'consent' => true,
        ]);

        $responseUlid->assertStatus(201);
        $responseUlid->assertJsonPath('data.ulid', fn ($val) => ! empty($val) && strlen($val) === 26);
        $this->assertArrayNotHasKey('id', $responseUlid->json('data'));
    }

    /**
     * SEC-10: All models with ULID automatically generate valid ULIDs on creation.
     */
    public function test_all_models_auto_generate_valid_ulid_and_define_ulid_route_key(): void
    {
        $vendor = Vendor::factory()->create();
        $this->assertIsValidUlid($vendor->ulid);
        $this->assertEquals('ulid', $vendor->getRouteKeyName());

        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $this->assertIsValidUlid($item->ulid);
        $this->assertEquals('ulid', $item->getRouteKeyName());

        $renter = Renter::factory()->create();
        $this->assertIsValidUlid($renter->ulid);
        $this->assertEquals('ulid', $renter->getRouteKeyName());

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'renter_id' => $renter->id,
            'item_id' => $item->id,
        ]);
        $this->assertIsValidUlid($booking->ulid);
        $this->assertEquals('ulid', $booking->getRouteKeyName());

        $doc = IdentityDocument::factory()->create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
        ]);
        $this->assertIsValidUlid($doc->ulid);
        $this->assertEquals('ulid', $doc->getRouteKeyName());

        $blacklist = BlacklistEntry::factory()->create([
            'vendor_id' => $vendor->id,
        ]);
        $this->assertIsValidUlid($blacklist->ulid);
        $this->assertEquals('ulid', $blacklist->getRouteKeyName());

        $settlement = Settlement::create([
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'actual_return_date' => '2026-10-15',
            'late_days' => 0,
            'late_fine' => 0,
            'damage_deduction' => 0,
            'total_deduction' => 0,
            'deposit_remaining' => 100000,
        ]);
        $this->assertIsValidUlid($settlement->ulid);
        $this->assertEquals('ulid', $settlement->getRouteKeyName());

        $damageItem = DamageItem::create([
            'settlement_id' => $settlement->id,
            'description' => 'Kancing jas lepas',
            'repair_cost' => 15000,
        ]);
        $this->assertIsValidUlid($damageItem->ulid);
        $this->assertEquals('ulid', $damageItem->getRouteKeyName());
    }

    /**
     * SEC-10: Public item availability API does not leak database sequential IDs in payload.
     */
    public function test_public_item_availability_does_not_leak_database_sequential_id(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");
        $response->assertStatus(200);

        $days = $response->json();
        $this->assertIsArray($days);

        foreach ($days as $day) {
            $this->assertArrayNotHasKey('id', $day);
            $this->assertArrayNotHasKey('item_id', $day);
            $this->assertArrayNotHasKey('vendor_id', $day);
            $this->assertArrayNotHasKey('booking_id', $day);
        }
    }
}
