<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\DamageItem;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Settlement;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SettlementModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_settlement_with_factory(): void
    {
        $settlement = Settlement::factory()->create();

        $this->assertNotNull($settlement->id);
        $this->assertNotNull($settlement->ulid);
        $this->assertNotNull($settlement->booking_id);
        $this->assertNotNull($settlement->vendor_id);
        $this->assertIsInt($settlement->late_days);
        $this->assertIsInt($settlement->late_fine);
        $this->assertIsInt($settlement->damage_deduction);
        $this->assertIsInt($settlement->total_deduction);
        $this->assertIsInt($settlement->deposit_remaining);
        $this->assertDatabaseHas('settlements', [
            'id' => $settlement->id,
            'ulid' => $settlement->ulid,
        ]);
    }

    public function test_can_create_damage_item_with_factory(): void
    {
        $damageItem = DamageItem::factory()->create([
            'description' => 'Resleting kostum rusak',
            'repair_cost' => 50000,
        ]);

        $this->assertNotNull($damageItem->id);
        $this->assertNotNull($damageItem->ulid);
        $this->assertNotNull($damageItem->settlement_id);
        $this->assertEquals(50000, $damageItem->repair_cost);
        $this->assertDatabaseHas('damage_items', [
            'id' => $damageItem->id,
            'description' => 'Resleting kostum rusak',
            'repair_cost' => 50000,
        ]);
    }

    public function test_booking_and_settlement_relationship(): void
    {
        $vendor = Vendor::factory()->create();
        $item = Item::factory()->create(['vendor_id' => $vendor->id]);
        $renter = Renter::factory()->create();

        $booking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_RETURNED,
        ]);

        $settlement = Settlement::factory()->create([
            'vendor_id' => $vendor->id,
            'booking_id' => $booking->id,
        ]);

        $this->assertInstanceOf(Settlement::class, $booking->settlement);
        $this->assertEquals($settlement->id, $booking->settlement->id);
        $this->assertInstanceOf(Booking::class, $settlement->booking);
        $this->assertEquals($booking->id, $settlement->booking->id);
    }

    public function test_settlement_and_damage_items_relationship(): void
    {
        $settlement = Settlement::factory()->create();

        $damage1 = DamageItem::factory()->create([
            'settlement_id' => $settlement->id,
            'description' => 'Kancing copot',
            'repair_cost' => 15000,
        ]);

        $damage2 = DamageItem::factory()->create([
            'settlement_id' => $settlement->id,
            'description' => 'Noda makeup',
            'repair_cost' => 35000,
        ]);

        $this->assertCount(2, $settlement->damageItems);
        $this->assertTrue($settlement->damageItems->contains($damage1));
        $this->assertTrue($settlement->damageItems->contains($damage2));
        $this->assertEquals($settlement->id, $damage1->settlement->id);
    }

    public function test_deleting_booking_cascades_to_settlement_and_damage_items(): void
    {
        $settlement = Settlement::factory()->create();
        $damageItem = DamageItem::factory()->create([
            'settlement_id' => $settlement->id,
        ]);

        $booking = $settlement->booking;

        $booking->delete();

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('settlements', ['id' => $settlement->id]);
        $this->assertDatabaseMissing('damage_items', ['id' => $damageItem->id]);
    }

    public function test_cannot_create_duplicate_settlement_for_same_booking(): void
    {
        $settlement = Settlement::factory()->create();

        $this->expectException(QueryException::class);

        // Attempt to create second settlement for same booking must fail unique constraint
        Settlement::create([
            'booking_id' => $settlement->booking_id,
            'vendor_id' => $settlement->vendor_id,
            'actual_return_date' => Carbon::now('Asia/Jakarta')->format('Y-m-d'),
            'late_days' => 0,
            'late_fine' => 0,
            'damage_deduction' => 0,
            'total_deduction' => 0,
            'deposit_remaining' => 50000,
        ]);
    }

    public function test_refund_and_shortfall_accessors(): void
    {
        // Scenario A: Refund (deposit remaining positive)
        $settlementRefund = Settlement::factory()->create([
            'deposit_remaining' => 75000,
        ]);

        $this->assertEquals(75000, $settlementRefund->refund_amount);
        $this->assertEquals(0, $settlementRefund->shortfall_amount);

        // Scenario B: Shortfall (deposit remaining negative)
        $settlementShortfall = Settlement::factory()->create([
            'deposit_remaining' => -125000,
        ]);

        $this->assertEquals(0, $settlementShortfall->refund_amount);
        $this->assertEquals(125000, $settlementShortfall->shortfall_amount);

        // Scenario C: Exact match (deposit remaining zero)
        $settlementZero = Settlement::factory()->create([
            'deposit_remaining' => 0,
        ]);

        $this->assertEquals(0, $settlementZero->refund_amount);
        $this->assertEquals(0, $settlementZero->shortfall_amount);
    }

    public function test_migrations_can_be_rolled_back_cleanly(): void
    {
        $this->assertTrue(Schema::hasTable('settlements'));
        $this->assertTrue(Schema::hasTable('damage_items'));

        $this->artisan('migrate:rollback', [
            '--path' => 'database/migrations/2026_10_19_000009_create_settlements_and_damage_items_tables.php',
        ])->assertSuccessful();

        $this->assertFalse(Schema::hasTable('damage_items'));
        $this->assertFalse(Schema::hasTable('settlements'));

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_10_19_000009_create_settlements_and_damage_items_tables.php',
        ])->assertSuccessful();

        $this->assertTrue(Schema::hasTable('settlements'));
        $this->assertTrue(Schema::hasTable('damage_items'));
    }
}
