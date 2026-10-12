<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /** Jenis pemberitahuan akun => [label, keterangan]. */
    public const JENIS = [
        'pengumuman' => ['Pengumuman baru', 'Saat pengurus menayangkan pengumuman OSIM.'],
        'pemilihan' => ['Pengingat pemilihan', 'Menjelang bilik suara dibuka dan sebelum ditutup.'],
        'pertanyaan' => ['Pertanyaan untuk kandidat', 'Saat ada pertanyaan baru di tanya jawab kandidatmu.'],
    ];

    protected $fillable = ['user_id', 'jenis', 'aktif'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
