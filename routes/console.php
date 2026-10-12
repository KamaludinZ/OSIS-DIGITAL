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
