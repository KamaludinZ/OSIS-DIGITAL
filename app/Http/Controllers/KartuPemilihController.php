<?php

namespace App\Http\Controllers;

use App\Models\Voter;
use App\Services\LayananPemilihan;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Lembar kartu pemilih siap cetak & gunting (berisi kode pilih). */
class KartuPemilihController extends Controller
{
    public function __invoke(Request $request, LayananPemilihan $pemilihan): View
    {
        $kelompok = $request->query('kelompok');

        $pemilih = Voter::terverifikasi()
            ->whereNotNull('kode_pilih')
            ->when($kelompok === 'gtk', fn ($q) => $q->where('jenis', 'gtk'))
            ->when($kelompok && $kelompok !== 'gtk', fn ($q) => $q->where('kelas', $kelompok))
            ->orderBy('jenis')->orderBy('kelas')->orderBy('nama')
            ->get();

        return view('laporan.kartu-pemilih', [
            'pemilih' => $pemilih,
            'kelompok' => $kelompok,
            'pemilihan' => $pemilihan->pemilihanAktif(),
        ]);
    }
}
