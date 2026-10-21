<?php

namespace Tests\Feature;

use App\Filament\Vendor\Resources\Bookings\Pages\ViewBooking;
use App\Livewire\Vendor\BookingSettlementForm;
use App\Models\Booking;
use App\Models\IdentityDocument;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Settlement;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\BookingStatusService;
use Carbon\Carbon;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class VendorBookingSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
    }

    public function test_vendor_can_render_settlement_form_component_for_returned_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Kostum Sailor Moon Premium',
            'fine_per_day' => 50000,
            'deposit_amount' => 100000,
        ]);
        $renter = Renter::factory()->create(['name' => 'Siti Nurhaliza']);

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'reference_code' => 'JS-20261019-SETTLE1',
            'status' => Booking::STATUS_RETURNED,
            'deposit_paid' => 100000,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'total_days' => 3,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(BookingSettlementForm::class, ['booking' => $booking])
            ->assertStatus(200)
            ->assertSee('JS-20261019-SETTLE1')
            ->assertSee('Kostum Sailor Moon Premium')
            ->assertSee('Tanggal Aktual Barang Diterima')
            ->assertSee('Ringkasan Perhitungan Denda');
    }

    public function test_changing_return_date_recalculates_fines_dynamically(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'fine_per_day' => 50000,
            'deposit_amount' => 100000,
        ]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_RETURNED,
            'deposit_paid' => 100000,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'total_days' => 3,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $component = Livewire::test(BookingSettlementForm::class, ['booking' => $booking]);

        // Scenario DT-02: 2 days late (actual_return_date = 2026-10-22)
        $component->set('actual_return_date', '2026-10-22');
        $calcDt02 = $component->get('calculation');
        $this->assertEquals(2, $calcDt02->lateDays);
        $this->assertEquals(100000, $calcDt02->lateFine);
        $this->assertEquals(100000, $calcDt02->totalDeduction);
        $this->assertEquals(0, $calcDt02->depositRemaining);
        $this->assertFalse($calcDt02->hasRefund);
        $this->assertFalse($calcDt02->hasShortfall);

        // Scenario DT-01: Returned on time (2026-10-20)
        $component->set('actual_return_date', '2026-10-20');
        $calcDt01 = $component->get('calculation');
        $this->assertEquals(0, $calcDt01->lateDays);
        $this->assertEquals(0, $calcDt01->lateFine);
        $this->assertEquals(0, $calcDt01->totalDeduction);
        $this->assertEquals(100000, $calcDt01->depositRemaining);
        $this->assertTrue($calcDt01->hasRefund);
        $this->assertEquals(100000, $calcDt01->refund);
    }

    public function test_adding_and_removing_damage_items_dynamically_updates_calculation(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'fine_per_day' => 50000,
            'deposit_amount' => 150000,
        ]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_RETURNED,
            'deposit_paid' => 150000,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'total_days' => 3,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        $component = Livewire::test(BookingSettlementForm::class, ['booking' => $booking])
            ->set('actual_return_date', '2026-10-20')
            ->call('addDamageItem')
            ->set('damage_items.0.description', 'Kancing copot')
            ->set('damage_items.0.amount', 30000)
            ->call('addDamageItem')
            ->set('damage_items.1.description', 'Noda membandel')
            ->set('damage_items.1.amount', 20000);

        $calc = $component->get('calculation');
        $this->assertEquals(50000, $calc->damageDeduction);
        $this->assertEquals(50000, $calc->totalDeduction);
        $this->assertEquals(100000, $calc->depositRemaining);
        $this->assertTrue($calc->hasRefund);
        $this->assertEquals(100000, $calc->refund);

        // Remove item at index 1 (Noda membandel: 20000)
        $component->call('removeDamageItem', 1);

        $calcAfterRemove = $component->get('calculation');
        $this->assertEquals(30000, $calcAfterRemove->damageDeduction);
        $this->assertEquals(30000, $calcAfterRemove->totalDeduction);
        $this->assertEquals(120000, $calcAfterRemove->depositRemaining);
        $this->assertEquals(120000, $calcAfterRemove->refund);
    }

    public function test_submitting_settlement_creates_settlement_damage_items_and_closes_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'fine_per_day' => 50000,
            'deposit_amount' => 100000,
        ]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_RETURNED,
            'deposit_paid' => 100000,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'total_days' => 3,
            'lock_version' => 3,
        ]);

        $identityDoc = IdentityDocument::create([
            'booking_id' => $booking->id,
            'renter_id' => $renter->id,
            'doc_type' => IdentityDocument::DOC_TYPE_KTP,
            'storage_path' => 'identity-documents/'.(string) Str::uuid().'.jpg',
            'storage_disk' => 's3',
            'file_size' => 2048,
            'mime_type' => 'image/jpeg',
            'consent_given' => true,
            'consent_version' => 'v1.0',
            'consent_at' => Carbon::now('Asia/Jakarta'),
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(BookingSettlementForm::class, ['booking' => $booking])
            ->set('actual_return_date', '2026-10-20')
            ->call('addDamageItem')
            ->set('damage_items.0.description', 'Ritsleting rusak')
            ->set('damage_items.0.amount', 25000)
            ->set('damage_notes', 'Kerusakan ringan pada ritsleting jaket')
            ->call('submit')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CLOSED, $booking->status);
        $this->assertNotNull($booking->closed_at);
        $this->assertEquals(25000, $booking->fine_amount);
        $this->assertEquals(4, $booking->lock_version);

        // Verify settlement database record
        $this->assertDatabaseHas('settlements', [
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'late_days' => 0,
            'late_fine' => 0,
            'damage_deduction' => 25000,
            'total_deduction' => 25000,
            'deposit_remaining' => 75000,
            'damage_notes' => 'Kerusakan ringan pada ritsleting jaket',
        ]);

        $settlement = Settlement::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals('2026-10-20', $settlement->actual_return_date->toDateString());

        // Verify damage item record
        $this->assertDatabaseHas('damage_items', [
            'settlement_id' => $settlement->id,
            'description' => 'Ritsleting rusak',
            'repair_cost' => 25000,
        ]);

        // Verify identity document retention schedule (BR-05)
        $identityDoc->refresh();
        $this->assertNotNull($identityDoc->delete_scheduled_at);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendor->id,
            'event' => 'booking_closed',
            'entity_type' => 'booking',
            'entity_id' => (string) $booking->id,
        ]);
    }

    public function test_settlement_shortfall_scenario_dt04(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'fine_per_day' => 50000,
            'deposit_amount' => 100000,
        ]);
        $renter = Renter::factory()->create();

        // DT-04: end_date 2026-10-20, returned 2026-10-23 (3 days late = 150.000 late fine)
        // Damage = 50.000 -> Total deduction = 200.000 -> Shortfall = 100.000
        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_RETURNED,
            'deposit_paid' => 100000,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'total_days' => 3,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(BookingSettlementForm::class, ['booking' => $booking])
            ->set('actual_return_date', '2026-10-23')
            ->call('addDamageItem')
            ->set('damage_items.0.description', 'Robek bagian samping')
            ->set('damage_items.0.amount', 50000)
            ->call('submit')
            ->assertHasNoErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CLOSED, $booking->status);

        $settlement = Settlement::where('booking_id', $booking->id)->firstOrFail();
        $this->assertEquals(3, $settlement->late_days);
        $this->assertEquals(150000, $settlement->late_fine);
        $this->assertEquals(50000, $settlement->damage_deduction);
        $this->assertEquals(200000, $settlement->total_deduction);
        $this->assertEquals(-100000, $settlement->deposit_remaining);
        $this->assertEquals(100000, $settlement->shortfall_amount);
        $this->assertEquals(0, $settlement->refund_amount);
    }

    public function test_filament_view_booking_settle_action_completes_settlement(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'fine_per_day' => 50000,
            'deposit_amount' => 100000,
        ]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_RETURNED,
            'deposit_paid' => 100000,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-20',
            'total_days' => 3,
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(ViewBooking::class, ['record' => $booking->ulid])
            ->assertActionVisible('settle')
            ->callAction('settle', [
                'actual_return_date' => '2026-10-21', // 1 day late = 50.000
                'damage_items' => [
                    ['description' => 'Aksesoris hilang', 'amount' => 20000],
                ],
                'damage_notes' => 'Aksesoris kepala hilang saat pemakaian',
            ])
            ->assertHasNoActionErrors();

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CLOSED, $booking->status);

        $this->assertDatabaseHas('settlements', [
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'late_days' => 1,
            'late_fine' => 50000,
            'damage_deduction' => 20000,
            'total_deduction' => 70000,
            'deposit_remaining' => 30000,
            'damage_notes' => 'Aksesoris kepala hilang saat pemakaian',
        ]);
    }

    public function test_vendor_isolation_cannot_mount_settlement_form_for_other_vendor(): void
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
            'status' => Booking::STATUS_RETURNED,
        ]);

        // Vendor A attempts to mount settlement for Vendor B's booking
        $this->actingAs($userA, 'vendor');
        session(['vendor_id' => $vendorA->id]);

        Livewire::test(BookingSettlementForm::class, ['booking' => $bookingB])
            ->assertStatus(404);

        // Check IDOR attempt is logged in audit_logs
        $this->assertDatabaseHas('audit_logs', [
            'vendor_id' => $vendorA->id,
            'event' => 'idor_attempt_blocked',
            'entity_id' => (string) $bookingB->id,
        ]);
    }

    public function test_cannot_mount_settlement_form_for_booking_not_in_returned_status(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_ACTIVE, // Not returned yet
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(BookingSettlementForm::class, ['booking' => $booking])
            ->assertStatus(400);
    }

    public function test_booking_status_service_throws_exception_if_settling_non_returned_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED, // not returned
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Booking dengan status 'approved' tidak dapat diselesaikan. Status harus 'returned'.");

        app(BookingStatusService::class)->closeWithSettlement($booking, [
            'actual_return_date' => '2026-10-20',
            'late_days' => 0,
            'late_fine' => 0,
            'damage_deduction' => 0,
            'total_deduction' => 0,
            'deposit_remaining' => 100000,
        ]);
    }
}
