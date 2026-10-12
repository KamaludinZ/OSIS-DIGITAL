<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    /** @use HasFactory<\Database\Factories\DivisionFactory> */
    use HasFactory;

    protected $fillable = ['nama', 'deskripsi'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'divisi_id');
    }

    public function workPrograms(): HasMany
    {
        return $this->hasMany(WorkProgram::class, 'divisi_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'divisi_id');
    }
}
