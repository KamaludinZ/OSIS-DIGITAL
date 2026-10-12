<?php

use App\Services\LayananNotifikasi;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Pemberitahuan')] class extends Component {
    public function with(): array
    {
        return ['jenis' => LayananNotifikasi::JENIS];
    }
}; ?>

{{--
    Pusat pemberitahuan. Daftar dan status "dibaca" dikelola Alpine store
    `notif` (resources/js/app.js) yang diisi umpan publik dari layout.
--}}
<div x-data="{ tab: 'semua', atur: location.hash === '#atur' }" x-init="$watch('atur', (buka) => history.replaceState(null, '', buka ? '#atur' : location.pathname))">
    <section class="osim-guilloche bg-hijau-700 text-white">
        <x-osim.wadah class="pb-8 pt-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="font-display text-4xl font-black leading-none sm:text-5xl">Pemberitahuan</h1>
                    <p class="mt-2 max-w-xl text-hijau-50">Pengumuman baru, pengingat pemilihan, dan agenda OSIM. Tanpa akun: tanda sudah dibaca tersimpan di perangkat ini.</p>
                </div>
                <button type="button" x-on:click="atur = ! atur" x-bind:aria-expanded="atur" aria-controls="atur-pemberitahuan"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-bold hover:bg-white/20">
                    <flux:icon.adjustments-horizontal variant="micro" /> Atur pemberitahuan
                </button>
            </div>
        </x-osim.wadah>
    </section>

    <x-osim.wadah class="flex flex-col gap-5 py-8">
        {{-- Preferensi --}}
        <section id="atur-pemberitahuan" x-cloak x-show="atur" x-transition.opacity aria-labelledby="judul-atur"
                 class="rounded-[14px] bg-white p-5 ring-1 ring-garis">
            <h2 id="judul-atur" class="font-display text-xl font-black text-tinta">Jenis yang ingin kamu terima</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                @foreach ($jenis as $kunci => [$label, $ikon])
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-hijau-50 px-4 py-3 ring-1 ring-garis has-[:checked]:ring-2 has-[:checked]:ring-hijau-600">
                        <input type="checkbox" class="size-5 accent-hijau-600"
                               x-bind:checked="$store.notif.pref['{{ $kunci }}'] !== false"
                               x-on:change="$store.notif.ubahPref('{{ $kunci }}', $event.target.checked)" />
                        <flux:icon :name="$ikon" variant="mini" class="text-hijau-700" />
                        <span class="text-sm font-bold text-tinta">{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-dashed border-garis pt-4 text-sm">
                <template x-if="$store.notif.izinPerangkat === 'default'">
                    <button type="button" x-on:click="$store.notif.mintaIzin()" class="inline-flex items-center gap-2 rounded-lg bg-hijau-600 px-4 py-2 font-bold text-white hover:bg-hijau-700">
                        <flux:icon.bell-alert variant="micro" /> Tampilkan juga di layar perangkat
                    </button>
                </template>
                <p class="text-tinta-2" x-show="$store.notif.izinPerangkat === 'granted'">Pemberitahuan juga muncul di layar perangkat ini selama situs OSIM terbuka.</p>
                <p class="text-tinta-2" x-show="$store.notif.izinPerangkat === 'denied'">Pemberitahuan layar diblokir di peramban ini. Ubah lewat pengaturan situs bila ingin mengaktifkan.</p>
                <p class="text-tinta-2" x-show="$store.notif.izinPerangkat === 'default'">Opsional. Hanya untuk perangkat pribadimu, bukan komputer lab.</p>
            </div>
        </section>

        {{-- Tab & aksi --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-1.5" role="tablist" aria-label="Saring pemberitahuan">
                <button type="button" role="tab" x-on:click="tab = 'semua'" x-bind:aria-selected="tab === 'semua'"
                        x-bind:class="tab === 'semua' ? 'bg-hijau-600 text-white' : 'bg-white text-hijau-800 ring-1 ring-garis hover:bg-hijau-50'"
                        class="rounded-full px-4 py-2 text-sm font-bold">Semua</button>
                <button type="button" role="tab" x-on:click="tab = 'baru'" x-bind:aria-selected="tab === 'baru'"
                        x-bind:class="tab === 'baru' ? 'bg-hijau-600 text-white' : 'bg-white text-hijau-800 ring-1 ring-garis hover:bg-hijau-50'"
                        class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-bold">
                    Belum dibaca
                    <span x-show="$store.notif.belumDibaca" x-text="$store.notif.belumDibaca" class="angka rounded-full bg-emas-400 px-1.5 text-xs font-black text-hijau-950"></span>
                </button>
            </div>
            <button type="button" x-on:click="$store.notif.tandaiSemua()" x-bind:disabled="! $store.notif.belumDibaca"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-bold text-hijau-700 hover:bg-hijau-100 disabled:opacity-40">
                <flux:icon.check variant="micro" /> Tandai semua dibaca
            </button>
        </div>

        {{-- Daftar --}}
        <ol class="flex flex-col gap-3" aria-live="polite">
            <template x-for="n in $store.notif.daftar.filter((n) => tab === 'semua' || ! $store.notif.sudahDibaca(n.id))" x-bind:key="n.id">
                <li>
                    <a x-bind:href="n.tautan" x-on:click="$store.notif.tandai(n.id)" wire:navigate
                       x-bind:class="$store.notif.sudahDibaca(n.id) ? 'bg-white ring-garis' : 'bg-white ring-hijau-300 shadow-[0_6px_18px_-12px_rgb(4_41_26/0.5)]'"
                       class="group flex gap-4 rounded-[14px] p-4 ring-1 transition hover:ring-hijau-500 sm:p-5">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl"
                              x-bind:class="n.penting ? 'bg-emas-400 text-hijau-950' : 'bg-hijau-100 text-hijau-700'">
                            @foreach ($jenis as $kunci => [$label, $ikon])
                                <span x-show="n.jenis === '{{ $kunci }}'"><flux:icon :name="$ikon" variant="mini" /></span>
                            @endforeach
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-x-2 text-xs font-semibold text-tinta-2">
                                <span x-text="{ pengumuman: 'Pengumuman', pemilihan: 'Pemilihan', kegiatan: 'Agenda' }[n.jenis]" class="font-sempit font-bold uppercase tracking-wider text-hijau-700"></span>
                                <span aria-hidden="true">·</span>
                                <time x-bind:datetime="n.waktu" x-text="new Date(n.waktu.replace(' ', 'T')).toLocaleString('id-ID', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' })"></time>
                                <span x-show="! $store.notif.sudahDibaca(n.id)" class="ml-auto flex items-center gap-1 text-hijau-700"><span class="size-2 rounded-full bg-hijau-600"></span> Baru</span>
                            </span>
                            <span class="mt-1 block font-bold leading-snug text-tinta group-hover:text-hijau-800" x-text="n.judul"></span>
                            <span class="mt-0.5 block text-sm text-tinta-2" x-text="n.isi"></span>
                        </span>
                    </a>
                </li>
            </template>
        </ol>

        <div x-cloak x-show="! $store.notif.daftar.filter((n) => tab === 'semua' || ! $store.notif.sudahDibaca(n.id)).length"
             class="flex flex-col items-center rounded-[14px] border-2 border-dashed border-garis px-6 py-12 text-center" role="status">
            <flux:icon.bell-slash class="size-10 text-hijau-300" />
            <p class="mt-2 font-bold text-tinta" x-text="tab === 'baru' ? 'Semua sudah kamu baca' : 'Belum ada pemberitahuan'"></p>
            <p class="mt-1 max-w-sm text-sm text-tinta-2">Pengumuman baru, pengingat pemilihan, dan agenda sepekan ke depan akan muncul di sini.</p>
        </div>
    </x-osim.wadah>
</div>
