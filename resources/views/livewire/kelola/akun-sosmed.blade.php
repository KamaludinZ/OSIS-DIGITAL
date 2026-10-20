<?php

use App\Enums\PlatformSosmed;
use App\Support\DataTiruanAkunSosmed;
use Livewire\Volt\Component;

new class extends Component {
    /** Periode yang sedang dilihat; bawaan periode aktif. Periode lain hanya-baca (arsip). */
    public string $periode = '';

    public function mount(): void
    {
        $this->authorize('kelola-publikasi');
        $this->periode = DataTiruanAkunSosmed::periodeAktif();
    }

    /** Nilai periode dari peramban hanya diterima bila memang ada; selain itu kembali ke periode aktif. */
    public function updatedPeriode(string $nilai): void
    {
        if (! array_key_exists($nilai, $this->jumlahPerPeriode())) {
            $this->periode = DataTiruanAkunSosmed::periodeAktif();
        }
    }

    /** @return array<string, int> periode => jumlah akun, periode aktif selalu ada dan di depan. */
    private function jumlahPerPeriode(): array
    {
        $aktif = DataTiruanAkunSosmed::periodeAktif();

        return collect(DataTiruanAkunSosmed::semua())->countBy('periode')
            ->union([$aktif => 0])
            ->sortKeysDesc()
            ->sortBy(fn ($n, $p) => $p === $aktif ? 0 : 1)
            ->all();
    }

    public function with(): array
    {
        return [
            'daftarPeriode' => $this->jumlahPerPeriode(),
            'akun' => collect(DataTiruanAkunSosmed::semua())->where('periode', $this->periode)->values()->all(),
            // Arsip baca-saja: tombol ubah/tambah disembunyikan bila false.
            'bolehUbah' => $this->periode === DataTiruanAkunSosmed::periodeAktif(),
        ];
    }
}; ?>

<section aria-labelledby="judul-akun-sosmed" class="overflow-hidden rounded-[14px] bg-white ring-1 ring-garis shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-dashed border-garis px-5 py-4">
        <div>
            <h2 id="judul-akun-sosmed" class="font-display text-xl font-black text-tinta">Akun resmi OSIM</h2>
            <p class="text-sm text-tinta-2">Tautan akun yang tampil di halaman publik. Tiap periode punya daftarnya sendiri.</p>
        </div>
        <div role="tablist" aria-label="Pilih periode" class="flex flex-wrap gap-1.5">
            @foreach ($daftarPeriode as $p => $jumlah)
                @php($dipilih = $p === $periode)
                <button type="button" role="tab" wire:click="$set('periode', '{{ $p }}')" aria-selected="{{ $dipilih ? 'true' : 'false' }}"
                        @class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-bold ring-1 transition',
                            'bg-hijau-600 text-white ring-hijau-600' => $dipilih,
                            'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => ! $dipilih])>
                    @if ($loop->first) <flux:icon.check-circle variant="micro" /> @else <flux:icon.archive-box variant="micro" /> @endif
                    {{ $p }}
                    <span class="angka rounded-full bg-black/10 px-1.5 text-xs">{{ $jumlah }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @unless ($bolehUbah)
        <p class="flex items-center gap-1.5 bg-emas-300/40 px-5 py-2 text-sm font-semibold text-tinta"><flux:icon.lock-closed variant="micro" /> Arsip periode {{ $periode }}: hanya bisa dilihat.</p>
    @endunless

    <ul @class(['divide-y divide-dashed divide-garis', 'opacity-75 grayscale' => ! $bolehUbah])>
        @forelse ($akun as $a)
            @php($platform = PlatformSosmed::from($a['platform']))
            <li wire:key="akun-{{ $a['id'] }}" class="flex items-center gap-3 px-5 py-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-hijau-50 text-hijau-700 ring-1 ring-garis">
                    <x-osim.ikon-sosmed :platform="$a['platform']" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-hijau-700">{{ $platform->label() }}</p>
                    <p class="truncate font-bold text-tinta">{{ $a['nama_akun'] }}</p>
                    <p class="truncate text-sm text-tinta-2">{{ $a['tautan'] }}</p>
                </div>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-tinta-2">Belum ada akun untuk periode {{ $periode }}.</li>
        @endforelse
    </ul>
</section>
