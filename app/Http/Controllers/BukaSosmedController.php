<?php

namespace App\Http\Controllers;

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
}
