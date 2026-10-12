<?php

namespace App\Enums;

enum Peran: string
{
    case Siswa = 'siswa';
    case Pengurus = 'pengurus';
    case Panitia = 'panitia';
    case Admin = 'admin';
}
