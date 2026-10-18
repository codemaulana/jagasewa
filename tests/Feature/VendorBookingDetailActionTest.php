<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\IdentityDocumentService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBookingDetailActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Storage::fake('s3');
    }

    public function test_authenticated_vendor_can_view_own_booking_detail_page(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Kostum Nezuko']);
        $renter = Renter::factory()->create(['name' => 'Anisa Rahma']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-VIEW',
            'status' => Booking::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get("/vendor/bookings/{$booking->ulid}");

        $response->assertStatus(200);
        $response->assertSee('JS-20261019-VIEW');
        $response->assertSee('Kostum Nezuko');
        $response->assertSee('Anisa Rahma');
    }

    public function test_vendor_a_cannot_view_vendor_b_booking_returns_404(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);
        $itemB = Item::factory()->create(['vendor_id' => $vendorB->id]);
        $renterB = Renter::factory()->create();

        $bookingB = Booking::factory()->create([
            'vendor_id' => $vendorB->id,
            'item_id' => $itemB->id,
            'renter_id' => $renterB->id,
            'reference_code' => 'JS-20261019-B001',
        ]);

        // Vendor A attempts to access Vendor B's booking
        $response = $this->actingAs($userA, 'vendor')
            ->withSession(['vendor_id' => $vendorA->id])
            ->get("/vendor/bookings/{$bookingB->ulid}");

        // IDOR-02 & BR-06: Must return 404, not 403
        $response->assertStatus(404);
    }

    public function test_vendor_can_approve_pending_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true, 'name' => 'Rental Cosplay Bandung']);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create(['name' => 'Budi Santoso', 'phone' => '081234567890']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'lock_version' => 1,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('approve')
            ->assertActionVisible('reject')
            ->callAction('approve')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);
        $this->assertNotNull($booking->approved_at);
        $this->assertEquals(2, $booking->lock_version);

        // Audit log check
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'booking_approved',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);

        // WhatsApp URL verification
        $waUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');
        $this->assertNotNull($waUrl);
        $this->assertStringContainsString('6281234567890', $waUrl);
        $this->assertStringContainsString('DISETUJUI', rawurldecode($waUrl));
    }

    public function test_vendor_can_reject_pending_booking_with_reason_and_reschedules_kyc_retention(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create(['name' => 'Dewi Lestari', 'phone' => '089876543210']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'lock_version' => 1,
        ]);

        // Create associated identity document
        $doc = IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => 'identity-documents/'.(string) Str::uuid().'.jpg',
            'storage_disk' => 's3',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta'),
            'delete_scheduled_at' => Carbon::now('Asia/Jakarta')->addDays(7), // initially 7 days
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('reject')
            ->callAction('reject', [
                'rejection_reason' => 'Barang sedang dalam jadwal pembersihan mendalam.',
            ])
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_REJECTED, $booking->status);
        $this->assertEquals('Barang sedang dalam jadwal pembersihan mendalam.', $booking->vendor_notes);
        $this->assertEquals(2, $booking->lock_version);

        // BR-05: Document retention must be rescheduled to 3 days
        $doc->refresh();
        $expectedRetentionDate = Carbon::now('Asia/Jakarta')->addDays(3)->format('Y-m-d');
        $this->assertEquals($expectedRetentionDate, $doc->delete_scheduled_at->format('Y-m-d'));

        // Audit log check
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'booking_rejected',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);

        // WhatsApp notification link verification
        $waUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'rejected');
        $this->assertNotNull($waUrl);
        $this->assertStringContainsString('6289876543210', $waUrl);
        $this->assertStringContainsString('belum dapat kami setujui', rawurldecode($waUrl));
    }

    public function test_approved_booking_hides_approve_and_reject_actions_but_shows_wa_button(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create(['phone' => '081234567890']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
            'approved_at' => Carbon::now('Asia/Jakarta')->subHours(2),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionHidden('approve')
            ->assertActionHidden('reject')
            ->assertActionVisible('contact_wa');
    }

    public function test_booking_detail_renders_signed_url_for_uploaded_identity_documents(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
        ]);

        $ktpPath = 'identity-documents/'.(string) Str::uuid().'.jpg';
        Storage::disk('s3')->put($ktpPath, 'ktp test image content');

        $selfiePath = 'identity-documents/'.(string) Str::uuid().'.jpg';
        Storage::disk('s3')->put($selfiePath, 'selfie test image content');

        $ktpDoc = IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => $ktpPath,
            'storage_disk' => 's3',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $selfieDoc = IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
            'doc_type' => IdentityDocument::DOC_TYPE_SELFIE,
            'storage_path' => $selfiePath,
            'storage_disk' => 's3',
            'file_size' => 2048,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $service = app(IdentityDocumentService::class);
        $ktpUrl = $service->generateTemporaryUrl($ktpDoc, 60);
        $selfieUrl = $service->generateTemporaryUrl($selfieDoc, 60);

        // Verify signed URLs are generated and contain signature parameter
        $this->assertStringContainsString('signature=', $ktpUrl);
        $this->assertStringContainsString('signature=', $selfieUrl);

        // Access secure view with signed URL
        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $response = $this->get($ktpUrl);
        $response->assertStatus(200);
        $this->assertEquals('ktp test image content', $response->getContent());
    }
}
