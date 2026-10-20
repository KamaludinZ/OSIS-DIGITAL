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

        // Pratinjau tata letak dengan data contoh (tanpa kode asli) untuk halaman Cetak & Laporan.
        if ($request->boolean('contoh')) {
            return view('laporan.kartu-pemilih', [
                'pemilih' => collect([
                    ['Ahmad Contoh', 'VII-A', 'siswa', '123456'],
                    ['Siti Contoh', 'VIII-C', 'siswa', '654321'],
                    ['Bu Guru Contoh', null, 'gtk', '112233'],
                ])->map(fn ($d) => (new Voter)->forceFill(['nama' => $d[0], 'kelas' => $d[1], 'jenis' => $d[2], 'kode_pilih' => $d[3], 'jabatan' => $d[1] ? null : 'Guru']))
                    ->concat(collect(range(1, 3))->map(fn ($i) => (new Voter)->forceFill(['nama' => 'Pemilih Contoh '.$i, 'kelas' => 'IX-B', 'jenis' => 'siswa', 'kode_pilih' => '00000'.$i]))),
                'kelompok' => null,
                'pemilihan' => $pemilihan->pemilihanAktif(),
                'contoh' => true,
            ]);
        }

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
