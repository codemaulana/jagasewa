<?php

namespace App\Filament\Vendor\Pages;

use App\Models\Booking;
use App\Models\Item;
use App\Models\Vendor;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Bisnis';

    protected static ?string $navigationLabel = 'Laporan & Analitik';

    protected static ?string $title = 'Laporan & Analitik Toko';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.vendor.pages.reports';

    public string $startDate = '';

    public string $endDate = '';

    public string $preset = 'this_month';

    public function mount(): void
    {
        $this->setPreset('this_month');
    }

    public function getVendor(): ?Vendor
    {
        return auth('vendor')->user()?->vendor;
    }

    /**
     * Staf operasional tidak memiliki akses ke laporan finansial toko.
     */
    public static function canAccess(): bool
    {
        $user = auth('vendor')->user();

        return $user && $user->canViewFinancials();
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
        $now = now('Asia/Jakarta');

        match ($preset) {
            'last_7_days' => [
                $this->startDate = $now->copy()->subDays(6)->toDateString(),
                $this->endDate = $now->toDateString(),
            ],
            'last_30_days' => [
                $this->startDate = $now->copy()->subDays(29)->toDateString(),
                $this->endDate = $now->toDateString(),
            ],
            'last_month' => [
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->toDateString(),
                $this->endDate = $now->copy()->subMonth()->endOfMonth()->toDateString(),
            ],
            default => [ // this_month
                $this->startDate = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate = $now->copy()->endOfMonth()->toDateString(),
            ],
        };
    }

    /**
     * Calculate financial summary, item utilization, and top renters for the selected period.
     */
    public function getViewData(): array
    {
        $vendor = $this->getVendor();
        $isPro = $vendor && $vendor->isPro();

        if (! $vendor) {
            return ['isPro' => false];
        }

        if (! $isPro) {
            return [
                'isPro' => false,
                'vendor' => $vendor,
            ];
        }

        $start = Carbon::parse($this->startDate, 'Asia/Jakarta')->startOfDay();
        $end = Carbon::parse($this->endDate, 'Asia/Jakarta')->endOfDay();
        $totalPeriodDays = max(1, $start->diffInDays($end) + 1);

        // 1. Bookings in period
        $bookings = Booking::where('vendor_id', $vendor->id)
            ->whereBetween('start_date', [$this->startDate, $this->endDate])
            ->with(['item', 'renter'])
            ->get();

        $validBookings = $bookings->whereIn('status', [
            Booking::STATUS_APPROVED,
            Booking::STATUS_ACTIVE,
            Booking::STATUS_RETURNED,
            Booking::STATUS_CLOSED,
        ]);

        $totalRevenue = (int) $validBookings->sum('total_price');
        $totalBookingsCount = $validBookings->count();
        $totalRentedDays = (int) $validBookings->sum('total_days');

        // 2. Item Utilization Analysis
        $items = Item::where('vendor_id', $vendor->id)->get();
        $itemStats = $items->map(function (Item $item) use ($validBookings, $totalPeriodDays) {
            $itemBookings = $validBookings->where('item_id', $item->id);
            $rentedDays = (int) $itemBookings->sum('total_days');
            $revenue = (int) $itemBookings->sum('total_price');
            $idleDays = max(0, $totalPeriodDays - $rentedDays);
            $utilizationRate = min(100, round(($rentedDays / $totalPeriodDays) * 100, 1));

            return [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->item_code,
                'category' => $item->category ?? 'Lainnya',
                'rented_days' => $rentedDays,
                'idle_days' => $idleDays,
                'utilization_rate' => $utilizationRate,
                'revenue' => $revenue,
                'bookings_count' => $itemBookings->count(),
            ];
        })->sortByDesc('revenue')->values();

        // 3. Top Renters in Period
        $topRenters = $validBookings->groupBy('renter_id')
            ->map(function ($group) {
                $first = $group->first();
                $renter = $first->renter;

                return [
                    'name' => $renter?->name ?? 'Penyewa',
                    'phone' => $renter?->phone ?? '-',
                    'bookings_count' => $group->count(),
                    'total_spent' => (int) $group->sum('total_price'),
                ];
            })->sortByDesc('total_spent')->take(5)->values();

        return [
            'isPro' => true,
            'vendor' => $vendor,
            'totalRevenue' => $totalRevenue,
            'totalBookingsCount' => $totalBookingsCount,
            'totalRentedDays' => $totalRentedDays,
            'totalPeriodDays' => $totalPeriodDays,
            'itemStats' => $itemStats,
            'topRenters' => $topRenters,
            'exportUrl' => route('vendor.reports.export', [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
            ]),
        ];
    }
}
