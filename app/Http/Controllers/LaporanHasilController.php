<?php

namespace App\Http\Controllers;

use App\Services\LayananHasil;
use App\Services\LayananPemilihan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Laporan hasil pemilihan untuk arsip sekolah (CSV & berita acara siap cetak). */
class LaporanHasilController extends Controller
{
    public function __construct(private LayananPemilihan $pemilihan, private LayananHasil $hasil) {}

    /** Data laporan; 404 bila tidak ada pemilihan, 403 bila belum boleh dilihat. */
    private function data(): array
    {
        $pemilihan = $this->pemilihan->pemilihanAktif();
        abort_unless($pemilihan, 404);
        Gate::authorize('lihat-perolehan', $pemilihan);

        $rekap = $this->hasil->rekap($pemilihan);
        $pemenang = $this->hasil->pemenang($pemilihan);
        $persen = fn (int $n, int $t) => $t > 0 ? round($n / $t * 100, 1) : 0.0;

        return [
            'pemilihan' => $pemilihan,
            'rekap' => $rekap,
            'pemenang' => $pemenang,
            'partisipasi' => $persen($rekap['suara_masuk'], $rekap['total_pemilih']),
            'baris' => collect($rekap['kandidat'])->sortBy('nomor_urut')->map(fn ($c) => $c + ['persen' => $persen($c['suara'], $rekap['suara_masuk'])])->values()->all(),
            'dibuat' => now(),
        ];
    }

    public function cetak(): View
    {
        return view('laporan.hasil', $this->data());
    }

    public function csv(): StreamedResponse
    {
        $d = $this->data();
        $koma = fn (float $x) => str_replace('.', ',', (string) $x);
        $nama = 'laporan-hasil-'.Str::slug($d['rekap']['judul']).'.csv';

        return response()->streamDownload(function () use ($d, $koma) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            // Sel teks yang diawali = + - @ dianggap rumus oleh Excel; beri awalan ' agar tetap teks.
            $aman = fn ($sel) => is_string($sel) && preg_match('/^[=+\-@\t\r]/', $sel) ? "'".$sel : $sel;
            $tulis = fn (array $baris) => fputcsv($out, array_map($aman, $baris), ';');
            $tulis(['Laporan Hasil', $d['rekap']['judul']]);
            $tulis(['Dibuat', $d['dibuat']->translatedFormat('j F Y H:i').' WIB']);
            $tulis(['Periode', $d['pemilihan']->mulai->translatedFormat('j F Y H:i').' – '.$d['pemilihan']->selesai->translatedFormat('j F Y H:i')]);
            $tulis(['Status', $d['rekap']['status'].($d['rekap']['hasil_publik'] ? ' (dipublikasikan)' : ' (belum dipublikasikan)')]);
            $tulis(['Total pemilih', $d['rekap']['total_pemilih']]);
            $tulis(['Suara masuk', $d['rekap']['suara_masuk']]);
            $tulis(['Partisipasi (%)', $koma($d['partisipasi'])]);
            $tulis([]);
            $tulis(['No. urut', 'Calon ketua', 'Calon wakil', 'Suara', 'Persentase (%)']);
            foreach ($d['baris'] as $c) {
                $tulis([$c['nomor_urut'], $c['nama_ketua'], $c['nama_wakil'] ?? '', $c['suara'], $koma($c['persen'])]);
            }
            $tulis([]);
            $tulis(['Hasil', match ($d['pemenang']['status']) {
                'menang' => 'Terpilih: No. '.$d['pemenang']['kandidat']['nomor_urut'].' '.$d['pemenang']['kandidat']['nama_tampil'],
                'seri' => 'Seri — ditetapkan panitia sesuai tata tertib',
                default => 'Belum ada suara',
            }]);
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
