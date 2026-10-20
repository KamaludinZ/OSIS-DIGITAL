<?php

namespace App\Exceptions;

use RuntimeException;

/** Perubahan periode tidak diizinkan (mis. mengubah arsip atau menghapus periode berisi data). */
class PeriodeDitolak extends RuntimeException {}
