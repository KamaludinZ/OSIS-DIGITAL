<?php

namespace App\Support;

/**
 * Data tiruan Kotak Saran selama fase frontend. Disimpan di sesi agar kirim & tanggapi bisa dicoba;
 * diganti tabel suggestions di layer backend.
 */
class DataTiruanSaran
{
    private const KUNCI = 'tiruan.saran';

    /** @return list<array{id: int, isi: string, nama: ?string, kelas: ?string, dikirim: string, tanggapan: ?string, penanggap: ?string, ditanggapi: ?string, periode: string}> */
    public static function semua(): array
    {
        return session()->exists(self::KUNCI) ? session(self::KUNCI) : self::awal();
    }

    public static function kirim(string $isi, ?string $nama = null, ?string $kelas = null): array
    {
        $semua = self::semua();
        $saran = [
            'id' => (collect($semua)->max('id') ?? 0) + 1,
            'isi' => $isi, 'nama' => $nama, 'kelas' => $kelas,
            'dikirim' => now()->toDateTimeString(),
            'tanggapan' => null, 'penanggap' => null, 'ditanggapi' => null,
            'periode' => \App\Models\OsisPeriod::aktif()?->nama ?? '2026/2027',
        ];
        session([self::KUNCI => [$saran, ...$semua]]);

        return $saran;
    }

    public static function simpan(array $semua): void
    {
        session([self::KUNCI => array_values($semua)]);
    }

    private static function awal(): array
    {
        $hari = fn (int $n, int $jam = 9) => now()->subDays($n)->setTime($jam, 10)->toDateTimeString();

        return [
            ['id' => 5, 'isi' => 'Tolong tambah tempat sampah terpilah di dekat kantin, sering penuh saat istirahat.', 'nama' => null, 'kelas' => null, 'dikirim' => $hari(1), 'tanggapan' => null, 'penanggap' => null, 'ditanggapi' => null, 'periode' => '2026/2027'],
            ['id' => 4, 'isi' => 'Bagaimana kalau kajian Jumat pagi diselingi lomba hafalan antarkelas?', 'nama' => 'Nayla Safitri', 'kelas' => 'VIII-B', 'dikirim' => $hari(3), 'tanggapan' => null, 'penanggap' => null, 'ditanggapi' => null, 'periode' => '2026/2027'],
            ['id' => 3, 'isi' => 'Wifi perpustakaan sering mati, padahal dipakai untuk tugas.', 'nama' => null, 'kelas' => null, 'dikirim' => $hari(6), 'tanggapan' => 'Sudah kami sampaikan ke bagian sarpras; perbaikan dijadwalkan pekan depan.', 'penanggap' => 'Siti Nurhaliza', 'ditanggapi' => $hari(5, 13), 'periode' => '2026/2027'],
            ['id' => 2, 'isi' => 'Usul turnamen futsal antarkelas diadakan setelah ujian.', 'nama' => 'Galih Prakoso', 'kelas' => 'IX-A', 'dikirim' => $hari(40), 'tanggapan' => 'Siap, masuk program Divisi Olahraga semester ini.', 'penanggap' => 'Rizal Firmansyah', 'ditanggapi' => $hari(38), 'periode' => '2026/2027'],
            ['id' => 1, 'isi' => 'Jadwal piket kelas sebaiknya ditempel di mading.', 'nama' => null, 'kelas' => null, 'dikirim' => $hari(200), 'tanggapan' => 'Terima kasih, sudah ditempel di mading tiap lantai.', 'penanggap' => 'Pengurus 2025/2026', 'ditanggapi' => $hari(198), 'periode' => '2025/2026'],
        ];
    }
}
