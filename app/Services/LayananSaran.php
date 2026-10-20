<?php

namespace App\Services;

use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Models\Suggestion;
use Illuminate\Support\Facades\Validator;

/** Kotak saran periode aktif: kirim (anonim/bernama). */
class LayananSaran
{
    /** Aturan isian kirim saran; dipakai halaman publik juga. */
    public static function aturan(): array
    {
        return [
            'isi' => ['required', 'string', 'min:10', 'max:1000'],
            'bernama' => ['boolean'],
            'nama' => ['exclude_unless:bernama,true', 'required', 'string', 'min:3', 'max:100'],
            'kelas' => ['exclude_unless:bernama,true', 'nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Simpan saran ke periode aktif. Anonim: nama & kelas tidak disimpan sama sekali
     * (juga tidak IP / perangkat), agar pengirim tidak bisa ditelusuri.
     *
     * @param  array{isi: string, bernama?: bool, nama?: ?string, kelas?: ?string}  $data
     *
     * @throws PeriodeDitolak|\Illuminate\Validation\ValidationException
     */
    public function kirim(array $data): Suggestion
    {
        $data = Validator::make($data, self::aturan(), [], ['isi' => 'isi saran'])->validate();

        if (! OsisPeriod::aktif()) {
            throw new PeriodeDitolak('Kotak saran sedang ditutup karena belum ada kepengurusan aktif.');
        }

        $bernama = (bool) ($data['bernama'] ?? false);

        return Suggestion::create([
            'isi' => trim($data['isi']),
            'nama' => $bernama ? trim($data['nama']) : null,
            'kelas' => $bernama && filled($data['kelas'] ?? null) ? strtoupper(trim($data['kelas'])) : null,
        ]);
    }
}
