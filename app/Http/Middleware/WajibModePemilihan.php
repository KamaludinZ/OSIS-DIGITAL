<?php

namespace App\Http\Middleware;

use App\Services\LayananModePemilihan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bilik suara, kandidat, dan hasil hanya terbuka saat mode pemilihan aktif.
 * Juga dipasang sebagai middleware persisten Livewire, sehingga aksi di halaman
 * yang sudah terbuka (mis. kirim suara) ikut tertolak begitu mode dimatikan.
 */
class WajibModePemilihan
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(LayananModePemilihan::class)->aktif(), 423, 'Mode pemilihan sedang nonaktif.');

        return $next($request);
    }
}
