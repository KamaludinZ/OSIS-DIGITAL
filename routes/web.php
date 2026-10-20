<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'beranda')->name('home');

Volt::route('cari', 'cari')->name('cari');
Volt::route('struktur', 'struktur')->name('struktur');
Volt::route('saran', 'saran')->name('saran');
Volt::route('kelola/sosmed', 'kelola.sosmed')->middleware(['auth', 'can:kelola-publikasi'])->name('kelola.sosmed');
Route::get('sosmed/{post}/buka', App\Http\Controllers\BukaSosmedController::class)->whereNumber('post')->middleware(['throttle:60,1', 'periode:'.App\Models\SocialPost::class.',post,arsip'])->name('sosmed.buka');
Volt::route('hasil', 'hasil')->middleware('mode-pemilihan')->name('hasil');
Volt::route('notifikasi', 'notifikasi')->name('notifikasi');
Route::get('notifikasi/umpan', fn (App\Services\LayananNotifikasi $layanan) => response()->json($layanan->umpan())->header('Cache-Control', 'public, max-age=60'))
    ->middleware('throttle:60,1')->name('notifikasi.umpan');
Route::get('mode-pemilihan', [App\Http\Controllers\ModePemilihanController::class, 'tampil'])
    ->middleware(['auth', 'throttle:60,1'])->name('mode-pemilihan');
Route::patch('mode-pemilihan', [App\Http\Controllers\ModePemilihanController::class, 'ubah'])
    ->middleware(['auth', 'can:ubah-mode-pemilihan', 'throttle:10,1'])->name('mode-pemilihan.ubah');
Route::get('admin-cetak/kartu-pemilih', App\Http\Controllers\KartuPemilihController::class)->middleware(['auth', 'can:kelola-pemilih'])->name('kartu-pemilih');
Route::get('hasil/laporan', [App\Http\Controllers\LaporanHasilController::class, 'cetak'])->middleware('mode-pemilihan')->name('hasil.laporan');
Route::get('hasil/laporan.csv', [App\Http\Controllers\LaporanHasilController::class, 'csv'])->middleware('mode-pemilihan')->name('hasil.laporan.csv');
Volt::route('pengumuman/{id}', 'pengumuman.detail')->whereNumber('id')->name('pengumuman.detail');
Volt::route('kegiatan/{id}', 'kegiatan.detail')->whereNumber('id')->middleware('periode:'.App\Models\Event::class.',id,arsip')->name('kegiatan.detail');
Volt::route('bilik-suara', 'bilik-suara')->middleware('mode-pemilihan')->name('bilik-suara');
Volt::route('bilik-suara/bukti', 'bilik-suara.bukti')->middleware('mode-pemilihan')->name('bilik-suara.bukti');
Volt::route('kandidat', 'kandidat.index')->middleware('mode-pemilihan')->name('kandidat.index');
Volt::route('kandidat/{id}', 'kandidat.profil')->whereNumber('id')->middleware(['mode-pemilihan', 'periode:'.App\Models\Candidate::class])->name('kandidat.profil');

// Alamat lama "dashboard": arahkan ke halaman awal sesuai peran.
Route::get('dashboard', fn () => redirect(auth()->user()->halamanAwal()))
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/notifikasi', 'settings.notifikasi')->name('settings.notifikasi');
    // Tampilan hanya tema terang OSIM; alamat lama diarahkan ke profil.
    Route::redirect('settings/appearance', '/settings/profile')->name('settings.appearance');
});

require __DIR__.'/auth.php';
