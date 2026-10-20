{{-- $modePemilihan, $menu, $bilikDibuka disediakan App\View\Composers\NavigasiPublik. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="#0f7a3d" />
        <title>{{ isset($title) ? $title.' · ' : '' }}OSIM MTsN 2 Kota Malang</title>
        <link rel="icon" href="/favicon.png" type="image/png" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="osim flex min-h-screen flex-col antialiased"
          x-data x-init="$store.notif.akunBelum = {{ auth()->user()?->unreadNotifications()->count() ?? 0 }}; $store.notif.ambil('{{ route('notifikasi.umpan') }}'); $store.mode.pantau({{ $modePemilihan ? 'true' : 'false' }}, '{{ route('mode-publik') }}')">
        {{-- Pemberitahuan menu berganti karena mode pemilihan diubah panitia --}}
        <div x-cloak x-show="$store.mode.berubah" x-transition.opacity role="status"
             class="fixed inset-x-4 top-20 z-50 mx-auto flex max-w-md items-center gap-3 rounded-xl bg-hijau-900 px-4 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_-12px_rgb(4_41_26/0.6)] print:hidden">
            <flux:icon.arrow-path variant="mini" class="shrink-0 animate-spin text-emas-300 motion-reduce:animate-none" />
            <span x-text="$store.mode.pemilihan ? 'Masa pemilihan dimulai. Menu diperbarui…' : 'Masa pemilihan selesai. Menu diperbarui…'">Menu diperbarui…</span>
        </div>
        <a href="#isi" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-emas-400 focus:px-4 focus:py-2 focus:font-semibold focus:text-hijau-950">Langsung ke isi</a>

        {{-- Kepala: pita hijau madrasah --}}
        <header class="osim-guilloche bg-hijau-600 text-white print:hidden">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5" wire:navigate>
                    <img src="/images/brand/logo-mtsn2.png" srcset="/images/brand/logo-mtsn2@2x.png 2x" alt="Logo MTsN 2 Kota Malang" width="40" height="40" class="size-10 shrink-0 drop-shadow-sm" />
                    <span class="min-w-0 leading-none">
                        <span class="font-display block text-lg font-black tracking-tight">OSIM</span>
                        <span class="block truncate text-xs font-medium text-hijau-100">MTsN 2 Kota Malang</span>
                    </span>
                </a>

                <nav aria-label="Menu utama" class="osim-menu-desktop ml-6 hidden items-center gap-1 lg:flex">
                    @foreach ($menu as $item)
                        @php($aktif = $item['aktif'])
                        <a href="{{ $item['url'] }}" wire:navigate @if ($aktif) aria-current="page" @endif
                           @class([
                               'flex items-center gap-2 whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition',
                               'bg-emas-400 text-hijau-950 shadow-[0_3px_10px_-3px_rgb(0_0_0/0.15)]' => $aktif,
                               'text-hijau-50 hover:bg-white/10' => ! $aktif,
                           ])>
                            <flux:icon :name="$item['ikon']" variant="micro" />
                            {{ $item['label'] }}
                            @if ($bilikDibuka && $item['rute'] === 'bilik-suara')
                                <span class="osim-penanda-dibuka relative flex size-2" title="Bilik suara sedang dibuka">
                                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-emas-300 opacity-75 motion-reduce:hidden"></span>
                                    <span class="relative inline-flex size-2 rounded-full {{ $aktif ? 'bg-hijau-700' : 'bg-emas-300' }}"></span>
                                </span>
                                <span class="sr-only">(sedang dibuka)</span>
                            @endif
                        </a>
                    @endforeach
                </nav>

                <div class="ml-auto flex items-center gap-1.5">
                    <a href="{{ route('notifikasi') }}" wire:navigate
                       x-bind:aria-label="'Pemberitahuan' + ($store.notif.totalBelum ? ', ' + $store.notif.totalBelum + ' belum dibaca' : '')"
                       aria-label="Pemberitahuan"
                       @class(['relative flex size-10 items-center justify-center rounded-full transition', 'bg-emas-400 text-hijau-950' => request()->routeIs('notifikasi'), 'text-white hover:bg-white/10' => ! request()->routeIs('notifikasi')])>
                        <flux:icon.bell variant="mini" />
                        <span x-cloak x-show="$store.notif.totalBelum > 0" x-text="$store.notif.totalBelum > 9 ? '9+' : $store.notif.totalBelum"
                              class="angka absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-emas-400 px-1 text-[11px] font-black text-hijau-950 ring-2 ring-hijau-600"></span>
                    </a>
                    <a href="{{ route('cari') }}" wire:navigate aria-label="Cari kabar OSIM"
                       @class(['flex size-10 items-center justify-center rounded-full transition', 'bg-emas-400 text-hijau-950' => request()->routeIs('cari'), 'text-white hover:bg-white/10' => ! request()->routeIs('cari')])>
                        <flux:icon.magnifying-glass variant="mini" />
                    </a>

                    @auth
                        <flux:dropdown position="bottom" align="end">
                            <button type="button" class="flex items-center gap-2 rounded-full bg-white/10 py-1 pl-1 pr-3 text-sm font-semibold hover:bg-white/20">
                                <span class="flex size-8 items-center justify-center rounded-full bg-emas-400 text-xs font-black text-hijau-950">{{ auth()->user()->initials() }}</span>
                                <span class="hidden max-w-32 truncate sm:block">{{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</span>
                            </button>
                            <flux:menu>
                                <div class="px-2 pb-2 pt-1.5">
                                    <div class="text-xs text-tinta-2">Masuk sebagai</div>
                                    <div class="max-w-56 truncate text-sm font-bold text-tinta">{{ auth()->user()->name }}</div>
                                    <div class="text-xs font-semibold text-hijau-700">{{ auth()->user()->role->label() }}</div>
                                </div>
                                <flux:menu.separator />
                                {{-- Tanya jawab kandidat hanya terbuka di masa pemilihan --}}
                                @if ($modePemilihan && auth()->user()->role === \App\Enums\Peran::Kandidat && auth()->user()->candidate_id)
                                    <flux:menu.item href="{{ route('kandidat.profil', ['id' => auth()->user()->candidate_id, 'tab' => 'tanya-jawab']) }}" icon="chat-bubble-left-right" wire:navigate>Tanggapi pertanyaan</flux:menu.item>
                                @endif
                                @if (auth()->user()->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                                    <flux:menu.item href="/admin" icon="building-office-2">Ruang OSIM</flux:menu.item>
                                @endif
                                @can('kelola-publikasi')
                                    <flux:menu.item href="{{ route('kelola.sosmed') }}" icon="play-circle" wire:navigate>Kelola media sosial</flux:menu.item>
                                @endcan
                                <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>Pengaturan akun</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">Keluar</flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="rounded-full bg-white px-4 py-2 text-sm font-bold text-hijau-800 shadow-[0_3px_10px_-3px_rgb(0_0_0/0.15)] transition hover:bg-hijau-50 whitespace-nowrap">
                            Masuk<span class="hidden sm:inline"> pengurus</span>
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        @if (session('status'))
            {{-- Pemberitahuan singkat (mis. sesudah keluar akun) --}}
            <div x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity
                 class="fixed inset-x-4 top-20 z-50 mx-auto flex max-w-md items-center gap-3 rounded-xl bg-hijau-900 px-4 py-3 text-sm font-semibold text-white shadow-[0_14px_30px_-12px_rgb(4_41_26/0.6)]" role="status">
                <flux:icon.check-circle variant="mini" class="shrink-0 text-emas-300" />
                <span class="flex-1">{{ session('status') }}</span>
                <button type="button" x-on:click="tampil = false" class="rounded-md p-1 hover:bg-white/10" aria-label="Tutup pemberitahuan"><flux:icon.x-mark variant="micro" /></button>
            </div>
        @endif

        <main id="isi" class="flex-1 pb-28 lg:pb-16">
            {{ $slot }}
        </main>

        <footer class="hidden border-t border-garis bg-white py-6 text-sm text-tinta-2 lg:block print:hidden">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6">
                <span>OSIM MTsN 2 Kota Malang · <em>Milenial Religius, Berbudaya Lingkungan</em></span>
                <x-akun-osim :akun="$akunSosmed" class="ml-auto" />
                <span class="font-arab text-base text-hijau-700" lang="ar" dir="rtl">اِقْرَأْ</span>
            </div>
        </footer>

        {{-- Tab bar bawah untuk HP --}}
        <nav aria-label="Menu utama" class="osim-menu-ponsel fixed inset-x-0 bottom-0 z-40 border-t border-garis bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur lg:hidden print:hidden">
            <ul @class(['mx-auto grid max-w-md', 'grid-cols-3' => count($menu) === 3, 'grid-cols-4' => count($menu) === 4])>
                @foreach ($menu as $item)
                    @php($aktif = $item['aktif'])
                    <li>
                        <a href="{{ $item['url'] }}" wire:navigate @if ($aktif) aria-current="page" @endif
                           class="flex min-w-0 flex-col items-center gap-1 px-1 pb-2 pt-2.5 text-[11px] font-semibold {{ $aktif ? 'text-hijau-800' : 'text-tinta-2' }}">
                            <span @class(['relative flex h-7 w-12 items-center justify-center rounded-full transition', 'bg-emas-400 text-hijau-950' => $aktif])>
                                <flux:icon :name="$item['ikon']" variant="{{ $aktif ? 'solid' : 'outline' }}" class="size-5" />
                                @if ($bilikDibuka && $item['rute'] === 'bilik-suara')
                                    <span class="absolute right-2 top-0.5 size-2 rounded-full bg-cap ring-2 ring-white" aria-hidden="true"></span>
                                    <span class="sr-only">(sedang dibuka)</span>
                                @endif
                            </span>
                            <span class="max-w-full truncate">{{ $item['label_pendek'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @fluxScripts
    </body>
</html>
