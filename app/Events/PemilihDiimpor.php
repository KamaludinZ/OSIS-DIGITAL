<?php

namespace App\Events;

use App\Models\VoterImport;
use Illuminate\Foundation\Events\Dispatchable;

/** Daftar pemilih berubah karena impor massal; rekap & tampilan partisipasi perlu disegarkan. */
class PemilihDiimpor
{
    use Dispatchable;

    public function __construct(public VoterImport $log) {}
}
