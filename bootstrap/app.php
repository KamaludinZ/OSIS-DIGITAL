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
        // Pengguna yang sudah masuk lalu membuka /login diarahkan ke halaman awal perannya.
        $middleware->redirectUsersTo(fn (Illuminate\Http\Request $request) => $request->user()?->halamanAwal() ?? '/');

        // AuthenticateSession: sesi di perangkat lain ikut berakhir saat kata sandi diganti/direset.
        $middleware->web(append: [Illuminate\Session\Middleware\AuthenticateSession::class, App\Http\Middleware\PastikanAkunAktif::class]);

        // Kandidat dialihkan dari Ruang OSIM sebelum Filament menolak dengan 403.
        foreach ([App\Http\Middleware\PastikanAkunAktif::class, App\Http\Middleware\AlihkanKandidatDariPanel::class] as $penjaga) {
            $middleware->prependToPriorityList(before: Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, prepend: $penjaga);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
