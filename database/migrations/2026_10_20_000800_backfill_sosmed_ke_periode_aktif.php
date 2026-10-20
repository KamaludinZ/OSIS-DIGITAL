<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data media sosial lama masuk ke periode aktif:
     * - konten (social_posts) yang belum punya periode_id;
     * - akun TikTok diturunkan dari tautan konten (@nama) bila periode aktif belum punya akun TikTok.
     *   Akun YouTube/Instagram tidak bisa diturunkan dari tautan konten; tambahkan lewat /kelola/sosmed.
     */
    public function up(): void
    {
        $aktif = DB::table('osis_periods')->where('status', 'aktif')->value('id');
        if (! $aktif) {
            return;
        }

        DB::table('social_posts')->whereNull('periode_id')->update(['periode_id' => $aktif]);

        $adaTiktok = DB::table('social_links')->where('periode_id', $aktif)->where('platform', 'tiktok')->exists();
        $tautan = DB::table('social_posts')->where('platform', 'tiktok')->orderByDesc('id')->pluck('tautan');

        foreach ($adaTiktok ? [] : $tautan as $url) {
            if (preg_match('#^https://(?:www\.)?tiktok\.com/@([A-Za-z0-9._]{2,24})(?:/|$)#', $url, $cocok)) {
                DB::table('social_links')->insert([
                    'periode_id' => $aktif,
                    'platform' => 'tiktok',
                    'nama_akun' => '@'.$cocok[1],
                    'tautan' => 'https://www.tiktok.com/@'.$cocok[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                break;
            }
        }
    }

    public function down(): void
    {
        // Pengisian data; tidak dibatalkan.
    }
};
