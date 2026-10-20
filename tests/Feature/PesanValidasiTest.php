<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PesanValidasiTest extends TestCase
{
    public function test_aturan_validasi_yang_dipakai_aplikasi_berbahasa_indonesia(): void
    {
        app()->setLocale('id');

        // Kumpulkan nama aturan string yang dipakai di kode aplikasi (mis. 'required', 'min:10', ->after(...)).
        $kode = collect(File::allFiles(app_path()))->merge(File::allFiles(resource_path('views/livewire')))
            ->map(fn ($f) => File::get($f->getPathname()))->implode("\n");
        preg_match_all("/'(required|string|min|max|email|url|image|mimes|mimetypes|in|unique|confirmed|boolean|date|after|regex|integer|numeric|digits|exists|enum|size|between|file|array)(?::[^']*)?'/", $kode, $cocok);

        foreach (array_unique($cocok[1]) as $aturan) {
            $pesan = trans('validation.'.$aturan);
            $teks = is_array($pesan) ? implode(' ', $pesan) : $pesan;

            $this->assertNotSame('validation.'.$aturan, $teks, "Aturan {$aturan} belum punya pesan.");
            $this->assertDoesNotMatchRegularExpression('/\b(must|field|should|valid email)\b/i', $teks, "Pesan {$aturan} masih berbahasa Inggris.");
        }
    }

    public function test_contoh_pesan_ramah(): void
    {
        app()->setLocale('id');

        $galat = validator(
            ['nisn' => '12', 'tanggal_lahir' => 'kemarin', 'nama_akun' => ''],
            ['nisn' => 'digits:10', 'tanggal_lahir' => 'date', 'nama_akun' => 'required'],
        )->errors();

        $this->assertSame('NISN harus 10 angka.', $galat->first('nisn'));
        $this->assertSame('Tanggal lahir harus berupa tanggal yang benar.', $galat->first('tanggal_lahir'));
        $this->assertSame('Nama akun wajib diisi.', $galat->first('nama_akun'));
    }
}
