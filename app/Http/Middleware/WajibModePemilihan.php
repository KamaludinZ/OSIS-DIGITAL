<?php

namespace App\Http\Middleware;

use App\Services\LayananModePemilihan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bilik suara, kandidat, dan hasil hanya terbuka saat mode pemilihan aktif.
 * - Kunjungan biasa (GET halaman) diarahkan ke halaman pemberitahuan /terkunci.
 * - Permintaan lain (aksi Livewire, JSON, unduhan CSV, POST) ditolak 423.
 * Juga dipasang sebagai middleware persisten Livewire, sehingga aksi di halaman
 * yang sudah terbuka (mis. kirim suara) ikut tertolak begitu mode dimatikan.
 */
class WajibModePemilihan
{
    /** Awalan alamat => nama halaman di pemberitahuan. */
    private const HALAMAN = ['bilik-suara' => 'bilik-suara', 'kandidat' => 'kandidat', 'hasil' => 'hasil'];

    public function handle(Request $request, Closure $next): Response
    {
        if (app(LayananModePemilihan::class)->aktif()) {
            return $next($request);
        }

        $halaman = self::HALAMAN[$request->segment(1)] ?? null;
        $kunjungan = $request->isMethod('GET') && ! $request->expectsJson() && ! $request->hasHeader('X-Livewire')
            && ! str_ends_with($request->path(), '.csv');

        abort_unless($kunjungan, 423, 'Mode pemilihan sedang nonaktif.');

        return redirect()->route('terkunci', array_filter(['halaman' => $halaman]));
    }
}
