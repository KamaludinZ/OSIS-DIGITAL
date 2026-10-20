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
        // Kosong: tetap tampil dengan petunjuk.
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/ringkasan')->assertOk()->assertSee('Pengurus inti periode ini belum diisi');

        $this->seed(\Database\Seeders\OrganisasiSeeder::class);
        $ketua = \App\Models\Member::periode()->where('jabatan', 'Ketua OSIM')->first();

        foreach ([Peran::Pengurus, Peran::Panitia, Peran::Admin] as $peran) {
            $this->actingAs(User::factory()->create(['role' => $peran]));

            $this->get('/admin/ringkasan')
                ->assertOk()
                ->assertSee('Ringkasan Organisasi')
                ->assertSee('Ruang OSIM')
                ->assertSeeInOrder(['Anggota pengurus', 'Divisi', 'Program berjalan', 'Kegiatan terjadwal'])
                ->assertSee('Pengurus inti')
                ->assertSee('Divisi Keagamaan')
                ->assertSee($ketua->nama)
                ->assertSee('periode '.\App\Models\Member::periodeAktif());
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

    public function test_pencarian_anggota_menurut_divisi_dan_pencarian_divisi(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        \App\Models\Division::create(['nama' => 'Divisi Keagamaan', 'deskripsi' => 'Kajian dan peringatan hari besar Islam.']);
        \App\Models\Division::create(['nama' => 'Divisi Olahraga', 'deskripsi' => 'Kompetisi antarkelas.']);

        $this->seed(\Database\Seeders\OrganisasiSeeder::class);
        \Livewire\Livewire::test(\App\Filament\Resources\Members\Pages\ListMembers::class)
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
            ->call('create')
            ->assertHasFormErrors(['abaikan_bentrok'])
            ->fillForm(['tempat' => 'Lapangan'])
            ->assertDontSee('Bentrok dengan')
            ->call('create')
            ->assertHasNoFormErrors();

        // Bentrok yang disengaja boleh disimpan bila dicentang.
        \Livewire\Livewire::test(\App\Filament\Resources\Events\Pages\CreateEvent::class)
            ->fillForm(['nama' => 'Bazar Gabungan', 'tanggal_mulai' => '2026-11-14', 'tanggal_selesai' => '2026-11-14', 'tempat' => 'Aula Utama'])
            ->fillForm(['abaikan_bentrok' => true])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('events', ['nama' => 'Bazar Gabungan']);

        // Tanggal selesai sebelum mulai ditolak.
        \Livewire\Livewire::test(\App\Filament\Resources\Events\Pages\CreateEvent::class)
            ->fillForm(['nama' => 'Salah Tanggal', 'tanggal_mulai' => '2026-11-20', 'tanggal_selesai' => '2026-11-19', 'tempat' => 'Kelas'])
            ->call('create')
            ->assertHasFormErrors(['tanggal_selesai']);

        $this->assertCount(0, \App\Models\Event::bentrok('2026-11-16', '2026-11-20', 'Aula Utama'));
        $this->assertCount(2, \App\Models\Event::bentrok('2026-11-10', '2026-11-14', 'Aula Utama')); // Pentas Seni + Bazar Gabungan
        $this->assertSame(['Bazar Gabungan'], \App\Models\Event::bentrok('2026-11-14', '2026-11-14', 'Aula Utama', $aula->id)->pluck('nama')->all());
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

    public function test_empty_state_data_pemilih(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));

        $this->get('/admin/data-pemilih')
            ->assertOk()
            ->assertSee('Belum ada data pemilih')
            ->assertSee('Templat siswa (CSV)')
            ->assertSee('/templat/pemilih-gtk.csv');

        \App\Models\Voter::factory()->create(['nama' => 'Ada Orang']);
        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->searchTable('zzz-tidak-ada')
            ->assertSee('Tidak ada pemilih yang cocok')
            ->assertSee('Coba kata kunci lain');
    }

    public function test_verifikasi_massal_dan_buat_ulang_kode(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $menunggu = \App\Models\Voter::factory()->count(3)->create(['verifikasi' => 'menunggu']);
        $lain = \App\Models\Voter::factory()->create(['verifikasi' => 'menunggu']);

        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->callTableBulkAction('verifikasi', $menunggu, data: ['verifikasi' => 'terverifikasi']);

        $this->assertSame(3, \App\Models\Voter::where('verifikasi', 'terverifikasi')->count());
        $this->assertSame('menunggu', $lain->fresh()->verifikasi);

        $berkode = \App\Models\Voter::factory()->berkode('555555')->create();
        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->callTableBulkAction('kodeUlang', [$berkode]);
        $this->assertNotSame('555555', $berkode->fresh()->kode_pilih);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $berkode->fresh()->kode_pilih);
    }

    public function test_pantau_partisipasi_per_kelas_dan_gtk(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $e = \App\Models\Election::first();
        $calon = \App\Models\Candidate::first();
        $a = \App\Models\Voter::factory()->count(4)->create(['kelas' => 'VII-A']);
        \App\Models\Voter::factory()->count(2)->create(['kelas' => 'IX-B']);
        $g = \App\Models\Voter::factory()->gtk()->create();
        \App\Models\Voter::factory()->create(['kelas' => 'VII-A', 'verifikasi' => 'ditolak']);
        foreach ([$a[0], $a[1], $a[2], $g] as $v) {
            app(\App\Services\LayananPemilihan::class)->kirimSuara($v, $e, $calon->id);
        }

        $r = app(\App\Services\LayananPartisipasi::class)->ringkasan($e);
        $this->assertSame(7, $r['total']);
        $this->assertSame(4, $r['memilih']);
        $this->assertSame(['VII-A', 'IX-B', 'GTK'], array_column($r['kelompok'], 'nama'));
        $this->assertSame([3, 0, 1], array_column($r['kelompok'], 'memilih'));

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/partisipasi')->assertOk()->assertSee('Pantau Partisipasi')->assertSee('57,14%')->assertSee('75,00%')->assertDontSee('Nadia Kusuma');

        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->get('/admin/partisipasi')->assertForbidden();
    }

    public function test_daftar_belum_memilih_per_kelompok(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $e = \App\Models\Election::first();
        $sudah = \App\Models\Voter::factory()->create(['nama' => 'Sudah Memilih', 'kelas' => 'VIII-A']);
        \App\Models\Voter::factory()->create(['nama' => 'Belum Satu', 'kelas' => 'VIII-A']);
        \App\Models\Voter::factory()->gtk()->create(['nama' => 'Guru Belum']);
        \App\Models\Voter::factory()->create(['nama' => 'Ditolak Tidak Dihitung', 'kelas' => 'VIII-A', 'verifikasi' => 'ditolak']);
        app(\App\Services\LayananPemilihan::class)->kirimSuara($sudah, $e, \App\Models\Candidate::first()->id);

        $belum = app(\App\Services\LayananPartisipasi::class)->belumMemilih($e);
        $this->assertSame(['VIII-A' => ['Belum Satu'], 'GTK' => ['Guru Belum']], $belum);

        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));
        $this->get('/admin/partisipasi')->assertSee('Belum Satu')->assertSee('Guru Belum')->assertDontSee('Sudah Memilih')->assertSee('1 belum');
    }

    public function test_angka_partisipasi_diperbarui_otomatis(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $pemilih = \App\Models\Voter::factory()->create(['kelas' => 'VII-A']);

        $this->get('/admin/partisipasi')->assertSee('wire:poll.15s.visible', false);
        $this->get('/admin/data-pemilih')->assertSee('wire:poll.30s', false);

        $halaman = \Livewire\Livewire::test(\App\Filament\Pages\Partisipasi::class)->assertSee('0 / 1 orang');
        app(\App\Services\LayananPemilihan::class)->kirimSuara($pemilih, \App\Models\Election::first(), \App\Models\Candidate::first()->id);
        $halaman->call('$refresh')->assertSee('1 / 1 orang')->assertSee('100%');
    }

    public function test_pratinjau_impor_tanpa_menyimpan(): void
    {
        \App\Models\Voter::factory()->create(['nomor_induk' => '0091234567']);
        $csv = tempnam(sys_get_temp_dir(), 'pra').'.csv';
        file_put_contents($csv, "nisn,nama,kelas,tanggal_lahir\n0091234567,Sudah Ada,VII-A,2013-05-21\n0091234999,Siswa Baru,VII-B,21/05/2013\nabc,Salah,VII-B,2013-01-01\n");

        $p = app(\App\Services\ImporPemilih::class)->pratinjau($csv, \App\Enums\JenisPemilih::Siswa);

        $this->assertSame(3, $p['total']);
        $this->assertSame(1, $p['baru']);
        $this->assertSame(1, $p['perbarui']);
        $this->assertCount(1, $p['gagal']);
        $this->assertSame(1, \App\Models\Voter::count()); // tidak ada yang tersimpan

        $html = view('filament.pratinjau-impor', ['p' => $p, 'jenis' => 'siswa'])->render();
        $this->assertStringContainsString('Siswa Baru', $html);
        $this->assertStringContainsString('1 baris akan dilewati', $html);

        // Unggah lewat modal Filament: pratinjau tampil, lalu impor tersimpan.
        $this->actingAs(User::factory()->create(['role' => Peran::Admin]));
        $berkas = \Illuminate\Http\UploadedFile::fake()->createWithContent('siswa.csv', file_get_contents($csv));
        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->mountAction('imporSiswa')
            ->fillForm(['berkas' => $berkas])
            ->callMountedAction()
            ->assertNotified('Impor selesai: 1 ditambahkan, 1 diperbarui');
        $this->assertSame(2, \App\Models\Voter::count());

        // Panel ringkasan impor terakhir menetap di halaman Data Pemilih sampai ditutup.
        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->assertSee('Impor terakhir · Siswa')
            ->assertSeeInOrder(['Ditambahkan', '1', 'Diperbarui', '1', 'Tidak berubah', '0', 'Dilewati', '1'])
            ->assertSee('Baris yang dilewati (1)')
            ->call('tutupRingkasanImpor')
            ->assertDontSee('Impor terakhir');

        // Tombol "Buat kode pilih" pada notifikasi membuat kode untuk pemilih baru.
        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->dispatch('buat-kode-pemilih-baru')
            ->assertNotified('2 kode pilih dibuat');
        $this->assertSame(0, \App\Models\Voter::whereNull('kode_pilih')->count());
    }

    public function test_crud_anggota_pengurus(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $divisi = \App\Models\Division::create(['nama' => 'Divisi Keagamaan']);
        $siswa = \App\Models\Voter::factory()->create(['nama' => 'Hafidz Ramadhan', 'kelas' => 'VIII-A']);

        $this->get('/admin/anggota')->assertOk()->assertSee('Anggota');

        \Livewire\Livewire::test(\App\Filament\Resources\Members\Pages\CreateMember::class)
            ->assertSchemaStateSet(['periode' => \App\Models\Member::periodeAktif(), 'jabatan' => 'Anggota'])
            ->fillForm(['voter_id' => $siswa->id])
            ->assertSchemaStateSet(['nama' => 'Hafidz Ramadhan', 'kelas' => 'VIII-A'])
            ->fillForm(['divisi_id' => $divisi->id, 'jabatan' => 'Koordinator'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('members', ['nama' => 'Hafidz Ramadhan', 'divisi_id' => $divisi->id, 'jabatan' => 'Koordinator', 'voter_id' => $siswa->id]);

        \Livewire\Livewire::test(\App\Filament\Resources\Members\Pages\CreateMember::class)
            ->fillForm(['nama' => 'Ketua Baru', 'jabatan' => 'Ketua OSIM', 'periode' => '2026'])
            ->call('create')
            ->assertHasFormErrors(['periode']);

        // Panitia tidak mengelola organisasi.
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/anggota')->assertForbidden();
        $this->get('/admin/divisions')->assertForbidden();
    }

    public function test_pencarian_global_anggota_dan_divisi(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $this->seed(\Database\Seeders\OrganisasiSeeder::class);
        \App\Models\Member::factory()->create(['nama' => 'Hafidz Periode Lalu', 'periode' => '2020/2021']);

        $hasil = \Filament\Facades\Filament::getGlobalSearchProvider()->getResults('hafidz');
        $judul = $hasil->getCategories()->flatten()->map->title->all();

        $this->assertContains('Hafidz Ramadhan', $judul);
        $this->assertNotContains('Hafidz Periode Lalu', $judul);

        $koordinator = \Filament\Facades\Filament::getGlobalSearchProvider()->getResults('koordinator')->getCategories()->flatten();
        $this->assertCount(8, $koordinator);
    }

    public function test_batas_program_unggulan(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Pengurus]));
        $divisi = \App\Models\Division::create(['nama' => 'Divisi Uji']);
        \App\Models\WorkProgram::factory()->count(6)->create(['unggulan' => true, 'divisi_id' => $divisi->id]);
        $biasa = \App\Models\WorkProgram::factory()->create(['unggulan' => false, 'divisi_id' => $divisi->id]);

        $this->assertSame(0, \App\Models\WorkProgram::kuotaUnggulanTersisa());

        \Livewire\Livewire::test(\App\Filament\Resources\WorkPrograms\Pages\CreateWorkProgram::class)
            ->fillForm(['nama' => 'Ketujuh', 'divisi_id' => $divisi->id, 'status' => 'rencana', 'unggulan' => true])
            ->call('create')
            ->assertHasFormErrors(['unggulan']);

        // Program yang sudah unggulan tetap bisa disunting tanpa ditolak.
        $sudah = \App\Models\WorkProgram::where('unggulan', true)->first();
        \Livewire\Livewire::test(\App\Filament\Resources\WorkPrograms\Pages\EditWorkProgram::class, ['record' => $sudah->id])
            ->fillForm(['capaian' => '10 kegiatan'])
            ->call('save')
            ->assertHasNoFormErrors();

        \Livewire\Livewire::test(\App\Filament\Resources\WorkPrograms\Pages\ListWorkPrograms::class)
            ->assertTableColumnStateSet('unggulan', false, $biasa)
            ->assertSee('Kuota 6 sorotan penuh');
    }

    public function test_otorisasi_pengurus_pada_data_organisasi(): void
    {
        $divisi = \App\Models\Division::create(['nama' => 'Divisi Uji']);
        $program = \App\Models\WorkProgram::factory()->create(['divisi_id' => $divisi->id]);

        $panitia = User::factory()->create(['role' => Peran::Panitia]);
        $this->actingAs($panitia);

        foreach ([\App\Models\Division::class, \App\Models\Member::class, \App\Models\WorkProgram::class, \App\Models\Event::class] as $model) {
            $this->assertFalse($panitia->can('create', $model), $model);
        }
        $this->assertFalse($panitia->can('update', $program));
        $this->assertFalse($panitia->can('delete', $divisi));

        \Livewire\Livewire::test(\App\Filament\Resources\Divisions\Pages\CreateDivision::class)->assertForbidden();
        \Livewire\Livewire::test(\App\Filament\Resources\WorkPrograms\Pages\EditWorkProgram::class, ['record' => $program->id])->assertForbidden();
        $this->get('/admin/work-programs')->assertForbidden();
        $this->get('/admin/events/create')->assertForbidden();

        $pengurus = User::factory()->create(['role' => Peran::Pengurus]);
        $this->assertTrue($pengurus->can('update', $program));
        $this->assertTrue(User::factory()->create(['role' => Peran::Admin])->can('delete', $divisi));
    }

    public function test_daftar_pemilih_dengan_filter(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $e = \App\Models\Election::first();
        $budi = \App\Models\Voter::factory()->berkode('111111')->create(['nama' => 'Budi Tujuh', 'kelas' => 'VII-A']);
        $sari = \App\Models\Voter::factory()->create(['nama' => 'Sari Sembilan', 'kelas' => 'IX-C', 'verifikasi' => 'menunggu']);
        $guru = \App\Models\Voter::factory()->gtk()->berkode('222222')->create(['nama' => 'Pak Guru']);
        app(\App\Services\LayananPemilihan::class)->kirimSuara($budi, $e, \App\Models\Candidate::first()->id);

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $daftar = fn () => \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class);

        $daftar()->filterTable('jenis', 'gtk')->assertCanSeeTableRecords([$guru])->assertCanNotSeeTableRecords([$budi, $sari]);
        $daftar()->filterTable('tingkat', 'IX')->assertCanSeeTableRecords([$sari])->assertCanNotSeeTableRecords([$budi, $guru]);
        $daftar()->filterTable('verifikasi', 'menunggu')->assertCanSeeTableRecords([$sari])->assertCanNotSeeTableRecords([$budi]);
        $daftar()->filterTable('sudah_memilih', true)->assertCanSeeTableRecords([$budi])->assertCanNotSeeTableRecords([$sari, $guru]);
        $daftar()->filterTable('sudah_memilih', false)->assertCanSeeTableRecords([$sari, $guru])->assertCanNotSeeTableRecords([$budi]);
        $daftar()->filterTable('kode', false)->assertCanSeeTableRecords([$sari])->assertCanNotSeeTableRecords([$budi, $guru]);
        $daftar()->searchTable('IX-C')->assertCanSeeTableRecords([$sari])->assertCanNotSeeTableRecords([$budi]);

        $pengurus = User::factory()->create(['role' => Peran::Pengurus]);
        $this->assertFalse($pengurus->can('viewAny', \App\Models\Voter::class));
        $this->assertFalse($pengurus->can('update', $budi));
    }

    public function test_verifikasi_pemilih_satuan(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $v = \App\Models\Voter::factory()->berkode('777777')->create(['verifikasi' => 'menunggu']);
        $daftar = fn () => \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class);

        $daftar()->assertTableActionVisible('verifikasiSatu', $v)->callTableAction('verifikasiSatu', $v);
        $this->assertSame('terverifikasi', $v->fresh()->verifikasi);
        $daftar()->assertTableActionHidden('verifikasiSatu', $v);

        $daftar()->callTableAction('tolak', $v);
        $this->assertSame('ditolak', $v->fresh()->verifikasi);
        $this->assertSame('bukan_pemilih', app(\App\Services\LayananPemilihan::class)->kelayakan($v->fresh())['alasan'] ?? null);
    }

    public function test_statistik_partisipasi_pemilih(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);
        $e = \App\Models\Election::first();
        $berkode = \App\Models\Voter::factory()->count(3)->sequence(fn ($s) => ['kode_pilih' => '30000'.$s->index])->create();
        \App\Models\Voter::factory()->create(['verifikasi' => 'menunggu']);
        \App\Models\Voter::factory()->create(['verifikasi' => 'ditolak', 'kode_pilih' => '399999']);
        app(\App\Services\LayananPemilihan::class)->kirimSuara($berkode[0], $e, \App\Models\Candidate::first()->id);

        $s = app(\App\Services\LayananPartisipasi::class)->statistik($e);
        $this->assertSame(['total' => 5, 'terverifikasi' => 3, 'menunggu' => 1, 'ditolak' => 1, 'berkode' => 3, 'memilih' => 1, 'persen' => 33.3], $s);

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/data-pemilih')->assertSee('5 pemilih terdaftar')->assertSee('3 terverifikasi (1 menunggu)')->assertSee('1 sudah memilih');
    }

    public function test_impor_menolak_data_ganda_dalam_berkas_dan_lewati_yang_sama(): void
    {
        $impor = app(\App\Services\ImporPemilih::class);
        $csv = tempnam(sys_get_temp_dir(), 'ganda').'.csv';
        // 91234567 dan 0091234567 adalah NISN yang sama setelah nol di depan dipulihkan.
        file_put_contents($csv, "nisn,nama,kelas,tanggal_lahir\n0091234567,Ahmad Fauzi,VII-A,2013-05-21\n91234567,Ahmad F.,VII-B,2013-05-21\n0091234570,Rina,VII-A,2013-02-02\n");

        $p = $impor->pratinjau($csv, \App\Enums\JenisPemilih::Siswa);
        $this->assertSame([2, 2], [$p['valid'], $p['baru']]);
        $this->assertSame(['Baris 3: NISN 0091234567 ganda dengan baris 2. Hapus salah satu.'], $p['gagal']);

        $hasil = $impor->impor($csv, \App\Enums\JenisPemilih::Siswa);
        $this->assertSame([2, 0, 0], [$hasil['tambah'], $hasil['perbarui'], $hasil['sama']]);
        $this->assertSame('VII-A', \App\Models\Voter::where('nomor_induk', '0091234567')->value('kelas'));

        $hasil = $impor->impor($csv, \App\Enums\JenisPemilih::Siswa);
        $this->assertSame([0, 0, 2], [$hasil['tambah'], $hasil['perbarui'], $hasil['sama']]);
        $this->assertSame(2, \App\Models\Voter::count());
    }

    public function test_pesan_galat_impor_berbahasa_awam(): void
    {
        $impor = app(\App\Services\ImporPemilih::class);
        $csv = tempnam(sys_get_temp_dir(), 'awam').'.csv';
        file_put_contents($csv, "nisn,nama,kelas,tanggal_lahir\nabc,Anak A,VII-A,2013-05-21\n0091111111,,VII-A,2013-05-21\n0092222222,Anak C,VII-A,kemarin\n");

        $gagal = $impor->pratinjau($csv, \App\Enums\JenisPemilih::Siswa)['gagal'];
        $this->assertStringContainsString('ubah format sel NISN menjadi Teks', $gagal[0]);
        $this->assertSame('Baris 3: Nama belum diisi.', $gagal[1]);
        $this->assertStringContainsString('Tulis seperti 2013-05-21', $gagal[2]);

        $rusak = tempnam(sys_get_temp_dir(), 'rusak').'.xlsx';
        file_put_contents($rusak, 'bukan excel');
        $this->assertStringContainsString('Berkas tidak bisa dibaca', $impor->pratinjau($rusak, \App\Enums\JenisPemilih::Siswa, 'data.xlsx')['gagal'][0]);
    }

    public function test_panduan_kolom_dan_unduh_templat_impor(): void
    {
        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));

        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->mountAction('imporSiswa')
            ->assertMountedActionModalSee(['nisn', 'tanggal_lahir', 'Format sel sebagai Teks', 'Unduh templat CSV siswa'])
            ->assertMountedActionModalSeeHtml('href="/templat/pemilih-siswa.csv" download');

        \Livewire\Livewire::test(\App\Filament\Resources\Voters\Pages\ListVoters::class)
            ->mountAction('imporGtk')
            ->assertMountedActionModalSee(['nik', 'Tepat 16 angka', 'Unduh templat CSV GTK']);

        $this->assertFileExists(public_path('templat/pemilih-siswa.csv'));
        $this->assertStringStartsWith('nisn,nama,kelas,tanggal_lahir', file_get_contents(public_path('templat/pemilih-siswa.csv')));
    }

    public function test_partisipasi_segarkan_dan_dijaga_dari_pengurus(): void
    {
        $this->seed(\Database\Seeders\PemilihanSeeder::class);

        $this->actingAs(User::factory()->pengurusMenjabat()->create());
        $this->get('/admin/partisipasi')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Peran::Panitia]));
        $this->get('/admin/partisipasi')->assertOk()->assertSee('wire:poll.15s.visible', false);
        \Livewire\Livewire::test(\App\Filament\Pages\Partisipasi::class)
            ->assertActionVisible('segarkan')
            ->callAction('segarkan')
            ->assertOk();
    }
}
