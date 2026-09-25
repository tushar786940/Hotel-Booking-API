<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            /*
             * ─── BASIC CONFIG ───
             * 'default' means this is the main panel
             * accessed at /admin
             */
            ->default()
            ->id('admin')
            ->path('admin')

            /*
             * ─── AUTHENTICATION ───
             * Use Laravel's default auth system
             * Admin users login at /admin/login
             */
            ->login()
            ->registration()
            ->passwordReset()

            /*
             * ─── BRANDING ───
             * Customize colors, logo, and favicon
             */
            ->colors([
                'primary' => Color::Blue,
                'danger'  => Color::Red,
                'gray'    => Color::Slate,
                'info'    => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->brandName('Hotel Booking Admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            // ->brandLogo(asset('images/logo.png'))  // Uncomment when you have a logo
            // ->favicon(asset('images/favicon.ico'))

            /*
             * ─── FEATURES ───
             */
            ->sidebarCollapsibleOnDesktop()  // Collapse sidebar on large screens
            ->maxContentWidth(Width::Full)   // Give data tables the available viewport width
            ->darkMode(true)                 // Enable dark mode toggle
            ->globalSearchKeyBindings(['command+k', 'ctrl+k']) // Spotlight search
            ->databaseNotifications()        // Bell icon for notifications

            /*
             * ─── DISCOVER RESOURCES & PAGES ───
             * Filament auto-discovers classes in these directories
             */
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\RevenueChart::class,
                \App\Filament\Widgets\BookingStatusChart::class,
                \App\Filament\Widgets\RecentBookingsTable::class,
            ])

            /*
             * ─── MIDDLEWARE ───
             */
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
