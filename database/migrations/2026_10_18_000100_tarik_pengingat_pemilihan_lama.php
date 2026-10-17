<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pengingat pemilihan versi lama tidak menyimpan penanda pemilihan, jadi tidak bisa
 * ditarik saat jadwal diubah dan bisa tertinggal di lonceng dengan jam yang salah.
 * Hapus sekali; penjadwal mengirim ulang yang masih berlaku (dengan penanda).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('notifications')
            ->whereIn('data->title', ['Besok bilik suara dibuka', 'Bilik suara sudah dibuka', 'Bilik suara ditutup 3 jam lagi'])
            ->whereNull('data->viewData->pengingat')
            ->delete();

        DB::table('elections')->update(['pengingat_terkirim' => null]);
    }

    public function down(): void
    {
        // Data pemberitahuan yang dihapus tidak dikembalikan.
    }
};
