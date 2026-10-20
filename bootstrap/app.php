<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Di balik reverse proxy (Traefik/Coolify): percayai hanya jaringan privat, agar IP asli
        // pengunjung terbaca (batas tebakan kode per IP) dan X-Forwarded-For palsu dari luar tidak dipercaya.
        $middleware->trustProxies(
            at: array_filter(array_map('trim', explode(',', env('TRUSTED_PROXIES', '127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16')))),
            headers: Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias(['periode' => App\Http\Middleware\BatasiDataPeriode::class]);

        // Pengguna yang sudah masuk lalu membuka /login diarahkan ke halaman awal perannya.
        $middleware->redirectUsersTo(fn (Illuminate\Http\Request $request) => $request->user()?->halamanAwal() ?? '/');

        // AuthenticateSession: sesi di perangkat lain ikut berakhir saat kata sandi diganti/direset.
        $middleware->web(append: [Illuminate\Session\Middleware\AuthenticateSession::class, App\Http\Middleware\PastikanAkunAktif::class, App\Http\Middleware\HeaderKeamanan::class]);

        // Kandidat dialihkan dari Ruang OSIM sebelum Filament menolak dengan 403.
        foreach ([App\Http\Middleware\PastikanAkunAktif::class, App\Http\Middleware\AlihkanKandidatDariPanel::class] as $penjaga) {
            $middleware->prependToPriorityList(before: Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, prepend: $penjaga);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
