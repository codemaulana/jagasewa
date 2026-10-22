<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialAvatarProvider;
use App\Filament\Vendor\Pages\Auth\Register;
use App\Filament\Vendor\Pages\Dashboard;
use App\Filament\Vendor\Pages\StoreSettings;
use App\Filament\Vendor\Widgets\OnboardingChecklistWidget;
use App\Filament\Vendor\Widgets\RentalStatsOverviewWidget;
use App\Filament\Vendor\Widgets\StoreLinkAndPlanWidget;
use App\Filament\Vendor\Widgets\TodayAgendaWidget;
use App\Http\Middleware\EnsureVendorOwnership;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
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
                'primary' => [
                    50 => '#f6f6f5',
                    100 => '#e7e7e5',
                    200 => '#d1d1cd',
                    300 => '#b2b1ab',
                    400 => '#6b6963',
                    500 => '#1B1B19',
                    600 => '#1B1B19',
                    700 => '#161614',
                    800 => '#11110f',
                    900 => '#0c0c0b',
                    950 => '#050505',
                ],
            ])
            ->font('Plus Jakarta Sans')
            ->viteTheme('resources/css/app.css')
            ->sidebarWidth('248px')
            ->collapsedSidebarWidth('72px')
            ->collapsibleNavigationGroups(false)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@vite(['resources/js/app.js'])"))
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.vendor.layout.topbar-badge'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.vendor.layout.bottom-nav'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.vendor.layout.sidebar-plan-card'))
            ->darkMode(false)
            ->defaultThemeMode(ThemeMode::Light)
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\Filament\Vendor\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\Filament\Vendor\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Langganan Toko')
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
                TodayAgendaWidget::class,
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
