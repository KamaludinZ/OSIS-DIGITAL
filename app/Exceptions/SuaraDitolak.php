<?php

namespace App\Exceptions;

use RuntimeException;

/** Suara tidak bisa dicatat (tidak layak, kandidat salah, atau sudah memilih). */
class SuaraDitolak extends RuntimeException
{
    public function __construct(string $message, public readonly string $alasan = 'ditolak')
    {
        parent::__construct($message);
    }
}
