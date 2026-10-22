<?php

namespace App\Filament\Vendor\Widgets;

use App\Helpers\FormatHelper;
use App\Models\Booking;
use App\Services\VendorMetricsService;
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

        $metricsService = app(VendorMetricsService::class);
        $tasks = $metricsService->getTodayTasks($vendor);
        $monthlyRevenue = $metricsService->getMonthlyRevenue($vendor);

        $now = now(VendorMetricsService::TIMEZONE);
        $periodLabel = '1 s/d '.$now->copy()->endOfMonth()->locale('id')->translatedFormat('d M Y');

        // Hak akses staf finansial vs operasional
        $user = auth('vendor')->user();
        $canViewFinancials = ! $user || $user->canViewFinancials();

        $activeBookingsCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_ACTIVE)
            ->count();

        // Booking Menunggu Konfirmasi
        $pendingCount = Booking::where('vendor_id', $vendor->id)
            ->where('status', Booking::STATUS_PENDING)
            ->count();

        $middleStat = $canViewFinancials
            ? Stat::make('Pendapatan Bulan Ini', FormatHelper::rupiah($monthlyRevenue))
                ->description("Periode {$periodLabel} · Pesanan terkonfirmasi")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->extraAttributes([
                    'title' => 'Total biaya sewa dari pesanan berstatus disetujui, aktif, dikembalikan, dan selesai.',
                ])
                ->url(url('/vendor/reports'))
            : Stat::make('Pesanan Sedang Aktif', $activeBookingsCount)
                ->description('Unit yang sedang disewa saat ini')
                ->descriptionIcon('heroicon-m-truck')
                ->color('info')
                ->url(url('/vendor/bookings'));

        return [
            Stat::make('Tugas Hari Ini', $tasks['total'])
                ->description("{$tasks['pickups']} Pengambilan · {$tasks['returns']} Pengembalian · {$tasks['overdue']} Terlambat")
                ->descriptionIcon($tasks['overdue'] > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-calendar-days')
                ->color($tasks['overdue'] > 0 ? 'danger' : ($tasks['total'] > 0 ? 'warning' : 'success'))
                ->extraAttributes([
                    'title' => 'Klik untuk melihat rincian agenda tugas hari ini di bawah',
                ])
                ->url('#agenda-hari-ini'),

            $middleStat,

            Stat::make('Menunggu Konfirmasi', $pendingCount)
                ->description($pendingCount > 0 ? "{$pendingCount} pesanan baru perlu diverifikasi" : 'Semua permohonan telah diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'gray')
                ->url(url('/vendor/bookings?tableFilters[status][value]=pending')),
        ];
    }
}
