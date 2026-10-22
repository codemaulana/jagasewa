<?php

namespace App\Filament\Vendor\Pages;

use App\Filament\Vendor\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Item;
use App\Models\Vendor;
use App\Services\Billing\EntitlementService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class VendorBookingCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Kalender Sewa';

    protected static ?string $title = 'Kalender Jadwal Sewa';

    protected static ?string $slug = 'calendar';

    protected static ?int $navigationSort = 15;

    protected string $view = 'filament.vendor.pages.vendor-booking-calendar';

    public string $selectedMonth = '';

    public ?string $selectedItemId = 'all';

    public ?string $selectedDate = null;

    public string $viewMode = 'month'; // 'month' or 'week'

    public function mount(): void
    {
        $now = now('Asia/Jakarta');
        $this->selectedMonth = $now->format('Y-m');
        $this->selectedDate = $now->toDateString();
    }

    public function getVendor(): ?Vendor
    {
        return auth('vendor')->user()?->vendor;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = in_array($mode, ['month', 'week'], true) ? $mode : 'month';
    }

    public function previousMonth(): void
    {
        if ($this->viewMode === 'week') {
            $this->selectedDate = Carbon::parse($this->selectedDate ?: now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta')
                ->subWeek()
                ->toDateString();
            $this->selectedMonth = Carbon::parse($this->selectedDate, 'Asia/Jakarta')->format('Y-m');
        } else {
            $this->selectedMonth = Carbon::parse($this->selectedMonth.'-01', 'Asia/Jakarta')
                ->subMonth()
                ->format('Y-m');
        }
    }

    public function nextMonth(): void
    {
        if ($this->viewMode === 'week') {
            $this->selectedDate = Carbon::parse($this->selectedDate ?: now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta')
                ->addWeek()
                ->toDateString();
            $this->selectedMonth = Carbon::parse($this->selectedDate, 'Asia/Jakarta')->format('Y-m');
        } else {
            $this->selectedMonth = Carbon::parse($this->selectedMonth.'-01', 'Asia/Jakarta')
                ->addMonth()
                ->format('Y-m');
        }
    }

    public function goToCurrentMonth(): void
    {
        $now = now('Asia/Jakarta');
        $this->selectedMonth = $now->format('Y-m');
        $this->selectedDate = $now->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function updatedSelectedItemId(): void
    {
        // Livewire auto-updates via wire:model.live
    }

    public function getMonthLabel(): string
    {
        $carbon = Carbon::parse($this->selectedMonth.'-01', 'Asia/Jakarta');
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $baseLabel = ($months[$carbon->month] ?? $carbon->format('F')).' '.$carbon->year;

        if ($this->viewMode === 'week') {
            $date = Carbon::parse($this->selectedDate ?: now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');
            $startOfWeek = $date->copy()->startOfWeek(Carbon::MONDAY);
            $endOfWeek = $date->copy()->endOfWeek(Carbon::SUNDAY);

            return "Minggu {$startOfWeek->format('d M')} - {$endOfWeek->format('d M Y')}";
        }

        return $baseLabel;
    }

    public function saveCategoryBuffer(string $category, int $before, int $after): void
    {
        $vendor = $this->getVendor();
        if (! $vendor) {
            return;
        }

        if (! app(EntitlementService::class)->isPro($vendor)) {
            Notification::make()
                ->title('Fitur Khusus Paket Pro')
                ->body('Pengaturan jeda Smart Buffer per kategori hanya tersedia untuk pelanggan paket Pro.')
                ->warning()
                ->send();

            return;
        }

        $settings = $vendor->category_buffer_settings ?? [];
        $settings[$category] = [
            'buffer_before' => max(0, $before),
            'buffer_after' => max(0, $after),
        ];

        $vendor->update(['category_buffer_settings' => $settings]);

        Notification::make()
            ->title('Jeda Buffer Kategori Disimpan')
            ->body("Jeda untuk kategori {$category} diatur: {$before} hari sebelum, {$after} hari sesudah.")
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            return [];
        }

        $isPro = app(EntitlementService::class)->isPro($vendor);

        $items = Item::where('vendor_id', $vendor->id)
            ->orderBy('name')
            ->get();

        $monthDate = Carbon::parse($this->selectedMonth.'-01', 'Asia/Jakarta');

        if ($this->viewMode === 'week') {
            $refDate = Carbon::parse($this->selectedDate ?: now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');
            $calendarStart = $refDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $calendarEnd = $refDate->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        } else {
            $startOfMonth = $monthDate->copy()->startOfMonth()->startOfDay();
            $endOfMonth = $monthDate->copy()->endOfMonth()->startOfDay();

            // ISO Day of Week: Monday = 1, Sunday = 7
            $startOfWeekDay = $startOfMonth->isoWeekday();
            $calendarStart = $startOfMonth->copy()->subDays($startOfWeekDay - 1);

            $endOfWeekDay = $endOfMonth->isoWeekday();
            $calendarEnd = $endOfMonth->copy()->addDays(7 - $endOfWeekDay);
        }

        $bufferMargin = 14;
        $queryStart = $calendarStart->copy()->subDays($bufferMargin)->toDateString();
        $queryEnd = $calendarEnd->copy()->addDays($bufferMargin)->toDateString();

        $bookingsQuery = Booking::query()
            ->where('vendor_id', $vendor->id)
            ->with(['item', 'renter'])
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_APPROVED,
                Booking::STATUS_ACTIVE,
                Booking::STATUS_RETURNED,
                Booking::STATUS_CLOSED,
            ])
            ->where('start_date', '<=', $queryEnd)
            ->where('end_date', '>=', $queryStart);

        if ($this->selectedItemId && $this->selectedItemId !== 'all') {
            $bookingsQuery->where('item_id', $this->selectedItemId);
        }

        $bookings = $bookingsQuery->get();

        // Map events by date and check clashes
        $eventsByDate = [];
        $clashes = [];

        foreach ($bookings as $booking) {
            $viewUrl = BookingResource::getUrl('view', ['record' => $booking->id]);
            $bStart = Carbon::parse($booking->start_date, 'Asia/Jakarta')->startOfDay();
            $bEnd = Carbon::parse($booking->end_date, 'Asia/Jakarta')->startOfDay();

            // 1. Buffer Before
            $bufBefore = (int) ($booking->item?->buffer_before ?? 0);
            if ($bufBefore > 0) {
                $bBufStart = $bStart->copy()->subDays($bufBefore);
                $curr = $bBufStart->copy();
                while ($curr->lt($bStart)) {
                    $dStr = $curr->toDateString();
                    $eventsByDate[$dStr][] = [
                        'id' => $booking->id,
                        'ulid' => $booking->ulid,
                        'type' => 'buffer',
                        'status' => 'buffer',
                        'status_label' => 'Jeda Siap (Buffer)',
                        'reference_code' => $booking->reference_code,
                        'item_name' => $booking->item?->name ?? 'Barang',
                        'renter_name' => $booking->renter?->name ?? 'Penyewa',
                        'view_url' => $viewUrl,
                    ];
                    $curr->addDay();
                }
            }

            // 2. Core Booking Dates
            $curr = $bStart->copy();
            while ($curr->lte($bEnd)) {
                $dStr = $curr->toDateString();
                $eventsByDate[$dStr][] = [
                    'id' => $booking->id,
                    'ulid' => $booking->ulid,
                    'type' => 'booking',
                    'status' => $booking->status,
                    'status_label' => match ($booking->status) {
                        Booking::STATUS_PENDING => 'Menunggu Approval',
                        Booking::STATUS_APPROVED => 'Disetujui',
                        Booking::STATUS_ACTIVE => 'Sedang Disewa',
                        Booking::STATUS_RETURNED => 'Dikembalikan',
                        Booking::STATUS_CLOSED => 'Selesai',
                        default => ucfirst($booking->status),
                    },
                    'reference_code' => $booking->reference_code,
                    'item_name' => $booking->item?->name ?? 'Barang',
                    'renter_name' => $booking->renter?->name ?? 'Penyewa',
                    'start_date' => $booking->start_date->format('d/m/Y'),
                    'end_date' => $booking->end_date->format('d/m/Y'),
                    'view_url' => $viewUrl,
                ];
                $curr->addDay();
            }

            // 3. Buffer After
            $bufAfter = (int) ($booking->item?->buffer_after ?? 0);
            if ($bufAfter > 0) {
                $bBufEnd = $bEnd->copy()->addDays($bufAfter);
                $curr = $bEnd->copy()->addDay();
                while ($curr->lte($bBufEnd)) {
                    $dStr = $curr->toDateString();
                    $eventsByDate[$dStr][] = [
                        'id' => $booking->id,
                        'ulid' => $booking->ulid,
                        'type' => 'buffer',
                        'status' => 'buffer',
                        'status_label' => 'Jeda Pembersihan (Buffer)',
                        'reference_code' => $booking->reference_code,
                        'item_name' => $booking->item?->name ?? 'Barang',
                        'renter_name' => $booking->renter?->name ?? 'Penyewa',
                        'view_url' => $viewUrl,
                    ];
                    $curr->addDay();
                }
            }
        }

        // Build Calendar Matrix
        $calendarGrid = [];
        $todayStr = now('Asia/Jakarta')->toDateString();
        $currDay = $calendarStart->copy();

        while ($currDay->lte($calendarEnd)) {
            $dateStr = $currDay->toDateString();
            $calendarGrid[] = [
                'date' => $dateStr,
                'day' => $currDay->day,
                'isCurrentMonth' => $currDay->month === $monthDate->month,
                'isToday' => $dateStr === $todayStr,
                'isSelected' => $dateStr === $this->selectedDate,
                'events' => $eventsByDate[$dateStr] ?? [],
            ];
            $currDay->addDay();
        }

        // Split into weeks of 7 days
        $weeks = array_chunk($calendarGrid, 7);

        $selectedDateEvents = $this->selectedDate ? ($eventsByDate[$this->selectedDate] ?? []) : [];

        return [
            'vendor' => $vendor,
            'isPro' => $isPro,
            'viewMode' => $this->viewMode,
            'items' => $items,
            'monthLabel' => $this->getMonthLabel(),
            'weeks' => $weeks,
            'selectedDate' => $this->selectedDate,
            'selectedDateLabel' => $this->selectedDate ? Carbon::parse($this->selectedDate, 'Asia/Jakarta')->format('d M Y') : null,
            'selectedDateEvents' => $selectedDateEvents,
            'daysOfWeek' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        ];
    }
}
