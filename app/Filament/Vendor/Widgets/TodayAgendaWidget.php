<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Booking;
use App\Services\VendorMetricsService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class TodayAgendaWidget extends Widget
{
    protected string $view = 'filament.vendor.widgets.today-agenda-widget';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public string $activeTab = 'pengambilan';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function markAsActive(int $bookingId): void
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor) {
            return;
        }

        $booking = Booking::where('vendor_id', $vendor->id)->find($bookingId);
        if ($booking && in_array($booking->status, [Booking::STATUS_APPROVED, Booking::STATUS_PENDING])) {
            $booking->update([
                'status' => Booking::STATUS_ACTIVE,
            ]);

            Notification::make()
                ->title('Unit Ditandai Telah Diambil')
                ->body("Pesanan {$booking->reference_code} sekarang berstatus Sedang Disewa.")
                ->success()
                ->send();
        }
    }

    public function markAsReturned(int $bookingId): void
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor) {
            return;
        }

        $booking = Booking::where('vendor_id', $vendor->id)->find($bookingId);
        if ($booking && $booking->status === Booking::STATUS_ACTIVE) {
            $booking->update([
                'status' => Booking::STATUS_RETURNED,
            ]);

            Notification::make()
                ->title('Unit Ditandai Telah Dikembalikan')
                ->body("Pesanan {$booking->reference_code} sekarang berstatus Dikembalikan.")
                ->success()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (! $vendor) {
            return [
                'activeTab' => $this->activeTab,
                'items' => collect(),
                'counts' => ['pengambilan' => 0, 'pengembalian' => 0, 'terlambat' => 0],
            ];
        }

        $today = now(VendorMetricsService::TIMEZONE)->toDateString();

        // 1. Pengambilan: start_date = today, status pending/approved
        $pickups = Booking::where('vendor_id', $vendor->id)
            ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_PENDING])
            ->whereDate('start_date', $today)
            ->with(['item', 'renter'])
            ->orderBy('id', 'desc')
            ->get();

        // 2. Pengembalian: end_date = today, status active
        $returns = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', $today)
            ->with(['item', 'renter'])
            ->orderBy('id', 'desc')
            ->get();

        // 3. Terlambat: end_date < today, status active
        $overdue = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', '<', $today)
            ->with(['item', 'renter'])
            ->orderBy('end_date', 'asc')
            ->get();

        $counts = [
            'pengambilan' => $pickups->count(),
            'pengembalian' => $returns->count(),
            'terlambat' => $overdue->count(),
        ];

        $currentItems = match ($this->activeTab) {
            'pengembalian' => $returns,
            'terlambat' => $overdue,
            default => $pickups,
        };

        return [
            'activeTab' => $this->activeTab,
            'items' => $currentItems,
            'counts' => $counts,
        ];
    }
}
