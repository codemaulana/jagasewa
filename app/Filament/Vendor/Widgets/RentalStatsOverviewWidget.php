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
        $startOfMonth = now(FineCalculatorService::TIMEZONE)->startOfMonth()->toDateString();
        $endOfMonth = now(FineCalculatorService::TIMEZONE)->endOfMonth()->toDateString();

        // 1. Tugas Hari Ini: Pengambilan, Pengembalian, Terlambat
        $pickupsToday = Booking::where('vendor_id', $vendor->id)
            ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_PENDING])
            ->whereDate('start_date', $today)
            ->count();

        $returnsToday = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', $today)
            ->count();

        $overdueReturns = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->whereDate('end_date', '<', $today)
            ->count();

        $totalTodayTasks = $pickupsToday + $returnsToday + $overdueReturns;

        // 2. Pendapatan Bulan Ini (Hanya Pemilik Toko) atau Pesanan Aktif (Staf Operasional)
        $user = auth('vendor')->user();
        $canViewFinancials = ! $user || $user->canViewFinancials();

        $monthlyRevenue = (int) Booking::where('vendor_id', $vendor->id)
            ->whereIn('status', [Booking::STATUS_ACTIVE, Booking::STATUS_RETURNED, Booking::STATUS_CLOSED])
            ->whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->sum('total_price');

        $activeBookingsCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->count();

        // 3. Booking Menunggu Konfirmasi
        $pendingCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_PENDING)
            ->count();

        $middleStat = $canViewFinancials
            ? Stat::make('Pendapatan Bulan Ini', 'Rp '.number_format($monthlyRevenue, 0, ',', '.'))
                ->description('Omzet transaksi sewa bulan '.now(FineCalculatorService::TIMEZONE)->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url(url('/vendor/bookings'))
            : Stat::make('Pesanan Sedang Aktif', $activeBookingsCount)
                ->description('Unit yang sedang disewa saat ini')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->url(url('/vendor/bookings'));

        return [
            Stat::make('Tugas Hari Ini', $totalTodayTasks)
                ->description("{$pickupsToday} Pengambilan · {$returnsToday} Pengembalian · {$overdueReturns} Terlambat")
                ->descriptionIcon($overdueReturns > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-calendar-days')
                ->color($overdueReturns > 0 ? 'danger' : ($totalTodayTasks > 0 ? 'warning' : 'success'))
                ->url(url('/vendor/bookings')),

            $middleStat,

            Stat::make('Menunggu Konfirmasi', $pendingCount)
                ->description($pendingCount > 0 ? "{$pendingCount} pesanan baru perlu diverifikasi" : 'Semua permohonan telah diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'gray')
                ->url(url('/vendor/bookings')),
        ];
    }
}
