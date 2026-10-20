<?php

namespace App\Services;

use App\Exceptions\PeriodeDitolak;
use App\Models\OrganizationStructure;
use App\Models\OsisPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Bagan struktur organisasi periode aktif: unggah/ganti, ubah rasio, hapus. */
class LayananStruktur
{
    public function aktif(): ?OrganizationStructure
    {
        return OrganizationStructure::aktif();
    }

    /**
     * Data halaman publik Struktur Organisasi: hanya bagan periode aktif, tanpa data pengunggah.
     *
     * @return array{periode: ?string, ada_periode_aktif: bool, struktur: ?array{gambar: string, rasio: string, diperbarui: string}}
     */
    public function untukPublik(): array
    {
        $periode = OsisPeriod::aktif();
        $bagan = $periode ? $this->aktif() : null;

        return [
            'periode' => $periode?->nama,
            'ada_periode_aktif' => $periode !== null,
            'struktur' => $bagan ? [
                'gambar' => $bagan->urlGambar(),
                'rasio' => $bagan->rasio,
                'diperbarui' => $bagan->updated_at->toDateTimeString(),
            ] : null,
        ];
    }

    /**
     * Arsip bagan periode lama untuk halaman publik (hanya periode berstatus arsip), terbaru dulu.
     *
     * @return array<string, array{periode: string, gambar: string, rasio: string, diperbarui: string}> slug periode => data
     */
    public function arsipPublik(): array
    {
        return OsisPeriod::query()->where('status', \App\Enums\StatusPeriode::Arsip)->has('struktur')
            ->with('struktur')->orderByDesc('nama')->get()
            ->mapWithKeys(fn (OsisPeriod $p) => [str_replace('/', '-', $p->nama) => [
                'periode' => $p->nama,
                'gambar' => $p->struktur->urlGambar(),
                'rasio' => $p->struktur->rasio,
                'diperbarui' => $p->struktur->updated_at->toDateTimeString(),
            ]])->all();
    }

    /**
     * Simpan bagan periode aktif dari berkas yang sudah tersimpan di disk public.
     * Bagan lama periode ini diganti dan berkas lamanya dihapus.
     *
     * @throws PeriodeDitolak|ValidationException
     */
    public function unggah(string $path, string $rasio, User $oleh): OrganizationStructure
    {
        try {
            $this->periksaRasio($rasio);
            $periode = OsisPeriod::aktif() ?? throw new PeriodeDitolak('Belum ada periode aktif. Aktifkan periode OSIM dulu.');
        } catch (PeriodeDitolak|ValidationException $e) {
            // Berkas yang terlanjur diunggah tidak dipakai: jangan tinggalkan sampah di disk.
            Storage::disk('public')->delete($path);

            throw $e;
        }

        [$struktur, $berkasLama] = DB::transaction(function () use ($path, $rasio, $oleh, $periode) {
            $struktur = OrganizationStructure::query()->lockForUpdate()->firstOrNew(['periode_id' => $periode->id]);
            $berkasLama = $struktur->exists ? $struktur->gambar : null;
            $struktur->fill(['gambar' => $path, 'rasio' => $rasio, 'diunggah_oleh' => $oleh->id])->save();

            return [$struktur, $berkasLama];
        });

        if ($berkasLama && $berkasLama !== $path) {
            Storage::disk('public')->delete($berkasLama);
        }

        return $struktur;
    }

    /** @throws PeriodeDitolak|ValidationException */
    public function ubahRasio(string $rasio): OrganizationStructure
    {
        $this->periksaRasio($rasio);
        $struktur = $this->aktif() ?? throw new PeriodeDitolak('Belum ada bagan untuk periode aktif.');
        $struktur->update(['rasio' => $rasio]);

        return $struktur;
    }

    /** Ganti gambar bagan periode aktif (rasio tetap bila tidak diberikan). @throws PeriodeDitolak|ValidationException */
    public function ganti(string $path, User $oleh, ?string $rasio = null): OrganizationStructure
    {
        $lama = $this->aktif();

        if (! $lama) {
            Storage::disk('public')->delete($path);

            throw new PeriodeDitolak('Belum ada bagan untuk periode aktif. Unggah dulu.');
        }

        return $this->unggah($path, $rasio ?? $lama->rasio, $oleh);
    }

    /** Hapus bagan periode aktif beserta berkasnya; bagan periode lain tidak tersentuh. @throws PeriodeDitolak */
    public function hapus(): void
    {
        $struktur = $this->aktif() ?? throw new PeriodeDitolak('Belum ada bagan untuk periode aktif.');
        $struktur->delete();
        Storage::disk('public')->delete($struktur->gambar);
    }

    /** @throws ValidationException */
    private function periksaRasio(string $rasio): void
    {
        if (! array_key_exists($rasio, OrganizationStructure::RASIO)) {
            throw ValidationException::withMessages(['rasio' => 'Rasio tampilan harus 4:3 atau 3:4.']);
        }
    }
}
