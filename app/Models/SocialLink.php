<?php

namespace App\Models;

use App\Enums\PlatformSosmed;
use App\Models\Concerns\IkutPeriode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Akun media sosial resmi OSIM; otomatis masuk & dibatasi ke periode aktif. */
class SocialLink extends Model
{
    /** @use HasFactory<\Database\Factories\SocialLinkFactory> */
    use HasFactory, IkutPeriode;

    protected $fillable = ['platform', 'nama_akun', 'tautan', 'urutan'];

    protected function casts(): array
    {
        return [
            'platform' => PlatformSosmed::class,
            'urutan' => 'integer',
            'jumlah_klik' => 'integer',
        ];
    }

    /** Tautan aman dibuka: https dan domain sesuai platformnya. */
    public function tautanSah(): bool
    {
        return $this->platform->cocokDengan($this->tautan);
    }
}
