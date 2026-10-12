<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Pemberitahuan pengumuman terjadwal yang baru tayang.
Artisan::command('osim:kirim-pengumuman-terjadwal', function () {
    $jumlah = app(App\Services\LayananNotifikasi::class)->kirimPengumumanTerjadwal();
    $this->info("{$jumlah} pemberitahuan pengumuman terkirim.");
})->purpose('Beri tahu akun saat pengumuman terjadwal mulai tayang')->everyMinute()->withoutOverlapping();

// Pengingat pemilihan untuk akun staf & kandidat (H-1, saat dibuka, 3 jam sebelum ditutup).
Artisan::command('osim:kirim-pengingat-pemilihan', function () {
    $kunci = app(App\Services\LayananNotifikasi::class)->kirimPengingatPemilihan();
    $this->info(count($kunci).' pengingat pemilihan terkirim.');
})->purpose('Kirim pengingat pemilihan ke akun')->everyFiveMinutes()->withoutOverlapping();

// Akun admin pertama di server produksi (tidak ada pendaftaran mandiri; seeder contoh hanya untuk lokal).
Artisan::command('osim:buat-admin {email} {--nama=Admin Madrasah}', function (string $email) {
    $email = strtolower(trim($email));

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $this->error('Email tidak valid.') ?? 1;
    }

    $sandi = $this->secret('Kata sandi (minimal 12 karakter, kosongkan untuk dibuatkan otomatis)') ?: Illuminate\Support\Str::password(16, symbols: false);

    if (strlen($sandi) < 12) {
        return $this->error('Kata sandi minimal 12 karakter.') ?? 1;
    }

    $akun = App\Models\User::updateOrCreate(['email' => $email], [
        'name' => $this->option('nama'),
        'password' => $sandi,
        'role' => App\Enums\Peran::Admin,
        'aktif' => true,
        'email_verified_at' => now(),
    ]);

    $this->info("Akun admin siap: {$akun->email}");
    $this->line("Kata sandi: {$sandi}");
    $this->warn('Simpan kata sandi ini sekarang; tidak akan ditampilkan lagi. Ganti setelah masuk (Pengaturan akun).');
})->purpose('Buat atau setel ulang akun admin (untuk server produksi)');
