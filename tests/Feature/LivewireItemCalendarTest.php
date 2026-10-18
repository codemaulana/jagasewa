<?php

namespace Tests\Feature;

use App\Livewire\ItemAvailabilityCalendar;
use App\Models\Item;
use App\Models\Vendor;
use App\Services\BookingCalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireItemCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    protected function tearDown(): void
    {
        BookingCalendarService::setBookingsResolver(null);
        parent::tearDown();
    }

    public function test_calendar_component_can_render_for_active_item(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->assertStatus(200)
            ->assertSee('Kalender Ketersediaan')
            ->assertSee('Sn')
            ->assertSee('Mg')
            ->assertSee('Tersedia')
            ->assertSee('Terpesan')
            ->assertSee('Buffer Jeda');
    }

    public function test_month_navigation_forward_and_backward(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $initialMonth = Carbon::now('Asia/Jakarta')->format('Y-m');
        $expectedNextMonth = Carbon::now('Asia/Jakarta')->addMonth()->format('Y-m');

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->assertSet('currentMonth', $initialMonth)
            ->call('goToNextMonth')
            ->assertSet('currentMonth', $expectedNextMonth)
            ->call('goToPreviousMonth')
            ->assertSet('currentMonth', $initialMonth);
    }

    public function test_cannot_navigate_to_past_months(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        $initialMonth = Carbon::now('Asia/Jakarta')->format('Y-m');

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->assertSet('currentMonth', $initialMonth)
            ->call('goToPreviousMonth')
            ->assertSet('currentMonth', $initialMonth);
    }

    public function test_renders_booked_and_buffer_statuses_with_visual_indicators(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 2,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        // Mock booking on 10–12 Oct 2026 with buffer 2, 2
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

        $test = Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item]);
        $test->set('currentMonth', '2026-10');

        $test->assertSee('Oktober 2026')
            ->assertSeeHtml('line-through')
            ->assertSeeHtml('title="Terpesan"')
            ->assertSeeHtml('title="Masa Jeda (Buffer)"');
    }

    public function test_cannot_select_booked_date(): void
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
                    'status' => 'approved',
                ],
            ];
        });

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->set('currentMonth', '2026-10')
            ->call('selectDate', '2026-10-10')
            ->assertSet('selectedStartDate', null)
            ->assertSee('Tanggal tersebut sudah terpesan');
    }

    public function test_cannot_select_buffer_date(): void
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
                    'status' => 'approved',
                ],
            ];
        });

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->set('currentMonth', '2026-10')
            ->call('selectDate', '2026-10-08')
            ->assertSet('selectedStartDate', null)
            ->assertSee('masa jeda (buffer)');
    }

    public function test_can_select_single_available_day(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'price_per_day' => 150000,
            'is_active' => true,
        ]);

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->set('currentMonth', '2026-10')
            ->call('selectDate', '2026-10-15')
            ->assertSet('selectedStartDate', '2026-10-15')
            ->assertSet('selectedEndDate', null)
            ->assertSee('Mulai Sewa')
            ->assertSee('15 Okt 2026')
            ->assertSee('1 Hari')
            ->assertSee('Rp 150.000');
    }

    public function test_can_select_valid_date_range(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'price_per_day' => 150000,
            'is_active' => true,
        ]);

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->set('currentMonth', '2026-10')
            ->call('selectDate', '2026-10-15')
            ->call('selectDate', '2026-10-17')
            ->assertSet('selectedStartDate', '2026-10-15')
            ->assertSet('selectedEndDate', '2026-10-17')
            ->assertDispatched('dates-selected')
            ->assertSee('3 Hari')
            ->assertSee('Rp 450.000');
    }

    public function test_cannot_select_range_crossing_booked_or_buffer_days(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 2,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        // Booking on 10-12 Oct (buffer 8-9 and 13-14)
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

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->set('currentMonth', '2026-10')
            ->call('selectDate', '2026-10-06')
            ->call('selectDate', '2026-10-16')
            ->assertSet('selectedStartDate', '2026-10-06')
            ->assertSet('selectedEndDate', null)
            ->assertSee('Rentang sewa tidak boleh melewati');
    }

    public function test_can_reset_date_selection(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);

        Livewire::test(ItemAvailabilityCalendar::class, ['item' => $item])
            ->set('currentMonth', '2026-10')
            ->call('selectDate', '2026-10-15')
            ->call('selectDate', '2026-10-17')
            ->assertSet('selectedStartDate', '2026-10-15')
            ->call('resetSelection')
            ->assertSet('selectedStartDate', null)
            ->assertSet('selectedEndDate', null)
            ->assertDispatched('dates-cleared');
    }

    public function test_public_item_page_contains_livewire_calendar_component(): void
    {
        $vendor = Vendor::factory()->create([
            'slug' => 'rental-cosplay-bandung',
            'is_active' => true,
        ]);

        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'slug' => 'kostum-frieren',
            'name' => 'Kostum Frieren',
            'is_active' => true,
        ]);

        $response = $this->get('/b/rental-cosplay-bandung/kostum-frieren');

        $response->assertStatus(200);
        $response->assertSee('Kalender Ketersediaan');
        $response->assertSee('Tersedia');
        $response->assertSee('Terpesan');
        $response->assertSee('Buffer Jeda');
    }
}
