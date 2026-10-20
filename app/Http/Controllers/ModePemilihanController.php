<?php

namespace App\Http\Controllers;

use App\Exceptions\PeriodeDitolak;
use App\Services\LayananModePemilihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModePemilihanController extends Controller
{
    /** Ubah sakelar mode pemilihan periode aktif (rute dijaga gate ubah-mode-pemilihan). */
    public function __invoke(Request $request, LayananModePemilihan $layanan): JsonResponse|RedirectResponse
    {
        $request->validate(['aktif' => ['required', 'boolean']]);

        try {
            $periode = $layanan->ubah($request->boolean('aktif'), $request->user());
        } catch (PeriodeDitolak $e) {
            return $request->wantsJson()
                ? response()->json(['pesan' => $e->getMessage()], 409)
                : back()->withErrors(['aktif' => $e->getMessage()]);
        }

        $pesan = $periode->mode_pemilihan ? 'Mode pemilihan dinyalakan' : 'Mode pemilihan dimatikan';

        return $request->wantsJson()
            ? response()->json(['aktif' => $periode->mode_pemilihan, 'periode' => $periode->nama, 'pesan' => $pesan])
            : back()->with('status', $pesan);
    }
}
