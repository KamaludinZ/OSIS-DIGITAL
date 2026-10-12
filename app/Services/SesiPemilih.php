<?php

namespace App\Services;

use App\Enums\JenisPemilih;
use App\Models\Voter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Identitas pemilih tanpa akun login:
 * - Bilik suara: kode pilih 6 digit  -> session "pemilih_id".
 * - Tanya jawab: NISN/NIK + tanggal lahir -> session "penanya_id".
 * Percobaan dibatasi per IP agar kode/identitas tidak bisa ditebak.
 */
class SesiPemilih
{
    public const KUNCI_PEMILIH = 'pemilih_id';

    public const KUNCI_PENANYA = 'penanya_id';

    /** @throws ValidationException */
    public function masukDenganKode(string $kode, string $field = 'kode'): Voter
    {
        $kode = preg_replace('/\D/', '', $kode);
        $batas = 'kode-pilih:'.request()->ip();

        if (RateLimiter::tooManyAttempts($batas, 10)) {
            throw ValidationException::withMessages([$field => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($batas).' detik.']);
        }

        $pemilih = strlen($kode) === 6 ? Voter::where('kode_pilih', $kode)->first() : null;

        if (! $pemilih) {
            RateLimiter::hit($batas, 60);
            throw ValidationException::withMessages([$field => 'Kode pilih tidak dikenal. Periksa lagi 6 angka di kartumu.']);
        }

        RateLimiter::clear($batas);
        session()->regenerate();
        session([self::KUNCI_PEMILIH => $pemilih->id]);

        return $pemilih;
    }

    public function pemilih(): ?Voter
    {
        $id = session(self::KUNCI_PEMILIH);

        return $id ? Voter::find($id) : null;
    }

    public function keluar(): void
    {
        session()->forget(self::KUNCI_PEMILIH);
    }

    /** @throws ValidationException */
    public function verifikasiPenanya(string $jenis, string $nomorInduk, string $tanggalLahir, string $field = 'nomor_induk'): Voter
    {
        $batas = 'verifikasi-penanya:'.request()->ip();

        if (RateLimiter::tooManyAttempts($batas, 5)) {
            throw ValidationException::withMessages([$field => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($batas).' detik.']);
        }

        $jenisEnum = JenisPemilih::tryFrom($jenis);
        $nomorInduk = preg_replace('/\s+/', '', $nomorInduk);

        try {
            $tanggal = Carbon::parse($tanggalLahir)->toDateString();
        } catch (\Throwable) {
            $tanggal = null;
        }

        $pemilih = ($jenisEnum && $tanggal)
            ? Voter::where('jenis', $jenisEnum)->where('nomor_induk', $nomorInduk)->whereDate('tanggal_lahir', $tanggal)->first()
            : null;

        if (! $pemilih) {
            RateLimiter::hit($batas, 60);
            throw ValidationException::withMessages([$field => ($jenisEnum?->labelNomorInduk() ?? 'Nomor induk').' dan tanggal lahir tidak cocok dengan data madrasah.']);
        }

        RateLimiter::clear($batas);
        $pemilih->forceFill(['penanya_terverifikasi_pada' => $pemilih->penanya_terverifikasi_pada ?? now()])->save();
        session([self::KUNCI_PENANYA => $pemilih->id]);

        return $pemilih;
    }

    public function penanya(): ?Voter
    {
        $id = session(self::KUNCI_PENANYA);

        return $id ? Voter::find($id) : null;
    }
}
