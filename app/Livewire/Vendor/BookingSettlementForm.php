<?php

namespace App\Livewire\Vendor;

use App\DTOs\FineCalculationResult;
use App\Models\Booking;
use App\Models\Settlement;
use App\Services\AuditService;
use App\Services\BookingStatusService;
use App\Services\FineCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BookingSettlementForm extends Component
{
    public Booking $booking;

    public string $actual_return_date = '';

    /**
     * @var array<int, array{description: string, amount: int|float|string}>
     */
    public array $damage_items = [];

    public string $damage_notes = '';

    public function mount(Booking $booking): void
    {
        // Enforce Vendor Isolation (Rule 1 & Rule 15: Cross-vendor resource returns 404, never 403)
        $currentVendorId = auth('vendor')->user()?->vendor_id ?? auth()->user()?->vendor_id ?? session('vendor_id');
        if (! $currentVendorId || (int) $booking->vendor_id !== (int) $currentVendorId) {
            AuditService::log(
                'idor_attempt_blocked',
                Booking::class,
                (string) $booking->id,
                ['action' => 'settlement_mount', 'target_vendor_id' => $booking->vendor_id],
                $currentVendorId
            );
            abort(404);
        }

        if ($booking->status !== Booking::STATUS_RETURNED) {
            abort(400, 'Penyelesaian sewa hanya dapat diproses untuk booking dengan status dikembalikan (returned).');
        }

        $this->booking = $booking->loadMissing('item', 'renter');
        $this->actual_return_date = Carbon::now(FineCalculatorService::TIMEZONE)->toDateString();
    }

    /**
     * Add a new damage item row.
     */
    public function addDamageItem(): void
    {
        $this->damage_items[] = [
            'description' => '',
            'amount' => 0,
        ];
    }

    /**
     * Remove a damage item row by index.
     */
    public function removeDamageItem(int $index): void
    {
        if (isset($this->damage_items[$index])) {
            unset($this->damage_items[$index]);
            $this->damage_items = array_values($this->damage_items);
        }
    }

    /**
     * Computed property: Real-time calculation using FineCalculatorService (BR-03).
     */
    #[Computed]
    public function calculation(): FineCalculationResult
    {
        $date = ! empty($this->actual_return_date)
            ? $this->actual_return_date
            : Carbon::now(FineCalculatorService::TIMEZONE)->toDateString();

        return app(FineCalculatorService::class)->calculateForBooking(
            booking: $this->booking,
            actualReturnDate: $date,
            damage: $this->damage_items
        );
    }

    /**
     * Submit settlement and close booking.
     */
    public function submit(BookingStatusService $statusService, FineCalculatorService $calculator): mixed
    {
        $this->validate([
            'actual_return_date' => ['required', 'date'],
            'damage_items.*.description' => ['nullable', 'string', 'max:255'],
            'damage_items.*.amount' => ['nullable', 'numeric', 'min:0'],
            'damage_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $calcResult = $calculator->calculateForBooking(
            booking: $this->booking,
            actualReturnDate: $this->actual_return_date,
            damage: $this->damage_items
        );

        $settlement = $statusService->closeWithSettlement(
            booking: $this->booking,
            settlementData: $calcResult->toSettlementAttributes([
                'damage_notes' => ! empty($this->damage_notes) ? $this->damage_notes : null,
            ]),
            damageItems: $this->damage_items
        );

        session()->flash('success', 'Penyelesaian sewa berhasil disimpan. Status pesanan kini selesai (closed).');
        $this->dispatch('settlement-completed', settlementId: $settlement->id);

        if (\Route::has('filament.vendor.resources.bookings.view')) {
            return redirect()->route('filament.vendor.resources.bookings.view', ['record' => $this->booking->ulid]);
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.vendor.booking-settlement-form');
    }
}
