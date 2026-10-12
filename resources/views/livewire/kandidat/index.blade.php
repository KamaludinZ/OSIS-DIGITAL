<?php

use App\Services\LayananPemilihan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Kandidat')] class extends Component {
    public ?array $pemilihan = null;

    public array $kandidat = [];

    public function mount(LayananPemilihan $layanan): void
    {
        $this->pemilihan = $layanan->ringkasanPemilihan();
        $this->kandidat = $layanan->daftarKandidat();
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 sm:pt-10 pb-24">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-4xl font-black leading-none sm:text-6xl">Kenali Pasangan Calon</h1>
                <p class="mt-3 max-w-xl text-hijau-50">
                    {{ $pemilihan['judul'] ?? 'Pemilihan Ketua OSIM' }} — baca profil, visi misi, dan materi kampanye sebelum menentukan pilihan.
                </p>
            </div>
            @if (($pemilihan['status'] ?? null) === 'berjalan')
                <x-tombol-memilih class="shrink-0" />
            @endif
        </div>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16">
        @if (empty($kandidat))
            <section class="flex flex-col items-center rounded-[14px] bg-white px-6 py-14 text-center shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis" role="status">
                <flux:icon.user-group class="size-10 text-hijau-300" />
                <h2 class="font-display mt-3 text-2xl font-black text-tinta">Belum ada pasangan calon</h2>
                <p class="mt-1 max-w-md text-tinta-2">Panitia belum mengumumkan pasangan calon. Pantau beranda untuk kabar terbaru.</p>
            </section>
        @else
            <section aria-label="Daftar pasangan calon" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($kandidat as $calon)
                    <x-kartu-kandidat :calon="$calon" wire:key="kandidat-{{ $calon['id'] }}">
                        @if (! empty($calon['visi']))
                            <x-osim.label>Visi</x-osim.label>
                            <p class="mt-1 line-clamp-3 text-tinta">{{ $calon['visi'] }}</p>
                        @endif

                        <div class="mt-auto pt-4">
                            <a href="{{ route('kandidat.profil', $calon['id']) }}" wire:navigate
                               class="flex w-full items-center justify-center gap-2 rounded-xl bg-hijau-600 px-4 py-3 font-bold text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)] hover:bg-hijau-700 active:translate-y-0.5">
                                Lihat profil &amp; kampanye <flux:icon.arrow-right variant="micro" />
                            </a>
                        </div>
                    </x-kartu-kandidat>
                @endforeach
            </section>
        @endif
    </x-osim.wadah>
</div>
