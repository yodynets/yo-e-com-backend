<?php

declare(strict_types = 1);

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Yeod\Shared\Infrastructure\Module\ModuleRegistry;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $modules = app(ModuleRegistry::class);

        return $panel
            ->default()
            ->id('dashboard')
            ->path((string)config('app.filament_path', 'dashboard'))
            // --- AUTH ------------------------------
            ->login()
            // --- BRANDING --------------------------
//            ->brandName('Kplus Management System')
//            ->brandLogo(asset('images/logo.jpg'))
//            ->brandLogoHeight('2rem')
//            ->favicon(asset('images/favicon.png'))
            ->colors([
                'primary' => Color::Emerald,
            ])
            // --- BRANDING --------------------------
            ->darkMode(false)
            // --- SIDEBAR ---------------------------
            ->sidebarCollapsibleOnDesktop()
            // --- NAVIGATION GROUPS -----------------
            ->navigationGroups([
                NavigationGroup::make()->label('Master Data'),
                NavigationGroup::make()->label('Inventory'),
                NavigationGroup::make()->label('Transactions'),
                NavigationGroup::make()->label('Adjustments'),
                NavigationGroup::make()->label('Reports'),
                NavigationGroup::make()->label('Settings'),
            ])

            // --- DASHBOARD -------------------------
            ->pages([
                Dashboard::class,
            ])
            // --- MIDDLEWARE -------------------------
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                ConvertEmptyStringsToNull::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DispatchServingFilamentEvent::class,
                DisableBladeIconComponents::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->resources($modules->resources())
            ->pages($modules->pages())
            ->widgets($modules->widgets())

//            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
//            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
//            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
//            ->widgets([
//                AccountWidget::class,
//                FilamentInfoWidget::class,
//            ])
            ;
    }
}
