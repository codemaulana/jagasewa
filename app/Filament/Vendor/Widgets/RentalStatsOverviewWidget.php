<?php

namespace App\Filament\Vendor\Widgets;

use App\Models\Booking;
use App\Services\FineCalculatorService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentalStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    public function getStats(): array
    {
        $vendor = auth('vendor')->user()?->vendor;

        if (! $vendor) {
            return [];
        }

        $today = now(FineCalculatorService::TIMEZONE)->toDateString();

        $pendingCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_PENDING)
            ->count();

        $activeCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->count();

        $returnsDueTodayCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', '<=', $today)
            ->count();

        $returnedNeedSettlementCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_RETURNED)
            ->count();

        $totalReturnsAttention = $returnsDueTodayCount + $returnedNeedSettlementCount;

        return [
            Stat::make('Pesanan Menunggu', $pendingCount)
                ->description($pendingCount > 0 ? 'Perlu tindakan verifikasi & persetujuan' : 'Semua pesanan telah diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'gray')
                ->url(url('/vendor/bookings')),

            Stat::make('Barang Sedang Disewa', $activeCount)
                ->description('Unit aktif di tangan penyewa')
                ->descriptionIcon('heroicon-m-truck')
                ->color($activeCount > 0 ? 'info' : 'gray')
                ->url(url('/vendor/bookings')),

            Stat::make('Pengembalian Perlu Dicek', $totalReturnsAttention)
                ->description($totalReturnsAttention > 0 ? "{$returnsDueTodayCount} jatuh tempo, {$returnedNeedSettlementCount} siap denda/settlement" : 'Tidak ada pengembalian mendesak')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($totalReturnsAttention > 0 ? 'danger' : 'success')
                ->url(url('/vendor/bookings')),
        ];
    }
}
