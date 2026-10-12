<?php

namespace App\Enums;

enum StatusPemilihan: string
{
    case Draft = 'draft';
    case Berjalan = 'berjalan';
    case Selesai = 'selesai';
}
