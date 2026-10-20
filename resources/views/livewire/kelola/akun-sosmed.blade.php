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

    public function with(): array
    {
        $semua = collect(DataTiruanAkunSosmed::semua());

        return [
            'daftarPeriode' => $semua->pluck('periode')->push(DataTiruanAkunSosmed::periodeAktif())->unique()->sortDesc()->values()->all(),
            'akun' => $semua->where('periode', $this->periode)->values()->all(),
            'aktif' => $this->periode === DataTiruanAkunSosmed::periodeAktif(),
        ];
    }
}; ?>

<section aria-labelledby="judul-akun-sosmed" class="overflow-hidden rounded-[14px] bg-white ring-1 ring-garis shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)]">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-dashed border-garis px-5 py-4">
        <div>
            <h2 id="judul-akun-sosmed" class="font-display text-xl font-black text-tinta">Akun resmi OSIM</h2>
            <p class="text-sm text-tinta-2">Tautan akun yang tampil di halaman publik. Tiap periode punya daftarnya sendiri.</p>
        </div>
        <label class="flex items-center gap-2 text-sm font-bold text-tinta">
            Periode
            <select wire:model.live="periode" class="h-10 rounded-xl border-2 border-garis bg-hijau-50/50 px-3 font-bold text-tinta focus:border-hijau-600 focus:outline-none">
                @foreach ($daftarPeriode as $p)
                    <option value="{{ $p }}">{{ $p }}{{ $p === \App\Support\DataTiruanAkunSosmed::periodeAktif() ? ' (aktif)' : ' (arsip)' }}</option>
                @endforeach
            </select>
        </label>
    </div>

    @unless ($aktif)
        <p class="flex items-center gap-1.5 bg-emas-300/40 px-5 py-2 text-sm font-semibold text-tinta"><flux:icon.lock-closed variant="micro" /> Arsip periode {{ $periode }}: hanya bisa dilihat.</p>
    @endunless

    <ul class="divide-y divide-dashed divide-garis">
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
