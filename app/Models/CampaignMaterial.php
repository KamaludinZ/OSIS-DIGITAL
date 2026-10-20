<?php

namespace App\Models;

use App\Enums\TipeMateri;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMaterial extends Model
{
    // Periode dari kandidat lebih dulu; IkutPeriode menambah global scope (mode longgar) & penjaga arsip.
    use \App\Models\Concerns\PeriodeDariKandidat, \App\Models\Concerns\IkutPeriode;

    /** Data lama tanpa periode tetap terbaca; tanpa periode aktif tidak difilter. */
    public function periodeLonggar(): bool
    {
        return true;
    }

    /** @use HasFactory<\Database\Factories\CampaignMaterialFactory> */
    use HasFactory;

    protected $fillable = ['candidate_id', 'tipe', 'url_konten', 'deskripsi', 'urutan'];

    protected function casts(): array
    {
        return ['tipe' => TipeMateri::class, 'urutan' => 'integer'];
    }

    /** Poster unggahan panel disimpan relatif di disk public; tautan lengkap dipakai apa adanya. */
    public function urlPublik(): ?string
    {
        if (blank($this->url_konten) || str_starts_with($this->url_konten, 'http') || str_starts_with($this->url_konten, '/')) {
            return $this->url_konten ?: null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->url_konten);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
