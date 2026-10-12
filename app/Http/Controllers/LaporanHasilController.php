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
            fputcsv($out, ['Laporan Hasil', $d['rekap']['judul']], ';');
            fputcsv($out, ['Dibuat', $d['dibuat']->translatedFormat('j F Y H:i').' WIB'], ';');
            fputcsv($out, ['Periode', $d['pemilihan']->mulai->translatedFormat('j F Y H:i').' – '.$d['pemilihan']->selesai->translatedFormat('j F Y H:i')], ';');
            fputcsv($out, ['Status', $d['rekap']['status'].($d['rekap']['hasil_publik'] ? ' (dipublikasikan)' : ' (belum dipublikasikan)')], ';');
            fputcsv($out, ['Total pemilih', $d['rekap']['total_pemilih']], ';');
            fputcsv($out, ['Suara masuk', $d['rekap']['suara_masuk']], ';');
            fputcsv($out, ['Partisipasi (%)', $koma($d['partisipasi'])], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['No. urut', 'Calon ketua', 'Calon wakil', 'Suara', 'Persentase (%)'], ';');
            foreach ($d['baris'] as $c) {
                fputcsv($out, [$c['nomor_urut'], $c['nama_ketua'], $c['nama_wakil'] ?? '', $c['suara'], $koma($c['persen'])], ';');
            }
            fputcsv($out, [], ';');
            fputcsv($out, ['Hasil', match ($d['pemenang']['status']) {
                'menang' => 'Terpilih: No. '.$d['pemenang']['kandidat']['nomor_urut'].' '.$d['pemenang']['kandidat']['nama_tampil'],
                'seri' => 'Seri — ditetapkan panitia sesuai tata tertib',
                default => 'Belum ada suara',
            }], ';');
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
