<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'beranda')->name('home');

Volt::route('cari', 'cari')->name('cari');
Volt::route('kelola/sosmed', 'kelola.sosmed')->middleware('auth')->name('kelola.sosmed');
Route::get('sosmed/{post}/buka', App\Http\Controllers\BukaSosmedController::class)->whereNumber('post')->middleware('throttle:60,1')->name('sosmed.buka');
Volt::route('hasil', 'hasil')->name('hasil');
Volt::route('pengumuman/{id}', 'pengumuman.detail')->whereNumber('id')->name('pengumuman.detail');
Volt::route('kegiatan/{id}', 'kegiatan.detail')->whereNumber('id')->name('kegiatan.detail');
Volt::route('bilik-suara', 'bilik-suara')->name('bilik-suara');
Volt::route('bilik-suara/bukti', 'bilik-suara.bukti')->middleware('auth')->name('bilik-suara.bukti');
Volt::route('kandidat', 'kandidat.index')->name('kandidat.index');
Volt::route('kandidat/{id}', 'kandidat.profil')->whereNumber('id')->name('kandidat.profil');

// Dashboard internal (Filament) belum dibangun; arahkan ke Beranda.
Route::redirect('dashboard', '/')
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
