<?php

namespace App\Services;

use App\Enums\PlatformSosmed;
use App\Rules\TautanSosmed;
use Illuminate\Validation\Rule;

/** Akun media sosial resmi OSIM per periode. */
class LayananAkunSosmed
{
    /** Aturan isian akun; platform wajib dan tautan harus alamat platform tersebut. */
    public static function aturan(?string $platform): array
    {
        return [
            'platform' => ['required', Rule::enum(PlatformSosmed::class)],
            'nama_akun' => ['required', 'string', 'min:3', 'max:100'],
            'tautan' => ['required', 'string', 'max:300', new TautanSosmed(PlatformSosmed::tryFrom((string) $platform))],
        ];
    }

    public static function pesan(): array
    {
        return [
            'platform.required' => 'Pilih platform media sosialnya.',
            'platform.enum' => 'Platform harus YouTube, Instagram, atau TikTok.',
            'nama_akun.required' => 'Tulis nama akunnya, misalnya @osim.mtsn2malang.',
            'nama_akun.min' => 'Nama akun minimal :min karakter.',
            'tautan.required' => 'Tempel tautan profil akunnya.',
        ];
    }
}
