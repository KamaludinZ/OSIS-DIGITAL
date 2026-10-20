<?php

namespace App\Services;

use App\Enums\PlatformSosmed;
use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Models\SocialLink;
use App\Rules\TautanSosmed;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Akun media sosial resmi OSIM per periode. */
class LayananAkunSosmed
{
    /** Hasil publik() diingat selama satu permintaan (layout & Beranda memakai data yang sama). */
    private ?array $publik = null;

    /** Aturan isian akun; platform wajib dan tautan harus alamat platform tersebut. */
    public static function aturan(?string $platform, ?SocialLink $kecuali = null): array
    {
        return [
            'platform' => ['required', Rule::enum(PlatformSosmed::class),
                // Satu akun per platform di periode aktif.
                Rule::unique('social_links', 'platform')->where('periode_id', OsisPeriod::aktif()?->id)->ignore($kecuali?->id)],
            'nama_akun' => ['required', 'string', 'min:3', 'max:100'],
            'tautan' => ['required', 'string', 'max:300', new TautanSosmed(PlatformSosmed::tryFrom((string) $platform))],
        ];
    }

    public static function pesan(): array
    {
        return [
            'platform.required' => 'Pilih platform media sosialnya.',
            'platform.enum' => 'Platform harus YouTube, Instagram, atau TikTok.',
            'platform.unique' => 'Akun platform ini untuk periode aktif sudah ada. Ubah saja yang lama.',
            'nama_akun.required' => 'Tulis nama akunnya, misalnya @osim.mtsn2malang.',
            'nama_akun.min' => 'Nama akun minimal :min karakter.',
            'tautan.required' => 'Tempel tautan profil akunnya.',
        ];
    }

    /** @throws PeriodeDitolak|\Illuminate\Validation\ValidationException */
    public function tambah(array $data): SocialLink
    {
        $this->pastikanPeriodeAktif();
        $this->publik = null;

        return SocialLink::create([...$this->isian($data), 'urutan' => (SocialLink::max('urutan') ?? 0) + 1]);
    }

    /** Hanya akun periode aktif. @throws PeriodeDitolak|\Illuminate\Validation\ValidationException */
    public function ubah(SocialLink $akun, array $data): SocialLink
    {
        $this->pastikanMilikPeriodeAktif($akun);
        $this->publik = null;
        $akun->update($this->isian($data, $akun));

        return $akun;
    }

    /**
     * Hapus akun milik periode aktif. Akun periode arsip maupun periode yang baru disiapkan
     * tidak bisa dihapus dari sini, agar riwayat akun tiap periode tetap utuh.
     *
     * @throws PeriodeDitolak
     */
    public function hapus(SocialLink $akun): void
    {
        $this->pastikanMilikPeriodeAktif($akun);
        $this->publik = null;
        $akun->delete();
    }

    /** @throws PeriodeDitolak */
    private function pastikanMilikPeriodeAktif(SocialLink $akun): void
    {
        $this->pastikanPeriodeAktif();

        if ((int) $akun->periode_id !== OsisPeriod::aktif()->id) {
            throw new PeriodeDitolak('Akun ini milik periode '.($akun->periode?->nama ?? 'lain').'; hanya akun periode aktif yang bisa diubah atau dihapus.');
        }
    }

    /**
     * Akun periode aktif untuk halaman publik; url menuju pengalihan aman.
     *
     * @return list<array{id: int, platform: string, label: string, nama_akun: string, url: string}>
     */
    public function publik(): array
    {
        return $this->publik ??= SocialLink::query()->orderBy('urutan')->orderBy('id')->get()
            ->filter(fn (SocialLink $a) => $a->tautanSah())
            ->map(fn (SocialLink $a) => [
                'id' => $a->id,
                'platform' => $a->platform->value,
                'label' => $a->platform->label(),
                'nama_akun' => $a->nama_akun,
                'url' => route('sosmed.akun', $a->id),
            ])->values()->all();
    }

    private function isian(array $data, ?SocialLink $kecuali = null): array
    {
        $data = Validator::make($data, self::aturan($data['platform'] ?? null, $kecuali), self::pesan())->validate();

        return [
            'platform' => $data['platform'],
            'nama_akun' => trim($data['nama_akun']),
            'tautan' => TautanSosmed::normalisasi($data['tautan']),
        ];
    }

    /** @throws PeriodeDitolak */
    private function pastikanPeriodeAktif(): void
    {
        if (! OsisPeriod::aktif()) {
            throw new PeriodeDitolak('Belum ada periode aktif. Aktifkan periode OSIM dulu.');
        }
    }
}
