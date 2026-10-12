<?php

namespace App\Services;

use App\Enums\JenisPemilih;
use App\Models\Voter;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Impor data pemilih (siswa / GTK) dari CSV atau Excel (.xlsx).
 * Kolom siswa: nisn, nama, kelas, tanggal_lahir.
 * Kolom GTK  : nik, nama, jabatan, tanggal_lahir.
 * Baris dengan nomor induk yang sudah ada diperbarui (bukan digandakan).
 */
class ImporPemilih
{
    /**
     * @return array{tambah: int, perbarui: int, gagal: list<string>}
     */
    public function impor(string $path, JenisPemilih $jenis, ?string $namaAsli = null): array
    {
        $ekstensi = Str::lower(pathinfo($namaAsli ?? $path, PATHINFO_EXTENSION));
        $reader = $ekstensi === 'xlsx' ? new XlsxReader : new CsvReader;
        $reader->open($path);

        $kolomInduk = $jenis === JenisPemilih::Siswa ? 'nisn' : 'nik';
        $kolomKet = $jenis === JenisPemilih::Siswa ? 'kelas' : 'jabatan';
        $hasil = ['tambah' => 0, 'perbarui' => 0, 'gagal' => []];
        $header = null;
        $baris = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $baris++;
                $sel = $row->toArray();

                if ($header === null) {
                    $header = array_map(fn ($h) => Str::snake(Str::lower(trim((string) $h))), $sel);
                    $wajib = [$kolomInduk, 'nama', 'tanggal_lahir'];
                    if ($hilang = array_diff($wajib, $header)) {
                        $reader->close();
                        $hasil['gagal'][] = 'Kolom wajib tidak ada: '.implode(', ', $hilang).'. Gunakan templat yang disediakan.';

                        return $hasil;
                    }

                    continue;
                }

                if (collect($sel)->filter(fn ($v) => filled($v))->isEmpty()) {
                    continue; // baris kosong
                }

                $data = array_combine($header, array_pad(array_slice($sel, 0, count($header)), count($header), null));
                $induk = $this->nomorInduk($data[$kolomInduk] ?? null, $jenis);
                $nama = Str::squish((string) ($data['nama'] ?? ''));
                $tanggal = $this->tanggal($data['tanggal_lahir'] ?? null);

                if (! $induk || ! $nama || ! $tanggal) {
                    $hasil['gagal'][] = "Baris {$baris}: ".(! $induk ? strtoupper($kolomInduk).' kosong/tidak valid' : (! $nama ? 'nama kosong' : 'tanggal lahir tidak valid'));

                    continue;
                }

                $pemilih = Voter::firstOrNew(['jenis' => $jenis, 'nomor_induk' => $induk]);
                $baru = ! $pemilih->exists;
                $pemilih->fill([
                    'nama' => $nama,
                    $kolomKet => Str::squish((string) ($data[$kolomKet] ?? '')) ?: null,
                    'tanggal_lahir' => $tanggal,
                ]);
                $pemilih->save();
                $hasil[$baru ? 'tambah' : 'perbarui']++;
            }

            break; // hanya sheet pertama
        }

        $reader->close();

        return $hasil;
    }

    /** NISN 10 digit (nol di depan sering hilang di Excel); NIK 16 digit. */
    private function nomorInduk(mixed $nilai, JenisPemilih $jenis): ?string
    {
        $angka = preg_replace('/\D/', '', is_float($nilai) ? number_format($nilai, 0, '', '') : (string) $nilai);

        if ($angka === '') {
            return null;
        }

        if ($jenis === JenisPemilih::Siswa) {
            return strlen($angka) <= 10 ? str_pad($angka, 10, '0', STR_PAD_LEFT) : null;
        }

        return strlen($angka) === 16 ? $angka : null;
    }

    private function tanggal(mixed $nilai): ?string
    {
        if ($nilai instanceof DateTimeInterface) {
            return Carbon::instance($nilai)->toDateString();
        }

        $teks = trim((string) $nilai);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y'] as $format) {
            try {
                $tgl = Carbon::createFromFormat('!'.$format, $teks);
                if ($tgl && $tgl->format($format) === $teks) {
                    return $tgl->toDateString();
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        return null;
    }
}
