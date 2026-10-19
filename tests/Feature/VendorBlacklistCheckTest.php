<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Models\BlacklistEntry;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\IdentityHashService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBlacklistCheckTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_booking_with_blacklisted_renter_displays_warning_section_badge_and_notification(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $rawPhone = '0812-3456-7890';
        $phoneHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_WA, $rawPhone);

        // Vendor blacklists this phone
        BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Merusak lensa kamera dan memblokir kontak WA.',
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Bambang Kusuma',
            'phone' => $rawPhone,
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-WARN',
            'status' => Booking::STATUS_PENDING,
        ]);

        // HTTP GET request test
        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get("/vendor/bookings/{$booking->ulid}");

        $response->assertStatus(200);
        $response->assertSee('Peringatan: Penyewa Terdaftar di Blacklist');
        $response->assertSee('Merusak lensa kamera dan memblokir kontak WA.');
        $response->assertSee('Nomor WhatsApp');
        $response->assertSee('Terdaftar di Blacklist');

        // Livewire component mount & notification test
        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertNotified('Peringatan: Penyewa Terdaftar di Blacklist');
    }

    public function test_booking_with_blacklisted_nik_displays_warning(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $rawNik = '3271123456789999';
        $nikHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_NIK, $rawNik);

        BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_NIK,
            'identity_hash' => $nikHash,
            'reason' => 'Identitas KTP bermasalah pada rental sebelumnya.',
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Siti Nurhaliza',
            'phone' => '087700001111',
            'nik' => $rawNik,
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get("/vendor/bookings/{$booking->ulid}");

        $response->assertStatus(200);
        $response->assertSee('Peringatan: Penyewa Terdaftar di Blacklist');
        $response->assertSee('Identitas KTP bermasalah pada rental sebelumnya.');
        $response->assertSee('NIK KTP');
        $response->assertSee('Terdaftar di Blacklist');

        // Livewire notification test
        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertNotified('Peringatan: Penyewa Terdaftar di Blacklist');
    }

    public function test_booking_with_clean_renter_does_not_display_warning_and_shows_clean_badge(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $renter = Renter::factory()->create([
            'name' => 'Rina Gunawan',
            'phone' => '085512345678',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-CLEAN',
            'status' => Booking::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user, 'vendor')
            ->withSession(['vendor_id' => $vendor->id])
            ->get("/vendor/bookings/{$booking->ulid}");

        $response->assertStatus(200);
        $response->assertDontSee('Peringatan: Penyewa Terdaftar di Blacklist');
        $response->assertDontSee('Terdaftar di Blacklist');
        $response->assertSee('Bersih (Bebas Blacklist)');

        // Livewire test: no warning notification
        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertNotNotified();
    }

    public function test_cross_vendor_isolation_vendor_b_blacklist_does_not_affect_vendor_a_booking(): void
    {
        $vendorA = Vendor::factory()->create(['is_active' => true]);
        $userA = VendorUser::factory()->create(['vendor_id' => $vendorA->id]);
        $itemA = Item::factory()->create(['vendor_id' => $vendorA->id]);

        $vendorB = Vendor::factory()->create(['is_active' => true]);

        $phone = '081299990000';
        $phoneHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_WA, $phone);

        // Vendor B blacklisted this phone
        BlacklistEntry::create([
            'vendor_id' => $vendorB->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Vendor B blacklist entry: kostum robek parah',
        ]);

        // Renter with the same phone books at Vendor A
        $renter = Renter::factory()->create([
            'phone' => $phone,
        ]);

        $bookingA = Booking::factory()->create([
            'vendor_id' => $vendorA->id,
            'item_id' => $itemA->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
        ]);

        $response = $this->actingAs($userA, 'vendor')
            ->withSession(['vendor_id' => $vendorA->id])
            ->get("/vendor/bookings/{$bookingA->ulid}");

        $response->assertStatus(200);
        // Vendor A must NOT see Vendor B's warning or reason!
        $response->assertDontSee('Peringatan: Penyewa Terdaftar di Blacklist');
        $response->assertDontSee('Vendor B blacklist entry');
        $response->assertDontSee('Terdaftar di Blacklist');
        $response->assertSee('Bersih (Bebas Blacklist)');

        // Livewire test: no notification for Vendor A
        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(ViewBooking::class, ['record' => $bookingA->ulid])
            ->assertNotNotified();
    }

    public function test_vendor_can_still_approve_or_reject_blacklisted_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);

        $phone = '081344445555';
        $phoneHash = IdentityHashService::hashIdentity(BlacklistEntry::TYPE_WA, $phone);

        BlacklistEntry::create([
            'vendor_id' => $vendor->id,
            'identity_type' => BlacklistEntry::TYPE_WA,
            'identity_hash' => $phoneHash,
            'reason' => 'Catatan blacklist lama',
        ]);

        $renter = Renter::factory()->create([
            'name' => 'Ahmad Dahlan',
            'phone' => $phone,
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_PENDING,
            'lock_version' => 1,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        // Vendor decides to approve anyway (US-10 / BR-04: keputusan tetap di tangan vendor)
        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('approve')
            ->callAction('approve')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);
        $this->assertEquals(2, $booking->lock_version);
    }
}
