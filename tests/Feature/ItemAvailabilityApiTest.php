<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Vendor;
use App\Services\BookingCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemAvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        BookingCalendarService::setBookingsResolver(null);
        parent::tearDown();
    }

    public function test_can_get_month_availability_for_active_item(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 2,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(200);
        $response->assertJsonIsArray();
        $response->assertJsonCount(31);

        $response->assertJsonFragment([
            'date' => '2026-10-01',
            'day' => 1,
            'status' => 'available',
        ]);
        $response->assertJsonFragment([
            'date' => '2026-10-31',
            'day' => 31,
            'status' => 'available',
        ]);
    }

    public function test_returns_correct_statuses_for_booked_and_buffer_days(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 2,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        // Mock an active booking on 10–12 Oct 2026 with buffer 2, 2
        BookingCalendarService::setBookingsResolver(function () {
            return [
                [
                    'start_date' => '2026-10-10',
                    'end_date' => '2026-10-12',
                    'buffer_before' => 2,
                    'buffer_after' => 2,
                    'status' => 'approved',
                ],
            ];
        });

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(200);
        $data = $response->json();

        $statusByDate = collect($data)->pluck('status', 'date')->all();

        // 08-09 Oct: buffer before
        $this->assertEquals('buffer', $statusByDate['2026-10-08']);
        $this->assertEquals('buffer', $statusByDate['2026-10-09']);

        // 10-12 Oct: core rental
        $this->assertEquals('booked', $statusByDate['2026-10-10']);
        $this->assertEquals('booked', $statusByDate['2026-10-11']);
        $this->assertEquals('booked', $statusByDate['2026-10-12']);

        // 13-14 Oct: buffer after
        $this->assertEquals('buffer', $statusByDate['2026-10-13']);
        $this->assertEquals('buffer', $statusByDate['2026-10-14']);

        // Outside buffer: available
        $this->assertEquals('available', $statusByDate['2026-10-07']);
        $this->assertEquals('available', $statusByDate['2026-10-15']);
    }

    public function test_booked_status_takes_precedence_over_buffer_status(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 1,
            'buffer_after' => 1,
            'is_active' => true,
        ]);

        // Booking 1: 10-11 Oct (buffer after on 12 Oct)
        // Booking 2: 12-13 Oct (core on 12 Oct)
        BookingCalendarService::setBookingsResolver(function () {
            return [
                [
                    'start_date' => '2026-10-10',
                    'end_date' => '2026-10-11',
                    'buffer_before' => 1,
                    'buffer_after' => 1,
                    'status' => 'approved',
                ],
                [
                    'start_date' => '2026-10-12',
                    'end_date' => '2026-10-13',
                    'buffer_before' => 1,
                    'buffer_after' => 1,
                    'status' => 'approved',
                ],
            ];
        });

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(200);
        $data = $response->json();
        $statusByDate = collect($data)->pluck('status', 'date')->all();

        // 12 Oct is core for Booking 2, even though it is buffer after for Booking 1
        $this->assertEquals('booked', $statusByDate['2026-10-12']);
    }

    public function test_handles_spanning_months_correctly(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 2,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        // Booking spanning September into October: 28 Sep - 02 Oct (buffer 2, 2)
        BookingCalendarService::setBookingsResolver(function () {
            return [
                [
                    'start_date' => '2026-09-28',
                    'end_date' => '2026-10-02',
                    'buffer_before' => 2,
                    'buffer_after' => 2,
                    'status' => 'active',
                ],
            ];
        });

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(200);
        $data = $response->json();
        $statusByDate = collect($data)->pluck('status', 'date')->all();

        $this->assertEquals('booked', $statusByDate['2026-10-01']);
        $this->assertEquals('booked', $statusByDate['2026-10-02']);
        $this->assertEquals('buffer', $statusByDate['2026-10-03']);
        $this->assertEquals('buffer', $statusByDate['2026-10-04']);
        $this->assertEquals('available', $statusByDate['2026-10-05']);
    }

    public function test_cancelled_booking_does_not_affect_availability(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 2,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        BookingCalendarService::setBookingsResolver(function () {
            return [
                [
                    'start_date' => '2026-10-10',
                    'end_date' => '2026-10-12',
                    'buffer_before' => 2,
                    'buffer_after' => 2,
                    'status' => 'cancelled_vendor',
                ],
            ];
        });

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(200);
        $data = $response->json();
        $statusByDate = collect($data)->pluck('status', 'date')->all();

        $this->assertEquals('available', $statusByDate['2026-10-10']);
        $this->assertEquals('available', $statusByDate['2026-10-11']);
        $this->assertEquals('available', $statusByDate['2026-10-12']);
    }

    public function test_querying_by_numeric_id_returns_404_to_prevent_enumeration(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        // Via internal numeric ID -> MUST return 404 to prevent enumeration (SEC-10)
        $responseId = $this->getJson("/api/items/{$item->id}/availability?month=2026-10");
        $responseId->assertStatus(404);

        // Via public ULID -> 200 OK
        $responseUlid = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");
        $responseUlid->assertStatus(200);
    }

    public function test_defaults_to_current_month_when_month_param_omitted(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/items/{$item->ulid}/availability");

        $response->assertStatus(200);
        $response->assertJsonIsArray();

        $expectedDays = Carbon::now('Asia/Jakarta')->daysInMonth;
        $response->assertJsonCount($expectedDays);
    }

    public function test_invalid_month_format_returns_422(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        // String non-date
        $res1 = $this->getJson("/api/items/{$item->ulid}/availability?month=bukan-bulan");
        $res1->assertStatus(422);
        $res1->assertJsonValidationErrors(['month']);

        // Bulan ke-13
        $res2 = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-13");
        $res2->assertStatus(422);

        // Format tanggal lengkap bukan YYYY-MM
        $res3 = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10-15");
        $res3->assertStatus(422);
    }

    public function test_inactive_item_returns_404(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->inactive()->create([
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(404);
    }

    public function test_item_with_inactive_vendor_returns_404(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => false]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");

        $response->assertStatus(404);
    }

    public function test_non_existent_item_returns_404(): void
    {
        $response = $this->getJson('/api/items/01HXXNONEXISTENTITEM0000000/availability?month=2026-10');

        $response->assertStatus(404);
    }

    public function test_response_time_under_200ms(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $start = microtime(true);
        $response = $this->getJson("/api/items/{$item->ulid}/availability?month=2026-10");
        $durationMs = (microtime(true) - $start) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $durationMs, "Response time was {$durationMs}ms, expected under 200ms.");
    }
}
