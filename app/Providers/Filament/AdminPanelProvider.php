<?php

namespace App\Providers\Filament;

use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
            ->default()
            ->id('admin')
            ->path('admin')
            // Masuk lewat halaman login OSIM (/login); tidak ada login terpisah.
            ->brandName('Ruang OSIM')
            ->brandLogo(fn () => view('filament.merek'))
            ->brandLogoHeight('2.75rem')
            ->favicon('/favicon.png')
            ->font('Figtree Variable', provider: LocalFontProvider::class)
            ->darkMode(false)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::hex('#0f7a3d'),
                // Abu bernuansa hijau (tinta & tinta-2 dunia OSIM), kontras teks >= 4.5:1 mulai 500.
                'gray' => [
                    50 => '#f6faf7', 100 => '#eef4ef', 200 => '#dbe7de', 300 => '#c0d2c5', 400 => '#8fa597',
                    500 => '#5a7062', 600 => '#4b6355', 700 => '#3d5c48', 800 => '#24382c', 900 => '#14261b', 950 => '#0b170f',
                ],
                'danger' => Color::hex('#d7261e'),
                'warning' => Color::hex('#d9a800'),
                'success' => Color::hex('#13894a'),
            ])
            ->homeUrl('/')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                \App\Http\Middleware\PastikanAkunAktif::class,
                \App\Http\Middleware\AlihkanKandidatDariPanel::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->userMenuItems([
                \Filament\Actions\Action::make('pengaturan-akun')->label('Pengaturan akun')
                    ->url(fn () => route('settings.profile'))->icon(\Filament\Support\Icons\Heroicon::OutlinedCog6Tooth),
                \Filament\Actions\Action::make('lihat-situs')->label('Lihat situs OSIM')
                    ->url(fn () => route('home'))->icon(\Filament\Support\Icons\Heroicon::OutlinedGlobeAlt),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
