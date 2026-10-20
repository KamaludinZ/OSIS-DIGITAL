<?php

use App\Enums\PlatformSosmed;
use App\Support\DataTiruanAkunSosmed;
use Livewire\Volt\Component;

new class extends Component {
    /** Periode yang sedang dilihat; bawaan periode aktif. Periode lain hanya-baca (arsip). */
    public string $periode = '';

    // Formulir tambah/ubah akun periode aktif.
    public ?int $diubah = null;

    public string $platform = 'instagram';

    public string $namaAkun = '';

    public string $tautan = '';

    public function mount(): void
    {
        $this->authorize('kelola-publikasi');
        $this->periode = DataTiruanAkunSosmed::periodeAktif();
    }

    public function simpan(): void
    {
        $this->authorize('kelola-publikasi');
        abort_unless($this->periode === DataTiruanAkunSosmed::periodeAktif(), 403);

        $platform = PlatformSosmed::tryFrom($this->platform);
        $this->validate([
            'platform' => ['required', \Illuminate\Validation\Rule::enum(PlatformSosmed::class),
                // Satu akun per platform di tiap periode.
                function ($attr, $nilai, $gagal) {
                    $kembar = collect(DataTiruanAkunSosmed::semua())->where('periode', $this->periode)
                        ->where('platform', $nilai)->where('id', '!=', $this->diubah)->isNotEmpty();
                    if ($kembar) {
                        $gagal('Akun '.PlatformSosmed::from($nilai)->label().' periode ini sudah ada. Ubah saja yang lama.');
                    }
                }],
            'namaAkun' => ['required', 'string', 'min:3', 'max:100'],
            'tautan' => ['required', 'url', 'max:300', function ($attr, $nilai, $gagal) use ($platform) {
                if ($platform && ! $platform->cocokDengan($nilai)) {
                    $gagal('Tautan harus diawali https:// dan berasal dari '.implode(' atau ', $platform->domain()).'.');
                }
            }],
        ], [
            'namaAkun.required' => 'Tulis nama akunnya, misalnya @osim.mtsn2malang.',
            'namaAkun.min' => 'Nama akun minimal :min karakter.',
            'tautan.required' => 'Tempel tautan profil akunnya.',
            'tautan.url' => 'Tautan belum benar. Salin langsung dari aplikasinya, contoh https://www.instagram.com/osim.mtsn2malang',
        ]);

        $semua = collect(DataTiruanAkunSosmed::semua());
        $isian = ['platform' => $this->platform, 'nama_akun' => trim($this->namaAkun), 'tautan' => trim($this->tautan)];
        $semua = $this->diubah
            ? $semua->map(fn ($a) => $a['id'] === $this->diubah ? [...$a, ...$isian] : $a)
            : $semua->push(['id' => ($semua->max('id') ?? 0) + 1, 'periode' => $this->periode, ...$isian]);
        DataTiruanAkunSosmed::simpan($semua->all());

        session()->flash('status-akun', $this->diubah ? 'Akun diperbarui.' : 'Akun ditambahkan.');
        $this->batal();
    }

    public function ubah(int $id): void
    {
        $akun = collect(DataTiruanAkunSosmed::semua())->where('periode', DataTiruanAkunSosmed::periodeAktif())->firstWhere('id', $id);
        abort_unless($akun, 404);

        $this->diubah = $id;
        $this->platform = $akun['platform'];
        $this->namaAkun = $akun['nama_akun'];
        $this->tautan = $akun['tautan'];
        $this->resetErrorBag();
    }

    public function hapus(int $id): void
    {
        $this->authorize('kelola-publikasi');
        $semua = collect(DataTiruanAkunSosmed::semua());
        abort_unless($semua->where('periode', DataTiruanAkunSosmed::periodeAktif())->contains('id', $id), 403);

        DataTiruanAkunSosmed::simpan($semua->reject(fn ($a) => $a['id'] === $id)->all());
        session()->flash('status-akun', 'Akun dihapus.');
        if ($this->diubah === $id) {
            $this->batal();
        }
    }

    public function batal(): void
    {
        $this->reset('diubah', 'namaAkun', 'tautan');
        $this->platform = 'instagram';
        $this->resetErrorBag();
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
                @if ($bolehUbah)
                    <div class="flex shrink-0 gap-1.5">
                        <button type="button" wire:click="ubah({{ $a['id'] }})" class="rounded-lg px-3 py-1.5 text-sm font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">Ubah</button>
                        <button type="button" wire:click="hapus({{ $a['id'] }})"
                                wire:confirm="Hapus akun {{ $platform->label() }} {{ $a['nama_akun'] }} dari periode {{ $periode }}? Tautannya tidak tampil lagi di halaman publik."
                                class="rounded-lg px-3 py-1.5 text-sm font-bold text-cap hover:bg-cap/10">Hapus</button>
                    </div>
                @endif
            </li>
        @empty
            <li class="px-5 py-8 text-center text-tinta-2">Belum ada akun untuk periode {{ $periode }}.</li>
        @endforelse
    </ul>

    @if ($bolehUbah)
        <form wire:submit="simpan" class="flex flex-col gap-3 border-t border-dashed border-garis bg-hijau-50/40 px-5 py-4">
            <h3 class="font-bold text-tinta">{{ $diubah ? 'Ubah akun' : 'Tambah akun' }}</h3>
            @if (session('status-akun'))
                <p class="flex items-center gap-1.5 rounded-lg bg-hijau-100 px-3 py-2 text-sm font-bold text-hijau-800" role="status"><flux:icon.check-circle variant="micro" /> {{ session('status-akun') }}</p>
            @endif
            <fieldset>
                <legend class="sr-only">Platform</legend>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (PlatformSosmed::cases() as $p)
                        <label @class(['flex cursor-pointer items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-sm font-bold ring-1', 'bg-hijau-600 text-white ring-hijau-600' => $platform === $p->value, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => $platform !== $p->value])>
                            <input type="radio" wire:model.live="platform" value="{{ $p->value }}" class="sr-only" />
                            <x-osim.ikon-sosmed :platform="$p->value" class="size-4" /> {{ $p->label() }}
                        </label>
                    @endforeach
                </div>
                @error('platform') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
            </fieldset>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="nama-akun" class="text-sm font-bold text-tinta">Nama akun</label>
                    <input id="nama-akun" wire:model="namaAkun" type="text" maxlength="100" placeholder="@osim.mtsn2malang"
                           class="mt-1 h-11 w-full rounded-xl border-2 border-garis bg-white px-3 text-tinta focus:border-hijau-600 focus:outline-none" />
                    @error('namaAkun') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tautan-akun" class="text-sm font-bold text-tinta">Tautan profil</label>
                    <input id="tautan-akun" wire:model="tautan" type="url" maxlength="300" placeholder="https://…"
                           class="mt-1 h-11 w-full rounded-xl border-2 border-garis bg-white px-3 text-tinta focus:border-hijau-600 focus:outline-none" />
                    @error('tautan') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex flex-wrap justify-end gap-2">
                @if ($diubah)
                    <button type="button" wire:click="batal" class="rounded-xl px-4 py-2 font-bold text-hijau-800 ring-1 ring-garis hover:bg-white">Batal</button>
                @endif
                <button type="submit" wire:loading.attr="disabled" wire:target="simpan"
                        class="inline-flex items-center gap-2 rounded-xl bg-hijau-600 px-4 py-2 font-bold text-white hover:bg-hijau-700 disabled:opacity-60">
                    <flux:icon.check variant="micro" /> {{ $diubah ? 'Simpan perubahan' : 'Tambah akun' }}
                </button>
            </div>
        </form>
    @endif
</section>
