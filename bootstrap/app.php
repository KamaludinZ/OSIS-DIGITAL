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

        // Kandidat dialihkan dari Ruang OSIM sebelum Filament menolak dengan 403.
        $middleware->prependToPriorityList(
            before: Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            prepend: App\Http\Middleware\AlihkanKandidatDariPanel::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
