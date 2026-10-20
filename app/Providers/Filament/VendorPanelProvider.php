<?php

namespace App\Providers\Filament;

use App\Filament\Vendor\Pages\Auth\Register;
use App\Filament\Vendor\Pages\StoreSettings;
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
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
                'primary' => Color::Zinc,
            ])
            ->font('Inter Tight')
            ->defaultThemeMode(ThemeMode::Light)
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\Filament\Vendor\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\Filament\Vendor\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
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
                    ->label(fn () => (auth('vendor')->user()?->vendor?->isPro() ? '💎 Paket Pro' : '🟢 Paket Free').' — Kelola')
                    ->url(fn () => route('vendor.billing.index'))
                    ->icon('heroicon-o-credit-card'),
                MenuItem::make()
                    ->label('Pengaturan Toko')
                    ->url(fn () => StoreSettings::getUrl())
                    ->icon('heroicon-o-cog-6-tooth'),
            ])
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\Filament\Vendor\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
