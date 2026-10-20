<?php

namespace App\Services;

use App\Models\OrganizationStructure;
use App\Models\OsisPeriod;
use App\Models\SocialLink;
use App\Models\Suggestion;

/** Kondisi periode kepengurusan berjalan untuk dasbor Ruang OSIM. */
class LayananRingkasan
{
    /**
     * @return array{periode: ?string, rentang: ?string, mode_pemilihan: bool, ada_bagan: bool, saran_belum: int,
     *               saran_total: int, akun_sosmed: int, pemilihan: ?string, suara_masuk: ?int, total_pemilih: ?int}
     */
    public function kondisiPeriode(): array
    {
        $periode = OsisPeriod::aktif();
        $pemilihan = app(LayananPemilihan::class)->pemilihanAktif();
        $rekap = $pemilihan ? app(LayananHasil::class)->rekap($pemilihan) : null;

        return [
            'periode' => $periode?->nama,
            'rentang' => $periode?->rentang(),
            'mode_pemilihan' => app(LayananModePemilihan::class)->aktif(),
            'ada_bagan' => $periode !== null && OrganizationStructure::query()->exists(),
            'saran_belum' => $periode ? Suggestion::query()->belumDitanggapi()->count() : 0,
            'saran_total' => $periode ? Suggestion::query()->count() : 0,
            'akun_sosmed' => $periode ? SocialLink::query()->count() : 0,
            'pemilihan' => $pemilihan?->judul,
            'suara_masuk' => $rekap['suara_masuk'] ?? null,
            'total_pemilih' => $rekap['total_pemilih'] ?? null,
        ];
    }
}
