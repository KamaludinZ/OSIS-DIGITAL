{{-- Panduan format kolom berkas impor pemilih + tombol unduh templat. $jenis: siswa | gtk --}}
@php
    $siswa = $jenis === 'siswa';
    $kolom = $siswa
        ? [['nisn', true, '0091234567', 'Maks. 10 angka. Format sel sebagai Teks.'], ['nama', true, 'Ahmad Fauzi', ''], ['kelas', false, 'VII-A', 'Dipakai untuk cetak kartu per kelas.'], ['tanggal_lahir', true, '2013-05-21', 'Atau 21/05/2013.']]
        : [['nik', true, '3573010101800001', 'Tepat 16 angka. Format sel sebagai Teks.'], ['nama', true, 'Drs. Contoh Guru', ''], ['jabatan', false, 'Guru Matematika', ''], ['tanggal_lahir', true, '1980-01-01', 'Atau 01/01/1980.']];
@endphp
<div class="flex flex-col gap-3 text-sm">
    <p class="text-tinta-2">Baris pertama berisi judul kolom persis seperti di bawah. Data dengan {{ $siswa ? 'NISN' : 'NIK' }} yang sama akan diperbarui, bukan digandakan.</p>
    <div class="overflow-x-auto rounded-lg ring-1 ring-garis">
        <table class="w-full text-left">
            <thead class="bg-hijau-50 text-xs uppercase tracking-wider text-tinta-2">
                <tr><th class="px-3 py-2">Kolom</th><th class="px-3 py-2">Wajib</th><th class="px-3 py-2">Contoh</th><th class="px-3 py-2">Catatan</th></tr>
            </thead>
            <tbody class="divide-y divide-garis">
                @foreach ($kolom as [$nama, $wajib, $contoh, $catatan])
                    <tr>
                        <td class="px-3 py-2 font-mono font-bold text-tinta">{{ $nama }}</td>
                        <td class="px-3 py-2">{{ $wajib ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-3 py-2 font-mono text-tinta-2">{{ $contoh }}</td>
                        <td class="px-3 py-2 text-tinta-2">{{ $catatan }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <a href="/templat/pemilih-{{ $jenis }}.csv" download class="inline-flex w-fit items-center gap-1.5 rounded-lg bg-hijau-600 px-3 py-2 font-bold text-white hover:bg-hijau-700">
        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="size-4" /> Unduh templat CSV {{ $siswa ? 'siswa' : 'GTK' }}
    </a>
</div>
