<?php

namespace App\Support;

/** Format angka gaya Indonesia (koma desimal, titik ribuan). */
class Angka
{
    /** Persentase a dari b dengan dua angka di belakang koma, mis. "57,14%"; b = 0 -> "0,00%". */
    public static function persen(int|float $bagian, int|float $total): string
    {
        return number_format($total > 0 ? $bagian / $total * 100 : 0, 2, ',', '.').'%';
    }
}
