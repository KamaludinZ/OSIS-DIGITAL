<?php

namespace App\Models;

use App\Enums\TipeMateri;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMaterial extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignMaterialFactory> */
    use HasFactory;

    protected $fillable = ['candidate_id', 'tipe', 'url_konten', 'deskripsi', 'urutan'];

    protected function casts(): array
    {
        return ['tipe' => TipeMateri::class, 'urutan' => 'integer'];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
