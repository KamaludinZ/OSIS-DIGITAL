<?php

namespace App\Rules;

use App\Enums\PlatformSosmed;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Tautan akun media sosial: alamat http/https yang domainnya sesuai platform.
 * Alamat http diterima lalu disimpan sebagai https (lihat normalisasi()).
 */
class TautanSosmed implements ValidationRule
{
    public function __construct(private ?PlatformSosmed $platform) {}

    /** "instagram.com/osim" / "http://…" -> "https://…"; spasi dibuang. */
    public static function normalisasi(string $tautan): string
    {
        $tautan = trim($tautan);

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $tautan)) {
            $tautan = 'https://'.$tautan;
        }

        return preg_replace('#^http://#i', 'https://', $tautan);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->platform) {
            return; // galat platform dilaporkan oleh aturan platform sendiri
        }

        $asli = trim((string) $value);
        $skema = strtolower((string) parse_url(self::normalisasiSkema($asli), PHP_URL_SCHEME));

        if (! in_array($skema, ['http', 'https'], true) || filter_var(self::normalisasi($asli), FILTER_VALIDATE_URL) === false) {
            $fail('Tautan harus berupa alamat http:// atau https://, contoh https://www.'.$this->platform->domain()[0].'/osim.mtsn2malang');

            return;
        }

        if (! $this->platform->cocokDengan(self::normalisasi($asli))) {
            $fail('Tautan ini bukan alamat '.$this->platform->label().'. Gunakan alamat dari '.implode(' atau ', $this->platform->domain()).'.');
        }
    }

    private static function normalisasiSkema(string $tautan): string
    {
        return preg_match('#^[a-z][a-z0-9+.-]*://#i', $tautan) ? $tautan : 'https://'.$tautan;
    }
}
