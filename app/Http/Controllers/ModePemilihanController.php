<?php

namespace App\Http\Controllers;

use App\Exceptions\PeriodeDitolak;
use App\Services\LayananModePemilihan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModePemilihanController extends Controller
{
    /** Status sakelar + pratinjau menu publik kedua mode (untuk staf yang sudah masuk). */
    public function tampil(LayananModePemilihan $layanan): JsonResponse
    {
        $status = $layanan->status();

        return response()->json([
            ...$status,
            'diubah_pada' => $status['diubah_pada']?->toIso8601String(),
            'menu' => array_column($status['menu'], 'label'),
            'pratinjau' => array_map(fn ($p) => ['menu' => array_column($p['menu'], 'label'), 'sedang_tampil' => $p['sedang_tampil']], $layanan->pratinjau()),
        ]);
    }

    /** Ubah sakelar mode pemilihan periode aktif (rute dijaga gate ubah-mode-pemilihan). */
    public function ubah(Request $request, LayananModePemilihan $layanan): JsonResponse|RedirectResponse
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
