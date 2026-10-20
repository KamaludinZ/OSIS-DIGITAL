<?php

namespace App\Models;

use App\Enums\JenisPemilih;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Pemilih: siswa (NISN) atau GTK (NIK). Memilih dengan kode 6 digit. */
class Voter extends Model
{
    /** @use HasFactory<\Database\Factories\VoterFactory> */
    use \App\Models\Concerns\IkutPeriode, HasFactory;

    protected $fillable = [
        'jenis', 'nomor_induk', 'nama', 'kelas', 'jabatan', 'tanggal_lahir', 'verifikasi',
    ];

    protected $hidden = ['kode_pilih'];

    protected function casts(): array
    {
        return [
            'jenis' => JenisPemilih::class,
            'tanggal_lahir' => 'date',
            'kode_dibuat_pada' => 'datetime',
            'penanya_terverifikasi_pada' => 'datetime',
        ];
    }

    /** Pemilih lama tanpa periode tetap terbaca; tanpa periode aktif tidak difilter. */
    public function periodeLonggar(): bool
    {
        return true;
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function scopeTerverifikasi(Builder $query): void
    {
        $query->where('verifikasi', 'terverifikasi');
    }

    public function sudahMemilih(Election $pemilihan): bool
    {
        return $this->votes()->where('election_id', $pemilihan->id)->exists();
    }

    /** Keterangan singkat: kelas untuk siswa, jabatan untuk GTK. */
    public function keterangan(): string
    {
        return $this->jenis === JenisPemilih::Siswa
            ? ($this->kelas ? 'Kelas '.$this->kelas : 'Siswa')
            : ($this->jabatan ?: 'GTK');
    }

    /** Buat kode pilih 6 digit acak yang unik. */
    public static function kodeBaru(): string
    {
        do {
            $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::where('kode_pilih', $kode)->exists());

        return $kode;
    }

    public function beriKode(): string
    {
        $this->forceFill(['kode_pilih' => static::kodeBaru(), 'kode_dibuat_pada' => now()])->save();

        return $this->kode_pilih;
    }
}
