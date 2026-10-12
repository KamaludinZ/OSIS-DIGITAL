<?php

use App\Services\LayananPemilihan;
use App\Services\LayananPublikasi;
use App\Services\LayananSosmed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Beranda')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public ?array $pemilihan = null;

    #[\Livewire\Attributes\Locked]

    public array $pengumuman = [];

    #[\Livewire\Attributes\Locked]

    public array $agenda = [];

    #[\Livewire\Attributes\Locked]

    public array $programKerja = [];

    #[\Livewire\Attributes\Locked]

    public array $sosmed = [];

    public function mount(LayananPemilihan $layanan, LayananPublikasi $publikasi, LayananSosmed $sosmed): void
    {
        $this->sosmed = $sosmed->perPlatform();
        $this->pemilihan = $layanan->ringkasanPemilihan();
        $this->pengumuman = $publikasi->pengumumanTerbaru(batas: 4);
        $this->agenda = $publikasi->agendaMendatang(batas: 4);
        $this->programKerja = $publikasi->programKerjaUnggulan();
    }
}; ?>

@php
    use Illuminate\Support\Carbon;

    $nama = auth()->check() ? \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') : 'Teman';
    $status = $pemilihan['status'] ?? null;
@endphp

<div>
    {{-- Pita sapaan --}}
    <x-osim.pita class="pt-6 pb-24 sm:pt-10 sm:pb-28">
        <div class="grid items-end gap-6 lg:grid-cols-[1fr_auto]">
            <div class="relative">
                <h1 class="font-display text-[clamp(1.85rem,8.4vw,3.75rem)] font-black leading-[0.95]">
                    Assalamu'alaikum,<br><span class="text-emas-300">{{ $nama }}!</span>
                </h1>
                <p class="mt-4 max-w-xl text-base text-hijau-50 sm:text-lg">
                    Semua kabar OSIM ada di sini: pengumuman, agenda, program kerja, dan pemilihan ketua OSIM.
                </p>
            </div>

            <div class="flex flex-col gap-4 lg:w-80 lg:items-end">
                <p class="font-arab hidden text-6xl leading-none text-emas-300/90 lg:block" lang="ar" aria-hidden="true">السَّلَامُ عَلَيْكُمْ</p>
                <form action="{{ route('cari') }}" method="GET" role="search" class="w-full">
                    <label for="cari-beranda" class="sr-only">Cari kabar OSIM</label>
                    <div class="flex items-center gap-2 rounded-xl bg-white px-3 shadow-[0_4px_10px_-3px_rgb(0_0_0/0.2)]">
                        <flux:icon.magnifying-glass variant="mini" class="text-hijau-600" />
                        <input id="cari-beranda" name="q" type="search" placeholder="Cari pengumuman atau kegiatan…"
                               class="h-12 w-full bg-transparent text-tinta placeholder:text-tinta-2/70 focus:outline-none" />
                    </div>
                </form>
            </div>
        </div>
    </x-osim.pita>

    <x-osim.wadah class="-mt-16 flex flex-col gap-10 sm:-mt-20">
        {{-- Kartu pemilihan menjorok dari pita --}}
        @if ($pemilihan)
            <section aria-labelledby="judul-pemilihan" class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
                <div class="flex items-center justify-between gap-3 bg-hijau-100 px-5 py-2">
                    <x-osim.label class="text-hijau-800">Kartu Pemilihan</x-osim.label>
                    @if ($status === 'berjalan')
                        <span class="flex items-center gap-1.5 text-xs font-bold text-hijau-800">
                            <span class="relative flex size-2"><span class="absolute inline-flex size-full animate-ping rounded-full bg-hijau-500 opacity-60 motion-reduce:hidden"></span><span class="relative inline-flex size-2 rounded-full bg-hijau-600"></span></span>
                            Bilik suara dibuka
                        </span>
                    @elseif ($status === 'draft')
                        <span class="text-xs font-bold text-tinta-2">Belum dibuka</span>
                    @else
                        <span class="text-xs font-bold text-tinta-2">Sudah ditutup</span>
                    @endif
                </div>

                <div class="grid sm:grid-cols-[1fr_auto]">
                    <div class="min-w-0 p-5 sm:p-6">
                        <h2 id="judul-pemilihan" class="font-display text-2xl font-extrabold leading-tight text-tinta sm:text-3xl">{{ $pemilihan['judul'] }}</h2>
                        <p class="mt-2 text-tinta-2">
                            @if ($status === 'berjalan')
                                Satu siswa, satu suara. Pilihanmu rahasia dan langsung terhitung.
                            @elseif ($status === 'draft')
                                Bilik suara belum dibuka. Kenali dulu para {{ \App\Support\Istilah::calon() }}.
                            @else
                                Bilik suara sudah ditutup. Terima kasih atas partisipasinya.
                            @endif
                        </p>
                    </div>

                    {{-- Sobekan kartu: perforasi + jam + aksi --}}
                    <div class="relative flex flex-col gap-4 border-t-2 border-dashed border-garis p-5 sm:w-80 sm:border-l-2 sm:border-t-0 sm:p-6">
                        <span class="absolute -left-3 -top-3 size-6 rounded-full bg-hijau-50" aria-hidden="true"></span>
                        <span class="absolute -right-3 -top-3 size-6 rounded-full bg-hijau-50 sm:-bottom-3 sm:-left-3 sm:right-auto sm:top-auto" aria-hidden="true"></span>
                        <dl class="grid grid-cols-2 gap-3">
                            <div>
                                <dt><x-osim.label>Dibuka</x-osim.label></dt>
                                <dd class="angka font-bold text-tinta">{{ Carbon::parse($pemilihan['mulai'])->translatedFormat('j M, H:i') }}</dd>
                            </div>
                            <div>
                                <dt><x-osim.label>Ditutup</x-osim.label></dt>
                                <dd class="angka font-bold text-tinta">{{ Carbon::parse($pemilihan['selesai'])->translatedFormat('j M, H:i') }}</dd>
                            </div>
                        </dl>

                        @if ($status === 'berjalan')
                            <x-tombol-memilih class="w-full text-lg" />
                        @elseif ($status === 'draft')
                            <a href="{{ route('kandidat.index') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white hover:bg-hijau-700">
                                Kenali kandidat <flux:icon.arrow-right variant="micro" />
                            </a>
                        @else
                            <a href="{{ route('hasil') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white hover:bg-hijau-700">
                                Lihat hasil <flux:icon.arrow-right variant="micro" />
                            </a>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        {{-- Mading: pengumuman & agenda --}}
        <div class="grid gap-x-6 gap-y-10 lg:grid-cols-3">
            <section aria-labelledby="judul-pengumuman" class="lg:col-span-2">
                <x-osim.tab id="judul-pengumuman" ikon="megaphone">Pengumuman Terbaru</x-osim.tab>
                <div class="overflow-hidden rounded-[14px] rounded-tl-none bg-white ring-1 ring-garis">
                    <x-daftar-pengumuman :pengumuman="$pengumuman" :batas="4" />
                </div>
            </section>

            <section aria-labelledby="judul-agenda">
                <x-osim.tab id="judul-agenda" warna="hijau" ikon="calendar-days">Agenda &amp; Kegiatan</x-osim.tab>
                <div class="overflow-hidden rounded-[14px] rounded-tl-none bg-white ring-1 ring-garis">
                    <x-agenda-mendatang :agenda="$agenda" :batas="4" />
                </div>
            </section>
        </div>

        {{-- Sorotan program kerja --}}
        <section aria-labelledby="judul-proker">
            <div class="mb-4 flex items-end justify-between gap-4">
                <h2 id="judul-proker" class="font-display text-2xl font-black text-tinta sm:text-3xl">Sorotan Program Kerja</h2>
            </div>
            <x-sorotan-program-kerja :program-kerja="$programKerja" />
        </section>

        {{-- Konten media sosial OSIM --}}
        @if (collect($sosmed)->sum(fn ($g) => count($g['konten'])) > 0)
            <section aria-labelledby="judul-sosmed">
                <h2 id="judul-sosmed" class="font-display mb-4 text-2xl font-black text-tinta sm:text-3xl">Dari Media Sosial OSIM</h2>
                <x-sosmed-osim :grup="$sosmed" />
            </section>
        @endif
    </x-osim.wadah>
</div>
