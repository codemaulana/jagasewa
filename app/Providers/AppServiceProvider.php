<?php

namespace App\Providers;

use App\Listeners\UpdateVendorUserLastLogin;
use App\Services\IdentityHashService;
use Carbon\Carbon;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Table;
use Filament\View\PanelsIconAlias;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (file_exists(app_path('Helpers/EntitlementHelper.php'))) {
            require_once app_path('Helpers/EntitlementHelper.php');
        }

        if (file_exists(app_path('Helpers/helpers.php'))) {
            require_once app_path('Helpers/helpers.php');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id', 'Indonesian');

        // Default Global Tabel: pagination 10/25/50, kolom bisa disembunyikan, tampilan kartu di mobile
        Table::configureUsing(function (Table $table): void {
            $table
                ->defaultPaginationPageOption(10)
                ->paginationPageOptions([10, 25, 50])
                ->columnManager()
                ->stackedOnMobile()
                ->emptyStateHeading('Belum ada data')
                ->emptyStateDescription('Data akan muncul di sini setelah Anda menambahkannya.');
        });

        // Default Global FileUpload: placeholder teks Bahasa Indonesia
        FileUpload::configureUsing(function (FileUpload $fileUpload): void {
            $fileUpload->placeholder('Tarik foto ke sini atau pilih dari perangkat');
        });

        // Icon Header & Sidebar
        FilamentIcon::register([
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => 'heroicon-o-bars-3-bottom-left',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => 'heroicon-o-bars-3',
            PanelsIconAlias::TOPBAR_OPEN_SIDEBAR_BUTTON => 'heroicon-o-bars-3',
            PanelsIconAlias::TOPBAR_CLOSE_SIDEBAR_BUTTON => 'heroicon-o-x-mark',
        ]);

        Event::listen(Login::class, UpdateVendorUserLastLogin::class);

        // Rate Limiter Configuration
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('booking.upload', function (Request $request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        RateLimiter::for('booking.public', function (Request $request) {
            $limits = [
                Limit::perHour(10)->by($request->ip()),
            ];

            if ($request->filled('phone')) {
                $phoneHash = IdentityHashService::hashIdentity(IdentityHashService::TYPE_WA, (string) $request->input('phone'));
                $limits[] = Limit::perHour(3)->by('wa:'.$phoneHash);
            }

            return $limits;
        });

        RateLimiter::for('signed-url', function (Request $request) {
            $key = optional($request->user('vendor'))->vendor_id ?? $request->ip();

            return Limit::perMinute(20)->by('signed-url:'.$key);
        });

        RateLimiter::for('blacklist.check', function (Request $request) {
            $key = optional($request->user('vendor'))->vendor_id ?? $request->ip();

            return Limit::perMinute(30)->by('blacklist-check:'.$key);
        });

        RateLimiter::for('password.reset', function (Request $request) {
            return Limit::perHour(3)->by($request->ip());
        });
    }
}
