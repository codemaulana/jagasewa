<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialAvatarProvider;
use App\Filament\Vendor\Pages\Auth\Register;
use App\Filament\Vendor\Pages\StoreSettings;
use App\Filament\Vendor\Widgets\OnboardingChecklistWidget;
use App\Filament\Vendor\Widgets\RentalStatsOverviewWidget;
use App\Filament\Vendor\Widgets\StoreLinkAndPlanWidget;
use App\Http\Middleware\EnsureVendorOwnership;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('vendor')
            ->brandName('JagaSewa')
            ->brandLogo(fn () => asset('jagasewa_logo1.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(fn () => asset('jagasewa_logo3.png'))
            ->authGuard('vendor')
            ->authPasswordBroker('vendor_users')
            ->login()
            ->registration(Register::class)
            ->colors([
                'primary' => Color::hex('#1B1B19'),
            ])
            ->font('Plus Jakarta Sans')
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@vite(['resources/css/app.css', 'resources/js/app.js'])"))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.vendor.layout.topbar-badge'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.vendor.layout.bottom-nav'))
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\Filament\Vendor\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\Filament\Vendor\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Ekspor Laporan CSV')
                    ->url(fn () => route('vendor.reports.export'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn () => auth('vendor')->user()?->vendor?->isPro() ?? false)
                    ->sort(85),
                NavigationItem::make('Paket & Kuota Toko')
                    ->url(fn () => route('vendor.billing.index'))
                    ->icon('heroicon-o-sparkles')
                    ->badge(
                        fn () => auth('vendor')->user()?->vendor?->isPro() ? 'PRO' : 'FREE',
                        fn () => auth('vendor')->user()?->vendor?->isPro() ? 'success' : 'gray'
                    )
                    ->sort(90),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => (auth('vendor')->user()?->vendor?->isPro() ? 'Paket Pro' : 'Paket Free').' — Kelola')
                    ->url(fn () => route('vendor.billing.index'))
                    ->icon('heroicon-o-credit-card'),
                MenuItem::make()
                    ->label('Pengaturan Toko')
                    ->url(fn () => StoreSettings::getUrl())
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->defaultAvatarProvider(InitialAvatarProvider::class)
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\Filament\Vendor\Widgets')
            ->widgets([
                StoreLinkAndPlanWidget::class,
                RentalStatsOverviewWidget::class,
                OnboardingChecklistWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureVendorOwnership::class,
            ]);
    }
}
