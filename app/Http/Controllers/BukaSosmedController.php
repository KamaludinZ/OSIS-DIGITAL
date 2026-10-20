<?php

namespace App\Http\Controllers;

use App\Models\SocialLink;
use App\Models\SocialPost;
use App\Services\LayananSosmed;
use Illuminate\Http\RedirectResponse;

class BukaSosmedController extends Controller
{
    /** Hitung kunjungan lalu arahkan ke konten asli di platformnya. */
    public function __invoke(SocialPost $post, LayananSosmed $layanan): RedirectResponse
    {
        abort_unless($post->dipublikasikan && $post->platform->cocokDengan($post->tautan), 404);

        $layanan->catatKlik($post);

        return redirect()->away($post->tautan);
    }

    /**
     * Akun resmi OSIM periode aktif: hitung kunjungan (sekali per sesi) lalu arahkan ke profilnya.
     * Akun periode lain tidak ditemukan (global scope); tautan yang tidak sah ditolak 404.
     */
    public function akun(int $akun): RedirectResponse
    {
        $akun = SocialLink::findOrFail($akun);
        abort_unless($akun->tautanSah(), 404);

        $sudah = session('akun_sosmed_dibuka', []);
        if (! in_array($akun->id, $sudah, true)) {
            // Tanpa memicu updated_at / penjaga arsip: hanya penghitung.
            SocialLink::query()->whereKey($akun->id)->increment('jumlah_klik');
            session(['akun_sosmed_dibuka' => [...$sudah, $akun->id]]);
        }

        return redirect()->away($akun->tautan);
    }
}
