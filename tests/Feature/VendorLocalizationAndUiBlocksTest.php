<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Enums\VendorPlan;
use App\Filament\Support\Components\RupiahInput;
use App\Filament\Support\Tables\DateColumn;
use App\Filament\Support\Tables\MoneyColumn;
use App\Filament\Support\Tables\StatusColumn;
use App\Filament\Vendor\Widgets\TodayAgendaWidget;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Renter;
use App\Models\Vendor;
use App\Models\VendorUser;
use Carbon\Carbon;
use Filament\Support\Enums\Alignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorLocalizationAndUiBlocksTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_is_configured_to_indonesian(): void
    {
        $this->assertEquals('id', config('app.locale'));
        $this->assertEquals('id', app()->getLocale());

        $date = Carbon::parse('2026-10-22 14:00:00');
        $this->assertEquals('22 Okt 2026', $date->translatedFormat('d M Y'));
    }

    public function test_enums_implement_filament_contracts_with_indonesian_labels(): void
    {
        // BookingStatus
        $this->assertEquals('Menunggu Konfirmasi', BookingStatus::PENDING->getLabel());
        $this->assertEquals('Sedang Disewa', BookingStatus::ACTIVE->getLabel());
        $this->assertEquals('Dikembalikan', BookingStatus::RETURNED->getLabel());
        $this->assertNotEmpty(BookingStatus::PENDING->getColor());
        $this->assertNotEmpty(BookingStatus::PENDING->getIcon());

        // ItemStatus
        $this->assertEquals('Tersedia', ItemStatus::AVAILABLE->getLabel());
        $this->assertEquals('Sedang Disewa', ItemStatus::RENTED->getLabel());
        $this->assertNotEmpty(ItemStatus::AVAILABLE->getColor());
        $this->assertNotEmpty(ItemStatus::AVAILABLE->getIcon());

        // ItemCondition
        $this->assertEquals('Sangat Baik (Seperti Baru)', ItemCondition::EXCELLENT->getLabel());
        $this->assertNotEmpty(ItemCondition::EXCELLENT->getColor());

        // VendorPlan
        $this->assertEquals('Paket Free', VendorPlan::FREE->getLabel());
        $this->assertEquals('Paket Pro', VendorPlan::PRO->getLabel());
    }

    public function test_reusable_ui_blocks_instantiation_and_defaults(): void
    {
        // RupiahInput
        $input = RupiahInput::make('deposit');
        $this->assertEquals('Rp', $input->getPrefixLabel());
        $this->assertEquals('decimal', $input->getInputMode());

        // MoneyColumn
        $moneyCol = MoneyColumn::make('price');
        $this->assertEquals(Alignment::End, $moneyCol->getAlignment());

        // DateColumn
        $dateCol = DateColumn::make('created_at');
        $this->assertNotNull($dateCol);

        // StatusColumn
        $statusCol = StatusColumn::make('status')->enumClass(BookingStatus::class);
        $this->assertTrue($statusCol->isBadge());
    }

    public function test_today_agenda_widget_renders_tasks_and_executes_actions(): void
    {
        $vendor = Vendor::factory()->create();
        $user = VendorUser::factory()->create(['vendor_id' => $vendor->id]);
        $item = Item::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Kamera Sony A7IV']);
        $renter = Renter::factory()->create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
        ]);

        $today = now('Asia/Jakarta')->toDateString();

        // 1 Booking pickup today
        $pickupBooking = Booking::factory()->create([
            'vendor_id' => $vendor->id,
            'item_id' => $item->id,
            'renter_id' => $renter->id,
            'status' => Booking::STATUS_APPROVED,
            'start_date' => $today,
            'end_date' => now('Asia/Jakarta')->addDays(3)->toDateString(),
            'reference_code' => 'JS-PCK-001',
        ]);

        $this->actingAs($user, 'vendor');
        session(['vendor_id' => $vendor->id]);

        Livewire::test(TodayAgendaWidget::class)
            ->assertSuccessful()
            ->assertSee('Agenda Tugas Hari Ini')
            ->assertSee('Pengambilan (1)')
            ->assertSee('JS-PCK-001')
            ->assertSee('Kamera Sony A7IV')
            ->assertSee('Budi Santoso')
            ->assertSee('Tandai Diambil')
            ->call('markAsActive', $pickupBooking->id);

        $this->assertEquals(Booking::STATUS_ACTIVE, $pickupBooking->fresh()->status);
    }
}
