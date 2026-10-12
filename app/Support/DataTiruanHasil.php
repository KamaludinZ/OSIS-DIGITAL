<?php

namespace App\Support;

/**
 * Data tiruan rekap suara selama fase frontend. Nama kandidat diambil dari
 * daftar asli; angka suara tiruan dikunci per nomor urut. Diganti hitungan
 * dari tabel votes saat layer backend dikerjakan.
 */
class DataTiruanHasil
{
    /**
     * @param  array  $kandidat  hasil LayananPemilihan::daftarKandidat()
     * @param  string|null  $simulasi  berjalan | selesai | publik (untuk mencoba tampilan)
     */
    public static function rekap(array $kandidat, ?array $pemilihan, ?string $simulasi = null): array
    {
        $suaraTiruan = [1 => 214, 2 => 251, 3 => 147];
        $status = match ($simulasi) {
            'berjalan' => 'berjalan',
            'selesai', 'publik' => 'selesai',
            default => $pemilihan['status'] ?? 'berjalan',
        };
        $hasilPublik = $simulasi === 'publik' || ($simulasi === null && ($pemilihan['hasil_publik'] ?? false));

        $perKandidat = collect($kandidat)->map(fn ($c) => [
            'id' => $c['id'],
            'nomor_urut' => $c['nomor_urut'],
            'nama_ketua' => $c['nama_ketua'],
            'nama_wakil' => $c['nama_wakil'],
            'foto_url' => $c['foto_url'],
            'suara' => $suaraTiruan[$c['nomor_urut']] ?? 0,
        ])->values();

        $suaraMasuk = $perKandidat->sum('suara');

        return [
            'judul' => $pemilihan['judul'] ?? 'Pemilihan Ketua OSIM',
            'status' => $status,
            'hasil_publik' => $hasilPublik,
            'total_pemilih' => 840,
            'suara_masuk' => $suaraMasuk,
            'diperbarui' => now()->toDateTimeString(),
            'kandidat' => $perKandidat->all(),
        ];
    }
}
