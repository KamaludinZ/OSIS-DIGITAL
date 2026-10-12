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

    /** Sesi bilik suara berakhir sendiri agar komputer lab yang ditinggal tidak bisa dipakai orang lain. */
    public const MENIT_SESI_PEMILIH = 15;

    /**
     * Batas percobaan kode pilih yang SALAH. Berlapis: per sesi peramban, per IP per menit,
     * dan per IP per jam (lab sekolah sering berbagi satu IP, jadi batas per IP dibuat longgar).
     * Hitungan tidak dihapus saat berhasil, supaya kode milik sendiri tidak bisa dipakai
     * untuk "mengosongkan" batas lalu menebak kode orang lain.
     */
    private function batasKode(): array
    {
        $ip = request()->ip();

        return [
            ['kode-pilih-sesi:'.session()->getId(), 5, 60],
            ['kode-pilih:'.$ip, 20, 60],
            ['kode-pilih-jam:'.$ip, 60, 3600],
        ];
    }

    /** @throws ValidationException */
    public function masukDenganKode(string $kode, string $field = 'kode'): Voter
    {
        $kode = preg_replace('/\D/', '', $kode);

        foreach ($this->batasKode() as [$kunci, $maks]) {
            if (RateLimiter::tooManyAttempts($kunci, $maks)) {
                throw ValidationException::withMessages([$field => 'Terlalu banyak percobaan kode yang salah. Coba lagi dalam '.max(1, ceil(RateLimiter::availableIn($kunci) / 60)).' menit atau minta bantuan panitia.']);
            }
        }

        $pemilih = strlen($kode) === 6 ? Voter::where('kode_pilih', $kode)->first() : null;

        if (! $pemilih) {
            foreach ($this->batasKode() as [$kunci, , $detik]) {
                RateLimiter::hit($kunci, $detik);
            }
            $this->waspadaiTebakan();

            throw ValidationException::withMessages([$field => 'Kode pilih tidak dikenal. Periksa lagi 6 angka di kartumu.']);
        }

        // ID sesi baru (cegah session fixation) tanpa mengganti token CSRF: halaman Livewire
        // yang sedang terbuka tetap memakai token lamanya, regenerate() akan memicu 419.
        session()->migrate(true);
        session([self::KUNCI_PEMILIH => $pemilih->id, 'pemilih_masuk_pada' => now()->timestamp]);

        return $pemilih;
    }

    public function pemilih(): ?Voter
    {
        $id = session(self::KUNCI_PEMILIH);

        if ($id && now()->timestamp - (int) session('pemilih_masuk_pada', 0) > self::MENIT_SESI_PEMILIH * 60) {
            $this->keluar(); // kedaluwarsa: harus memasukkan kode lagi

            return null;
        }

        return $id ? Voter::find($id) : null;
    }

    /** Lonjakan kode salah dari satu IP: beri tahu panitia sekali per jam. */
    private function waspadaiTebakan(): void
    {
        $ip = request()->ip();

        if (RateLimiter::attempts('kode-pilih-jam:'.$ip) < 40 || ! \Illuminate\Support\Facades\Cache::add('waspada-kode:'.$ip, true, 3600)) {
            return;
        }

        $panitia = \App\Models\User::query()->where('aktif', true)->whereIn('role', ['panitia', 'admin'])->get();

        \Filament\Notifications\Notification::make()->title('Banyak kode pilih salah dari satu perangkat/jaringan')
            ->body("Lebih dari 40 kode salah dalam satu jam dari IP {$ip}. Periksa bilik suara; bila mencurigakan, buat ulang kode pemilih yang belum memilih.")
            ->warning()->sendToDatabase($panitia);
    }

    public function keluar(): void
    {
        session()->forget([self::KUNCI_PEMILIH, 'pemilih_masuk_pada']);
    }

    /** @throws ValidationException */
    public function verifikasiPenanya(string $jenis, string $nomorInduk, string $tanggalLahir, string $field = 'nomor_induk'): Voter
    {
        $ip = request()->ip();
        $batasan = [['verifikasi-penanya-sesi:'.session()->getId(), 5, 60], ['verifikasi-penanya:'.$ip, 15, 60], ['verifikasi-penanya-jam:'.$ip, 60, 3600]];

        foreach ($batasan as [$kunci, $maks]) {
            if (RateLimiter::tooManyAttempts($kunci, $maks)) {
                throw ValidationException::withMessages([$field => 'Terlalu banyak percobaan. Coba lagi dalam '.max(1, ceil(RateLimiter::availableIn($kunci) / 60)).' menit.']);
            }
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
            foreach ($batasan as [$kunci, , $detik]) {
                RateLimiter::hit($kunci, $detik);
            }

            throw ValidationException::withMessages([$field => ($jenisEnum?->labelNomorInduk() ?? 'Nomor induk').' dan tanggal lahir tidak cocok dengan data madrasah.']);
        }

        // ID sesi baru (cegah session fixation) tanpa mengganti token CSRF: halaman Livewire
        // yang sedang terbuka tetap memakai token lamanya, regenerate() akan memicu 419.
        session()->migrate(true);
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
