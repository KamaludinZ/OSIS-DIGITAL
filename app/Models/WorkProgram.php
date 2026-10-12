<?php

namespace App\Models;

use App\Enums\StatusProgramKerja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkProgram extends Model
{
    /** @use HasFactory<\Database\Factories\WorkProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'nama',
        'deskripsi',
        'divisi_id',
        'unggulan',
        'status',
        'capaian',
    ];

    protected function casts(): array
    {
        return [
            'unggulan' => 'boolean',
            'status' => StatusProgramKerja::class,
        ];
    }

    public function divisi(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'divisi_id');
    }
}
