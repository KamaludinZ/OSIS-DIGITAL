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
