<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\RecentReportsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\TodayClassesWidget;
use App\Filament\Widgets\TomorrowClassesWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('نظام متابعة الطلاب')
            ->login()
            ->colors([
                'primary' => Color::Pink,
                'gray' => [
                    50 => '#fafafa',
                    100 => '#f4f4f5',
                    200 => '#e4e4e7',
                    300 => '#d4d4d8',
                    400 => '#a1a1aa',
                    500 => '#71717a',
                    600 => '#52525b',
                    700 => '#3f192b',
                    800 => '#2d0f1e',
                    900 => '#1f0915',
                    950 => '#14040d',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Red,
                'info' => Color::Sky,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('الإدارة الأكاديمية')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('الإدارة المالية')
                    ->collapsible(false),
                NavigationGroup::make()
                    ->label('إدارة النظام')
                    ->collapsible(false),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverviewWidget::class,
                TodayClassesWidget::class,
                TomorrowClassesWidget::class,
                RecentReportsWidget::class,
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
            ]);
    }
}
