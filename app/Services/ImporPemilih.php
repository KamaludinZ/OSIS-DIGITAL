<?php

namespace App\Services;

use App\Enums\JenisPemilih;
use App\Models\Voter;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Baca berkas tanpa menyimpan apa pun.
     *
     * @return array{kolom_hilang: list<string>, baris: list<array{baris: int, nomor_induk: ?string, nama: string, keterangan: ?string, tanggal_lahir: ?string, galat: ?string}>}
     */
    public function baca(string $path, JenisPemilih $jenis, ?string $namaAsli = null): array
    {
        $ekstensi = Str::lower(pathinfo($namaAsli ?? $path, PATHINFO_EXTENSION));
        $reader = $ekstensi === 'xlsx' ? new XlsxReader : new CsvReader;

        try {
            $reader->open($path);
        } catch (\Throwable) {
            return ['kolom_hilang' => [], 'baris' => [], 'tak_terbaca' => true];
        }

        $kolomInduk = $jenis === JenisPemilih::Siswa ? 'nisn' : 'nik';
        $kolomKet = $jenis === JenisPemilih::Siswa ? 'kelas' : 'jabatan';
        $hasil = ['kolom_hilang' => [], 'baris' => []];
        $header = null;
        $nomor = 0;
        $dilihat = []; // nomor induk => baris pertama, untuk menolak data ganda dalam satu berkas

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $nomor++;
                $sel = $row->toArray();

                if ($header === null) {
                    $header = array_map(fn ($h) => Str::snake(Str::lower(trim((string) $h))), $sel);
                    $hasil['kolom_hilang'] = array_values(array_diff([$kolomInduk, 'nama', 'tanggal_lahir'], $header));

                    if ($hasil['kolom_hilang']) {
                        break 2;
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

                $hasil['baris'][] = [
                    'baris' => $nomor,
                    'nomor_induk' => $induk,
                    'nama' => $nama,
                    'keterangan' => Str::squish((string) ($data[$kolomKet] ?? '')) ?: null,
                    'tanggal_lahir' => $tanggal,
                    // Pesan berbahasa awam + cara memperbaikinya.
                    'galat' => match (true) {
                        ! $induk => $jenis === JenisPemilih::Siswa
                            ? 'NISN kosong atau bukan angka (paling banyak 10 angka). Di Excel, ubah format sel NISN menjadi Teks agar angka 0 di depan tidak hilang.'
                            : 'NIK harus tepat 16 angka. Di Excel, ubah format sel NIK menjadi Teks agar angkanya tidak berubah menjadi 3,57E+15.',
                        ! $nama => 'Nama belum diisi.',
                        ! $tanggal => 'Tanggal lahir tidak terbaca. Tulis seperti 2013-05-21 atau 21/05/2013.',
                        isset($dilihat[$induk]) => strtoupper($kolomInduk)." {$induk} ganda dengan baris {$dilihat[$induk]}. Hapus salah satu.",
                        default => null,
                    },
                ];

                if ($induk && ! isset($dilihat[$induk])) {
                    $dilihat[$induk] = $nomor;
                }
            }

            break; // hanya sheet pertama
        }

        $reader->close();

        return $hasil;
    }

    /** Masalah pada berkas secara keseluruhan (bukan per baris), dalam bahasa awam. */
    private static function galatBerkas(array $isi): ?string
    {
        if ($isi['tak_terbaca'] ?? false) {
            return 'Berkas tidak bisa dibaca. Simpan ulang sebagai CSV atau Excel (.xlsx) dari templat, lalu unggah lagi.';
        }

        if ($isi['kolom_hilang']) {
            return 'Kolom wajib tidak ada: '.implode(', ', $isi['kolom_hilang']).'. Judul kolom harus di baris pertama, sama persis dengan templat. Unduh templat lalu salin datamu ke dalamnya.';
        }

        return null;
    }

    /**
     * @return array{total: int, valid: int, baru: int, perbarui: int, gagal: list<string>, contoh: list<array>}
     */
    public function pratinjau(string $path, JenisPemilih $jenis, ?string $namaAsli = null): array
    {
        $isi = $this->baca($path, $jenis, $namaAsli);

        if ($galatBerkas = self::galatBerkas($isi)) {
            return ['total' => 0, 'valid' => 0, 'baru' => 0, 'perbarui' => 0, 'gagal' => [$galatBerkas], 'contoh' => []];
        }

        $valid = collect($isi['baris'])->whereNull('galat');
        $ada = Voter::where('jenis', $jenis)->whereIn('nomor_induk', $valid->pluck('nomor_induk'))->pluck('nomor_induk')->flip();

        return [
            'total' => count($isi['baris']),
            'valid' => $valid->count(),
            'baru' => $valid->reject(fn ($b) => isset($ada[$b['nomor_induk']]))->count(),
            'perbarui' => $valid->filter(fn ($b) => isset($ada[$b['nomor_induk']]))->count(),
            'gagal' => collect($isi['baris'])->whereNotNull('galat')->map(fn ($b) => "Baris {$b['baris']}: {$b['galat']}")->values()->all(),
            'contoh' => array_slice($isi['baris'], 0, 8),
        ];
    }

    /**
     * @return array{tambah: int, perbarui: int, sama: int, gagal: list<string>}
     */
    public function impor(string $path, JenisPemilih $jenis, ?string $namaAsli = null): array
    {
        $isi = $this->baca($path, $jenis, $namaAsli);
        $hasil = ['tambah' => 0, 'perbarui' => 0, 'sama' => 0, 'gagal' => []];

        if ($galatBerkas = self::galatBerkas($isi)) {
            $hasil['gagal'][] = $galatBerkas;

            return $this->catatLog($hasil, $jenis, $namaAsli ?? basename($path));
        }

        $kolomKet = $jenis === JenisPemilih::Siswa ? 'kelas' : 'jabatan';

        DB::transaction(function () use ($isi, $jenis, $kolomKet, &$hasil) {
            foreach ($isi['baris'] as $b) {
                if ($b['galat']) {
                    $hasil['gagal'][] = "Baris {$b['baris']}: {$b['galat']}";

                    continue;
                }

                $pemilih = Voter::firstOrNew(['jenis' => $jenis, 'nomor_induk' => $b['nomor_induk']]);
                $baru = ! $pemilih->exists;
                $pemilih->fill(['nama' => $b['nama'], $kolomKet => $b['keterangan'], 'tanggal_lahir' => $b['tanggal_lahir']]);

                if (! $baru && ! $pemilih->isDirty()) {
                    $hasil['sama']++;

                    continue;
                }

                $pemilih->save();
                $hasil[$baru ? 'tambah' : 'perbarui']++;
            }
        });

        return $this->catatLog($hasil, $jenis, $namaAsli ?? basename($path));
    }

    /** Simpan ringkasan impor ke log voter_imports; mengembalikan hasil + id log. */
    private function catatLog(array $hasil, JenisPemilih $jenis, string $namaBerkas): array
    {
        $log = \App\Models\VoterImport::create([
            'periode_id' => \App\Models\OsisPeriod::aktif()?->id,
            'user_id' => auth()->id(),
            'jenis' => $jenis->value,
            'nama_berkas' => mb_substr($namaBerkas, 0, 255),
            'tambah' => $hasil['tambah'],
            'perbarui' => $hasil['perbarui'],
            'sama' => $hasil['sama'],
            'dilewati' => count($hasil['gagal']),
            'galat' => $hasil['gagal'] ?: null,
        ]);

        if ($hasil['tambah'] || $hasil['perbarui']) {
            \App\Events\PemilihDiimpor::dispatch($log);
        }

        return [...$hasil, 'log_id' => $log->id];
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
