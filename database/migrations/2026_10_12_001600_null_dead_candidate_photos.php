<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Kosongkan foto_url kandidat yang menunjuk ke berkas lokal yang sudah tidak ada. */
    public function up(): void
    {
        DB::table('candidates')
            ->whereNotNull('foto_url')
            ->where('foto_url', 'like', '/%')
            ->orderBy('id')
            ->each(function ($calon) {
                if (! is_file(public_path(ltrim($calon->foto_url, '/')))) {
                    DB::table('candidates')->where('id', $calon->id)->update(['foto_url' => null]);
                }
            });
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: berkas lama sudah tidak ada.
    }
};
