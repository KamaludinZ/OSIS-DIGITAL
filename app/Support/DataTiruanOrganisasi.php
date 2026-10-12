<?php

namespace App\Support;

/**
 * Data tiruan Kelola Organisasi selama fase frontend (fase 3).
 * Diganti tabel divisions/members/work_programs/events di layer backend.
 */
class DataTiruanOrganisasi
{
    public static function periode(): string
    {
        return '2025/2026';
    }

    public static function pengurusInti(): array
    {
        return [
            ['jabatan' => 'Ketua OSIM', 'nama' => 'Ahmad Fauzan', 'kelas' => 'IX-A'],
            ['jabatan' => 'Wakil Ketua', 'nama' => 'Siti Nurhaliza', 'kelas' => 'IX-C'],
            ['jabatan' => 'Sekretaris', 'nama' => 'Rania Putri', 'kelas' => 'VIII-B'],
            ['jabatan' => 'Bendahara', 'nama' => 'Yusuf Maulana', 'kelas' => 'VIII-D'],
        ];
    }

    public static function divisi(): array
    {
        return [
            ['nama' => 'Divisi Keagamaan', 'koordinator' => 'Hafidz Ramadhan', 'anggota' => 4, 'program' => 3],
            ['nama' => 'Divisi Pendidikan', 'koordinator' => 'Laila Khairunnisa', 'anggota' => 3, 'program' => 2],
            ['nama' => 'Divisi Lingkungan', 'koordinator' => 'Bima Saputra', 'anggota' => 3, 'program' => 2],
            ['nama' => 'Divisi Seni & Budaya', 'koordinator' => 'Aisyah Zahra', 'anggota' => 3, 'program' => 1],
            ['nama' => 'Divisi Olahraga', 'koordinator' => 'Rizal Firmansyah', 'anggota' => 3, 'program' => 2],
            ['nama' => 'Divisi Humas', 'koordinator' => 'Nabila Azzahra', 'anggota' => 2, 'program' => 1],
            ['nama' => 'Divisi Kebangsaan', 'koordinator' => 'Dafa Pratama', 'anggota' => 2, 'program' => 1],
            ['nama' => 'Divisi Sosial', 'koordinator' => 'Kayla Ramadhani', 'anggota' => 2, 'program' => 1],
        ];
    }

    public static function programKerja(): array
    {
        return [
            ['nama' => 'Kajian Jumat Pagi', 'divisi' => 'Divisi Keagamaan', 'status' => 'jalan'],
            ['nama' => 'Bank Sampah Sekolah', 'divisi' => 'Divisi Lingkungan', 'status' => 'jalan'],
            ['nama' => 'Kotak Aspirasi Digital', 'divisi' => 'Divisi Humas', 'status' => 'jalan'],
            ['nama' => 'Kelas Inspirasi Alumni', 'divisi' => 'Divisi Pendidikan', 'status' => 'selesai'],
            ['nama' => 'Turnamen Futsal Antarkelas', 'divisi' => 'Divisi Olahraga', 'status' => 'rencana'],
            ['nama' => 'Pentas Seni Tahunan', 'divisi' => 'Divisi Seni & Budaya', 'status' => 'rencana'],
            ['nama' => 'Peringatan Hari Santri', 'divisi' => 'Divisi Kebangsaan', 'status' => 'jalan'],
        ];
    }

    public static function jadwal(): array
    {
        return [
            ['nama' => 'Rapat Pleno Pengurus', 'tanggal' => today()->addDays(2)->toDateString(), 'tempat' => 'Ruang OSIM', 'divisi' => 'Pengurus Inti'],
            ['nama' => 'Lomba Kebersihan Kelas', 'tanggal' => today()->addDays(7)->toDateString(), 'tempat' => 'Seluruh kelas', 'divisi' => 'Divisi Lingkungan'],
            ['nama' => 'Peringatan Sumpah Pemuda', 'tanggal' => today()->addDays(16)->toDateString(), 'tempat' => 'Lapangan Upacara', 'divisi' => 'Divisi Kebangsaan'],
        ];
    }

    /** Daftar anggota pengurus tiruan (inti + anggota tiap divisi). */
    public static function anggota(): array
    {
        $nama = ['Fikri Ananda', 'Zahra Amalia', 'Raka Wibowo', 'Nayla Safitri', 'Ilham Saputra', 'Putri Ayu', 'Galih Prakoso', 'Annisa Rahma',
            'Fahri Hidayat', 'Salma Nadhifa', 'Reza Mahendra', 'Dinda Kirana', 'Arya Pamungkas', 'Keisha Aulia', 'Bagas Wirawan', 'Tiara Maharani',
            'Naufal Akbar', 'Alya Rahmadani', 'Hanif Fadillah', 'Syifa Nurfadilah', 'Daffa Alfarizi', 'Citra Maharani'];
        $kelas = ['VII-A', 'VII-C', 'VIII-A', 'VIII-B', 'VIII-D', 'IX-A', 'IX-B', 'IX-C'];

        $hasil = [];
        foreach (self::pengurusInti() as $i => $p) {
            $hasil[] = ['id' => $i + 1, 'nama' => $p['nama'], 'kelas' => $p['kelas'], 'divisi' => 'Pengurus Inti', 'jabatan' => $p['jabatan']];
        }

        $n = 0;
        foreach (self::divisi() as $d) {
            $hasil[] = ['id' => count($hasil) + 1, 'nama' => $d['koordinator'], 'kelas' => $kelas[$n % 8], 'divisi' => $d['nama'], 'jabatan' => 'Koordinator'];
            for ($k = 1; $k < $d['anggota']; $k++) {
                $hasil[] = ['id' => count($hasil) + 1, 'nama' => $nama[$n % count($nama)], 'kelas' => $kelas[($n + 3) % 8], 'divisi' => $d['nama'], 'jabatan' => 'Anggota'];
                $n++;
            }
            $n++;
        }

        return $hasil;
    }
}
