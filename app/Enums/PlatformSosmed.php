<?php

namespace App\Enums;

enum PlatformSosmed: string
{
    case YouTube = 'youtube';
    case Instagram = 'instagram';
    case TikTok = 'tiktok';

    public function label(): string
    {
        return match ($this) {
            self::YouTube => 'YouTube',
            self::Instagram => 'Instagram',
            self::TikTok => 'TikTok',
        };
    }

    /** Domain yang sah untuk tautan konten platform ini. */
    public function domain(): array
    {
        return match ($this) {
            self::YouTube => ['youtube.com', 'youtu.be'],
            self::Instagram => ['instagram.com'],
            self::TikTok => ['tiktok.com'],
        };
    }

    /** Orientasi awal di formulir; YouTube & Instagram bisa potret maupun lanskap. */
    public function orientasiBawaan(): string
    {
        return $this === self::TikTok ? 'potret' : 'lanskap';
    }

    /** Apakah tautan benar-benar mengarah ke platform ini (https, domain cocok). */
    public function cocokDengan(string $url): bool
    {
        $bagian = parse_url($url);

        if (($bagian['scheme'] ?? null) !== 'https' || empty($bagian['host'])) {
            return false;
        }

        $host = strtolower($bagian['host']);

        foreach ($this->domain() as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
