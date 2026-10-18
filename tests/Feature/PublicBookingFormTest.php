<?php

namespace Tests\Feature;

use App\Livewire\Public\BookingForm;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PublicBookingFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
    }

    public function test_booking_form_renders_properly_on_public_item_page(): void
    {
        $vendor = Vendor::factory()->create(['slug' => 'studio-form', 'is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'slug' => 'kostum-form',
            'is_active' => true,
        ]);

        $response = $this->get('/b/studio-form/kostum-form');

        $response->assertStatus(200);
        $response->assertSeeLivewire(BookingForm::class);
        $response->assertSee('Formulir Pengajuan Sewa');
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Nomor WhatsApp');
    }

    public function test_can_submit_valid_booking(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'price_per_day' => 150000,
            'deposit_amount' => 100000,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'is_active' => true,
        ]);

        $startDate = Carbon::now('Asia/Jakarta')->addDays(3)->toDateString();
        $endDate = Carbon::now('Asia/Jakarta')->addDays(5)->toDateString(); // 3 days

        Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'Andi Pratama')
            ->set('phone', '081234567890')
            ->set('address', 'Jl. Sukajadi No. 12, Bandung')
            ->set('startDate', $startDate)
            ->set('endDate', $endDate)
            ->set('renterNotes', 'Mohon dikirim pagi hari.')
            ->set('agreePrivacy', true)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'total_days' => 3,
            'total_price' => 450000,
            'deposit_paid' => 100000,
            'status' => 'pending',
            'renter_notes' => 'Mohon dikirim pagi hari.',
        ]);

        $renter = Renter::where('phone_hash', Renter::hashValue('6281234567890'))->first();
        $this->assertNotNull($renter);
        $this->assertEquals('Andi Pratama', $renter->name);
        $this->assertEquals('081234567890', $renter->phone);
    }

    public function test_submitting_without_privacy_consent_fails_validation(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);

        Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'Budi Santoso')
            ->set('phone', '081234567890')
            ->set('address', 'Alamat lengkap')
            ->set('startDate', Carbon::now('Asia/Jakarta')->addDays(2)->toDateString())
            ->set('endDate', Carbon::now('Asia/Jakarta')->addDays(3)->toDateString())
            ->set('agreePrivacy', false)
            ->call('submit')
            ->assertHasErrors(['agreePrivacy']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_submitting_invalid_phone_fails_validation(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'is_active' => true]);

        Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'Budi Santoso')
            ->set('phone', '12345') // Invalid format
            ->set('address', 'Alamat lengkap')
            ->set('startDate', Carbon::now('Asia/Jakarta')->addDays(2)->toDateString())
            ->set('endDate', Carbon::now('Asia/Jakarta')->addDays(3)->toDateString())
            ->set('agreePrivacy', true)
            ->call('submit')
            ->assertHasErrors(['phone']);

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_submitting_clashing_dates_fails_with_error_message(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'is_active' => true,
        ]);

        $occupiedStart = Carbon::now('Asia/Jakarta')->addDays(5)->toDateString();
        $occupiedEnd = Carbon::now('Asia/Jakarta')->addDays(7)->toDateString();

        // Existing approved booking
        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'start_date' => $occupiedStart,
            'end_date' => $occupiedEnd,
            'status' => Booking::STATUS_APPROVED,
        ]);

        // Trying to book overlapping dates
        Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'Penyewa Telat')
            ->set('phone', '081299998888')
            ->set('address', 'Alamat Bandung')
            ->set('startDate', Carbon::now('Asia/Jakarta')->addDays(6)->toDateString())
            ->set('endDate', Carbon::now('Asia/Jakarta')->addDays(8)->toDateString())
            ->set('agreePrivacy', true)
            ->call('submit')
            ->assertSee('bertabrakan dengan pesanan lain');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_submitting_during_buffer_period_fails(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'buffer_before' => 1,
            'buffer_after' => 2,
            'is_active' => true,
        ]);

        $existingStart = Carbon::now('Asia/Jakarta')->addDays(5);
        $existingEnd = Carbon::now('Asia/Jakarta')->addDays(7);

        Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'start_date' => $existingStart->toDateString(),
            'end_date' => $existingEnd->toDateString(),
            'status' => Booking::STATUS_ACTIVE,
        ]);

        // Buffer after is 2 days (days 8 and 9 are buffer)
        // Trying to book starting on day 8 should fail
        Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'Penyewa Buffer')
            ->set('phone', '081277776666')
            ->set('address', 'Alamat Bandung')
            ->set('startDate', Carbon::now('Asia/Jakarta')->addDays(8)->toDateString())
            ->set('endDate', Carbon::now('Asia/Jakarta')->addDays(10)->toDateString())
            ->set('agreePrivacy', true)
            ->call('submit')
            ->assertSee('bertabrakan dengan pesanan lain');

        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_double_submit_prevented_by_idempotency_key(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'price_per_day' => 100000,
            'is_active' => true,
        ]);

        $start = Carbon::now('Asia/Jakarta')->addDays(10)->toDateString();
        $end = Carbon::now('Asia/Jakarta')->addDays(12)->toDateString();
        $key = 'uuid-fixed-idempotency-key-123';

        $component = Livewire::test(BookingForm::class, ['item' => $item])
            ->set('name', 'User Idempoten')
            ->set('phone', '081255554444')
            ->set('address', 'Alamat Jakarta')
            ->set('startDate', $start)
            ->set('endDate', $end)
            ->set('idempotencyKey', $key)
            ->set('agreePrivacy', true);

        // First submit
        $component->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseCount('bookings', 1);

        // Second submit with exact same idempotencyKey
        $component->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect();

        // Database still only has 1 booking
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_component_reacts_to_dates_selected_event(): void
    {
        $vendor = Vendor::factory()->create(['is_active' => true]);
        $item = Item::factory()->create([
            'vendor_id' => $vendor->id,
            'price_per_day' => 200000,
            'is_active' => true,
        ]);

        $startDate = Carbon::now('Asia/Jakarta')->addDays(2)->toDateString();
        $endDate = Carbon::now('Asia/Jakarta')->addDays(5)->toDateString(); // 4 days

        Livewire::test(BookingForm::class, ['item' => $item])
            ->dispatch('dates-selected', [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalDays' => 4,
                'totalPrice' => 800000,
            ])
            ->assertSet('startDate', $startDate)
            ->assertSet('endDate', $endDate)
            ->assertSet('totalDays', 4)
            ->assertSet('totalPrice', 800000);
    }
}
