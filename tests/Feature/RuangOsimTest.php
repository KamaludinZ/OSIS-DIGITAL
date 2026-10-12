<?php

namespace Tests\Feature;

use App\Enums\Peran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuangOsimTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login_osim(): void
    {
        $this->get('/admin/ringkasan')->assertRedirect('/login');
    }

    public function test_siswa_tidak_boleh_masuk_ruang_osim(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/ringkasan')->assertForbidden();
    }

    public function test_pengurus_melihat_ringkasan_organisasi(): void
    {
        foreach ([Peran::Pengurus, Peran::Panitia, Peran::Admin] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]));

            $this->get('/admin/ringkasan')
                ->assertOk()
                ->assertSee('Ringkasan Organisasi')
                ->assertSee('Ruang OSIM')
                ->assertSeeInOrder(['Anggota pengurus', 'Divisi', 'Program berjalan', 'Kegiatan terjadwal'])
                ->assertSee('Pengurus inti')
                ->assertSee('Divisi Keagamaan');
        }
    }

    public function test_kelola_divisi_crud(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));

        $this->get('/admin/divisions')->assertOk()->assertSee('Divisi');

        \Livewire\Livewire::test(\App\Filament\Resources\Divisions\Pages\CreateDivision::class)
            ->fillForm(['nama' => 'Divisi Keagamaan', 'deskripsi' => 'Kajian dan peringatan hari besar Islam.'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('divisions', ['nama' => 'Divisi Keagamaan']);

        \Livewire\Livewire::test(\App\Filament\Resources\Divisions\Pages\CreateDivision::class)
            ->fillForm(['nama' => 'Divisi Keagamaan'])
            ->call('create')
            ->assertHasFormErrors(['nama' => 'unique']);
    }

    public function test_daftar_anggota_bisa_dicari_dan_difilter(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        $this->get('/admin/anggota')->assertOk()->assertSee('Anggota Pengurus')->assertSee('Tambah anggota');

        \Livewire\Livewire::test(\App\Filament\Pages\Anggota::class)
            ->assertSee('Ahmad Fauzan')
            ->searchTable('Hafidz')
            ->assertSee('Hafidz Ramadhan')
            ->assertDontSee('Ahmad Fauzan')
            ->searchTable('')
            ->filterTable('divisi', 'Divisi Humas')
            ->assertSee('Nabila Azzahra')
            ->assertDontSee('Hafidz Ramadhan');
    }

    public function test_pencarian_anggota_menurut_divisi_dan_pencarian_divisi(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        \App\Models\Division::create(['nama' => 'Divisi Keagamaan', 'deskripsi' => 'Kajian dan peringatan hari besar Islam.']);
        \App\Models\Division::create(['nama' => 'Divisi Olahraga', 'deskripsi' => 'Kompetisi antarkelas.']);

        \Livewire\Livewire::test(\App\Filament\Pages\Anggota::class)
            ->searchTable('humas')
            ->assertSee('Nabila Azzahra')
            ->assertDontSee('Ahmad Fauzan');

        \Livewire\Livewire::test(\App\Filament\Resources\Divisions\Pages\ListDivisions::class)
            ->searchTable('hari besar')
            ->assertCanSeeTableRecords(\App\Models\Division::where('nama', 'Divisi Keagamaan')->get())
            ->assertCanNotSeeTableRecords(\App\Models\Division::where('nama', 'Divisi Olahraga')->get());

        $hasil = \Filament\Facades\Filament::getGlobalSearchProvider()->getResults('kajian');
        $this->assertSame(['Divisi Keagamaan'], $hasil->getCategories()->flatten()->map->title->all());
    }

    public function test_kelola_program_kerja_dan_ubah_status_dari_tabel(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $divisi = \App\Models\Division::create(['nama' => 'Divisi Lingkungan']);

        $this->get('/admin/work-programs')->assertOk()->assertSee('Program Kerja');

        \Livewire\Livewire::test(\App\Filament\Resources\WorkPrograms\Pages\CreateWorkProgram::class)
            ->fillForm(['nama' => 'Bank Sampah', 'divisi_id' => $divisi->id, 'status' => 'rencana', 'unggulan' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $program = \App\Models\WorkProgram::firstOrFail();
        $this->assertSame(\App\Enums\StatusProgramKerja::Rencana, $program->status);

        \Livewire\Livewire::test(\App\Filament\Resources\WorkPrograms\Pages\ListWorkPrograms::class)
            ->call('updateTableColumnState', 'status', (string) $program->id, 'jalan');

        $this->assertSame(\App\Enums\StatusProgramKerja::Jalan, $program->fresh()->status);

        // Program unggulan yang dikelola di sini muncul di sorotan Beranda.
        $this->get('/')->assertSee('Bank Sampah')->assertSee('Sedang berjalan');
    }

    public function test_jadwal_kegiatan_memperingatkan_bentrok_tempat(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $aula = \App\Models\Event::create(['nama' => 'Pentas Seni', 'tanggal_mulai' => '2026-11-14', 'tanggal_selesai' => '2026-11-15', 'tempat' => 'Aula Utama']);

        $this->get('/admin/events')->assertOk()->assertSee('Jadwal Kegiatan');

        // Tanggal beririsan di tempat sama (beda huruf besar/spasi) -> peringatan muncul.
        \Livewire\Livewire::test(\App\Filament\Resources\Events\Pages\CreateEvent::class)
            ->fillForm(['nama' => 'Lomba Pidato', 'tanggal_mulai' => '2026-11-15', 'tanggal_selesai' => '2026-11-15', 'tempat' => ' aula utama '])
            ->assertSee('Bentrok dengan 1 kegiatan di tempat yang sama')
            ->assertSee('Pentas Seni')
            ->fillForm(['tempat' => 'Lapangan'])
            ->assertDontSee('Bentrok dengan')
            ->call('create')
            ->assertHasNoFormErrors();

        // Tanggal selesai sebelum mulai ditolak.
        \Livewire\Livewire::test(\App\Filament\Resources\Events\Pages\CreateEvent::class)
            ->fillForm(['nama' => 'Salah Tanggal', 'tanggal_mulai' => '2026-11-20', 'tanggal_selesai' => '2026-11-19', 'tempat' => 'Kelas'])
            ->call('create')
            ->assertHasFormErrors(['tanggal_selesai']);

        $this->assertCount(0, \App\Models\Event::bentrok('2026-11-16', '2026-11-20', 'Aula Utama'));
        $this->assertCount(1, \App\Models\Event::bentrok('2026-11-10', '2026-11-14', 'Aula Utama'));
        $this->assertCount(0, \App\Models\Event::bentrok('2026-11-14', '2026-11-14', 'Aula Utama', $aula->id));
    }

    public function test_data_pemilih_hanya_untuk_panitia_dan_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/data-pemilih')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        \App\Models\Voter::factory()->berkode('123456')->create(['nama' => 'Siti Kusuma', 'nomor_induk' => '0091234037']);
        $this->get('/admin/data-pemilih')->assertOk()->assertSee('Data Pemilih')->assertSee('1 pemilih terdaftar');

        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->searchTable('0091234037')
            ->assertSee('Siti Kusuma')
            ->assertSee('123456');
    }

    public function test_impor_siswa_dan_gtk_dari_csv(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));
        $impor = app(\App\Services\ImporPemilih::class);

        $csv = tempnam(sys_get_temp_dir(), 'siswa').'.csv';
        file_put_contents($csv, "NISN,Nama,Kelas,Tanggal Lahir\n91234567,Ahmad Fauzi,VII-A,2013-05-21\n0091234568, Siti  Aminah ,VIII-B,2/11/2012\n,Tanpa NISN,VII-A,2013-01-01\n0091234569,Tanggal Salah,VII-A,kemarin\n");
        $hasil = $impor->impor($csv, \App\Enums\JenisPemilih::Siswa);

        $this->assertSame(2, $hasil['tambah']);
        $this->assertCount(2, $hasil['gagal']);
        $this->assertDatabaseHas('voters', ['nomor_induk' => '0091234567', 'nama' => 'Ahmad Fauzi', 'kelas' => 'VII-A']);
        $this->assertSame('2012-11-02', \App\Models\Voter::where('nomor_induk', '0091234568')->first()->tanggal_lahir->toDateString());

        // Impor ulang memperbarui, tidak menggandakan.
        file_put_contents($csv, "nisn,nama,kelas,tanggal_lahir\n0091234567,Ahmad Fauzi,VII-B,2013-05-21\n");
        $this->assertSame(1, $impor->impor($csv, \App\Enums\JenisPemilih::Siswa)['perbarui']);
        $this->assertSame('VII-B', \App\Models\Voter::where('nomor_induk', '0091234567')->value('kelas'));

        $gtk = tempnam(sys_get_temp_dir(), 'gtk').'.csv';
        file_put_contents($gtk, "nik,nama,jabatan,tanggal_lahir\n3573010101800001,Pak Guru,Guru IPA,1980-01-01\n12345,NIK Pendek,Guru,1980-01-01\n");
        $hasil = $impor->impor($gtk, \App\Enums\JenisPemilih::Gtk);
        $this->assertSame(1, $hasil['tambah']);
        $this->assertDatabaseHas('voters', ['jenis' => 'gtk', 'nomor_induk' => '3573010101800001', 'jabatan' => 'Guru IPA']);

        $salah = tempnam(sys_get_temp_dir(), 'salah').'.csv';
        file_put_contents($salah, "nama,kelas\nTanpa Kolom,VII-A\n");
        $this->assertStringContainsString('Kolom wajib tidak ada', $impor->impor($salah, \App\Enums\JenisPemilih::Siswa)['gagal'][0]);
    }

    public function test_buat_kode_dan_cetak_kartu_pemilih(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $siswa = \App\Models\Voter::factory()->count(3)->create(['kelas' => 'VII-A']);
        \App\Models\Voter::factory()->create(['verifikasi' => 'menunggu']);

        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->callAction('buatKode', data: ['ulang' => false]);

        $this->assertSame(3, \App\Models\Voter::whereNotNull('kode_pilih')->count());
        $kode = $siswa->first()->fresh()->kode_pilih;

        $this->get(route('kartu-pemilih', ['kelompok' => 'VII-A']))
            ->assertOk()
            ->assertSee('Kartu Pemilih')
            ->assertSee($kode)
            ->assertSee('3 kartu');

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get(route('kartu-pemilih'))->assertForbidden();
    }
}
