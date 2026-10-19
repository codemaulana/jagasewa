<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Helpers\WaNotificationHelper;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Settlement;
use App\Models\Vendor;
use App\Models\VendorUser;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBookingWaNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_approving_booking_dispatches_notification_with_valid_whatsapp_url(): void
    {
        $vendor = Vendor::factory()->create([
            'is_active' => true,
            'name' => 'Rental Cosplay Bandung',
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Sailor Moon M',
        ]);
        $renter = Renter::factory()->create([
            'name' => 'Adinda Putri',
            'phone' => '081234567890',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-APPW',
            'status' => Booking::STATUS_PENDING,
            'start_date' => Carbon::parse('2026-10-25'),
            'end_date' => Carbon::parse('2026-10-27'),
            'total_days' => 3,
            'lock_version' => 1,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('approve')
            ->callAction('approve')
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_APPROVED, $booking->status);

        // Verify generated WhatsApp URL
        $waUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');
        $this->assertNotNull($waUrl);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $waUrl);

        $decodedQuery = rawurldecode(parse_url($waUrl, PHP_URL_QUERY));
        $this->assertStringContainsString('DISETUJUI', $decodedQuery);
        $this->assertStringContainsString('JS-20261019-APPW', $decodedQuery);
        $this->assertStringContainsString('Kostum Sailor Moon M', $decodedQuery);
        $this->assertStringContainsString('Rental Cosplay Bandung', $decodedQuery);
        $this->assertStringContainsString('Adinda Putri', $decodedQuery);
    }

    public function test_rejecting_booking_dispatches_notification_with_valid_whatsapp_url(): void
    {
        $vendor = Vendor::factory()->create([
            'is_active' => true,
            'name' => 'Kamera Rental Jakarta',
        ]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Sony FX3 Cinema Line',
        ]);
        $renter = Renter::factory()->create([
            'name' => 'Rizky Ramadhan',
            'phone' => '+62 898-7654-3210',
        ]);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-REJW',
            'status' => Booking::STATUS_PENDING,
            'lock_version' => 1,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('reject')
            ->callAction('reject', [
                'rejection_reason' => 'Unit kamera sedang dalam kalibrasi sensor.',
            ])
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_REJECTED, $booking->status);

        // Verify generated WhatsApp URL
        $waUrl = WaNotificationHelper::generateRenterChatUrl($booking, 'rejected');
        $this->assertNotNull($waUrl);
        $this->assertStringStartsWith('https://wa.me/6289876543210?text=', $waUrl);

        $decodedQuery = rawurldecode(parse_url($waUrl, PHP_URL_QUERY));
        $this->assertStringContainsString('belum dapat kami setujui', $decodedQuery);
        $this->assertStringContainsString('JS-20261019-REJW', $decodedQuery);
        $this->assertStringContainsString('Sony FX3 Cinema Line', $decodedQuery);
    }

    public function test_header_action_contact_wa_renders_appropriate_url_for_approved_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Genshin Xiao']);
        $renter = Renter::factory()->create(['phone' => '081299988877']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-APPR',
            'status' => Booking::STATUS_APPROVED,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $component = Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('contact_wa');

        $action = $component->instance()->getAction('contact_wa');
        $this->assertNotNull($action);

        $url = $action->getUrl();
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/6281299988877?text=', $url);

        $decoded = rawurldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('DISETUJUI', $decoded);
        $this->assertStringContainsString('JS-20261019-APPR', $decoded);
    }

    public function test_header_action_contact_wa_renders_appropriate_url_for_rejected_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Fuji XT4']);
        $renter = Renter::factory()->create(['phone' => '081344455566']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-REJD',
            'status' => Booking::STATUS_REJECTED,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $component = Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('contact_wa');

        $action = $component->instance()->getAction('contact_wa');
        $this->assertNotNull($action);

        $url = $action->getUrl();
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/6281344455566?text=', $url);

        $decoded = rawurldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('belum dapat kami setujui', $decoded);
        $this->assertStringContainsString('JS-20261019-REJD', $decoded);
    }

    public function test_header_action_contact_wa_renders_appropriate_url_for_closed_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Kebaya Modern']);
        $renter = Renter::factory()->create(['phone' => '081577788899']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-CLSD',
            'status' => Booking::STATUS_CLOSED,
        ]);

        Settlement::factory()->create([
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'deposit_remaining' => 50000,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $component = Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('contact_wa');

        $action = $component->instance()->getAction('contact_wa');
        $this->assertNotNull($action);

        $url = $action->getUrl();
        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/6281577788899?text=', $url);

        $decoded = rawurldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('telah SELESAI', $decoded);
        $this->assertStringContainsString('Rp 50.000', $decoded);
    }

    public function test_whatsapp_url_returns_null_safely_when_renter_has_invalid_phone(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $renter = Renter::factory()->create(['phone' => 'invalid-phone']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'renter_id' => $renter->id,
        ]);

        $url = WaNotificationHelper::generateRenterChatUrl($booking, 'approved');
        $this->assertNull($url);
    }
}
