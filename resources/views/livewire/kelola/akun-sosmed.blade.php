<?php

use App\Enums\PlatformSosmed;
use App\Exceptions\PeriodeDitolak;
use App\Models\OsisPeriod;
use App\Models\SocialLink;
use App\Services\LayananAkunSosmed;
use Livewire\Volt\Component;

new class extends Component {
    /** id periode yang sedang dilihat; bawaan periode aktif. Periode lain hanya-baca (arsip). */
    public ?int $periode = null;

    // Formulir tambah/ubah akun periode aktif.
    public ?int $diubah = null;

    public string $platform = 'instagram';

    public string $namaAkun = '';

    public string $tautan = '';

    public function mount(): void
    {
        $this->authorize('kelola-publikasi');
        $this->periode = OsisPeriod::aktif()?->id;
    }

    private function bolehUbah(): bool
    {
        return $this->periode !== null && $this->periode === OsisPeriod::aktif()?->id;
    }

    public function simpan(LayananAkunSosmed $layanan): void
    {
        $this->authorize('kelola-publikasi');
        abort_unless($this->bolehUbah(), 403);

        $data = ['platform' => $this->platform, 'nama_akun' => $this->namaAkun, 'tautan' => $this->tautan];

        try {
            $this->diubah
                ? $layanan->ubah(SocialLink::findOrFail($this->diubah), $data)
                : $layanan->tambah($data);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Nama isian layanan -> nama properti komponen.
            foreach ($e->errors() as $isian => $pesan) {
                $this->addError(['nama_akun' => 'namaAkun'][$isian] ?? $isian, $pesan[0]);
            }

            return;
        } catch (PeriodeDitolak $e) {
            $this->addError('platform', $e->getMessage());

            return;
        }

        session()->flash('status-akun', $this->diubah ? 'Akun diperbarui.' : 'Akun ditambahkan.');
        $this->batal();
    }

    public function ubah(int $id): void
    {
        $akun = SocialLink::findOrFail($id); // global scope: hanya akun periode aktif

        $this->diubah = $akun->id;
        $this->platform = $akun->platform->value;
        $this->namaAkun = $akun->nama_akun;
        $this->tautan = $akun->tautan;
        $this->resetErrorBag();
    }

    public function hapus(int $id, LayananAkunSosmed $layanan): void
    {
        $this->authorize('kelola-publikasi');
        $akun = SocialLink::find($id);
        abort_unless($akun && $this->bolehUbah(), 403);

        $layanan->hapus($akun);
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
    public function updatedPeriode($nilai): void
    {
        if (! array_key_exists((int) $nilai, $this->daftarPeriode())) {
            $this->periode = OsisPeriod::aktif()?->id;
        }
    }

    /** @return array<int, array{nama: string, aktif: bool, jumlah: int}> id => periode; aktif di depan, lalu arsip yang punya akun. */
    private function daftarPeriode(): array
    {
        return OsisPeriod::query()
            ->where(fn ($q) => $q->where('status', 'aktif')->orWhereHas('socialLinks'))
            ->withCount('socialLinks')->urutTampil()->get()
            ->mapWithKeys(fn (OsisPeriod $p) => [$p->id => ['nama' => $p->nama, 'aktif' => $p->adalahAktif(), 'jumlah' => $p->social_links_count]])
            ->all();
    }

    public function with(): array
    {
        $daftar = $this->daftarPeriode();

        return [
            'daftarPeriode' => $daftar,
            'namaPeriode' => $daftar[$this->periode]['nama'] ?? '—',
            'akun' => $this->periode ? SocialLink::diPeriode($this->periode)->orderBy('urutan')->orderBy('id')->get() : collect(),
            // Arsip baca-saja: tombol ubah/tambah disembunyikan bila false.
            'bolehUbah' => $this->bolehUbah(),
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
            @foreach ($daftarPeriode as $id => $p)
                @php($dipilih = $id === $periode)
                <button type="button" role="tab" wire:click="$set('periode', {{ $id }})" aria-selected="{{ $dipilih ? 'true' : 'false' }}"
                        @class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-bold ring-1 transition',
                            'bg-hijau-600 text-white ring-hijau-600' => $dipilih,
                            'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => ! $dipilih])>
                    @if ($p['aktif']) <flux:icon.check-circle variant="micro" /> @else <flux:icon.archive-box variant="micro" /> @endif
                    {{ $p['nama'] }}
                    <span class="angka rounded-full bg-black/10 px-1.5 text-xs">{{ $p['jumlah'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($periode === null)
        <p class="flex items-center gap-1.5 bg-emas-300/40 px-5 py-2 text-sm font-semibold text-tinta"><flux:icon.exclamation-triangle variant="micro" /> Belum ada periode aktif. Aktifkan periode OSIM dulu untuk menambah akun.</p>
    @elseif (! $bolehUbah)
        <p class="flex items-center gap-1.5 bg-emas-300/40 px-5 py-2 text-sm font-semibold text-tinta"><flux:icon.lock-closed variant="micro" /> Arsip periode {{ $namaPeriode }}: hanya bisa dilihat.</p>
    @endif

    <ul @class(['divide-y divide-dashed divide-garis', 'opacity-75 grayscale' => ! $bolehUbah])>
        @forelse ($akun as $a)
            <li wire:key="akun-{{ $a->id }}" class="flex items-center gap-3 px-5 py-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-hijau-50 text-hijau-700 ring-1 ring-garis">
                    <x-osim.ikon-sosmed :platform="$a->platform->value" class="size-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-hijau-700">{{ $a->platform->label() }} · <span class="angka">{{ number_format($a->jumlah_klik, 0, ',', '.') }}</span> kali dibuka</p>
                    <p class="truncate font-bold text-tinta">{{ $a->nama_akun }}</p>
                    <p class="truncate text-sm text-tinta-2">{{ $a->tautan }}</p>
                </div>
                @if ($bolehUbah)
                    <div class="flex shrink-0 gap-1.5">
                        <button type="button" wire:click="ubah({{ $a->id }})" class="rounded-lg px-3 py-1.5 text-sm font-bold text-hijau-800 ring-1 ring-garis hover:bg-hijau-50">Ubah</button>
                        <button type="button" wire:click="hapus({{ $a->id }})"
                                wire:confirm="Hapus akun {{ $a->platform->label() }} {{ $a->nama_akun }} dari periode {{ $namaPeriode }}? Tautannya tidak tampil lagi di halaman publik."
                                class="rounded-lg px-3 py-1.5 text-sm font-bold text-cap hover:bg-cap/10">Hapus</button>
                    </div>
                @endif
            </li>
        @empty
            <li class="px-5 py-8 text-center text-tinta-2">Belum ada akun untuk periode {{ $namaPeriode }}.</li>
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
                    <input id="tautan-akun" wire:model="tautan" type="text" inputmode="url" maxlength="300" placeholder="https://…"
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
