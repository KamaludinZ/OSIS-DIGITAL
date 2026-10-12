<?php

namespace App\Http\Middleware;

use App\Enums\Peran;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Akun kandidat tidak punya Ruang OSIM: arahkan ke tanya jawab miliknya, bukan halaman 403. */
class AlihkanKandidatDariPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === Peran::Kandidat) {
            return redirect($request->user()->halamanAwal());
        }

        return $next($request);
    }
}
