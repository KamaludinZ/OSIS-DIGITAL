@props(['heading' => '', 'subheading' => ''])

@php
    $akun = auth()->user();
    $tab = [
        ['settings.profile', 'Profil', 'identification'],
        ['settings.password', 'Kata sandi', 'key'],
        ['settings.notifikasi', 'Pemberitahuan', 'bell'],
    ];
@endphp

{{-- Pengaturan akun: kartu anggota dengan tab mading, serasi dengan halaman publik. --}}
<div>
    <section class="osim-guilloche bg-hijau-700 text-white">
        <x-osim.wadah class="pt-8">
            <div class="flex flex-wrap items-center gap-4 pb-6">
                <span class="font-display flex size-14 shrink-0 items-center justify-center rounded-xl bg-emas-400 text-xl font-black text-hijau-950">{{ $akun->initials() }}</span>
                <div class="min-w-0">
                    <h1 class="font-display text-3xl font-black leading-none sm:text-4xl">Pengaturan Akun</h1>
                    <p class="mt-1.5 text-hijau-100">{{ $akun->name }} · {{ $akun->role->label() }}</p>
                </div>
                @if ($akun->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')))
                    <a href="/admin" class="ml-auto inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-bold hover:bg-white/20">
                        <flux:icon.building-office-2 variant="micro" /> Ke Ruang OSIM
                    </a>
                @elseif ($akun->candidate_id)
                    <a href="{{ route('kandidat.profil', ['id' => $akun->candidate_id, 'tab' => 'tanya-jawab']) }}" wire:navigate class="ml-auto inline-flex items-center gap-2 rounded-xl bg-white/10 px-4 py-2.5 text-sm font-bold hover:bg-white/20">
                        <flux:icon.chat-bubble-left-right variant="micro" /> Ke tanya jawab
                    </a>
                @endif
            </div>

            <nav aria-label="Bagian pengaturan" class="-mx-4 flex gap-1.5 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                @foreach ($tab as [$rute, $label, $ikon])
                    @php($aktif = request()->routeIs($rute))
                    <a href="{{ route($rute) }}" wire:navigate @if ($aktif) aria-current="page" @endif
                       @class([
                           'flex items-center gap-2 whitespace-nowrap rounded-t-xl px-4 pb-2.5 pt-3 text-sm font-bold transition',
                           'bg-hijau-50 text-hijau-900' => $aktif,
                           'bg-hijau-900/30 text-hijau-50 hover:bg-hijau-900/50' => ! $aktif,
                       ])>
                        <flux:icon :name="$ikon" variant="micro" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
        </x-osim.wadah>
    </section>

    <x-osim.wadah class="py-8">
        <div class="max-w-xl rounded-[14px] bg-white p-6 ring-1 ring-garis sm:p-8">
            <h2 class="font-display text-2xl font-black text-tinta">{{ $heading }}</h2>
            @if ($subheading)
                <p class="mt-1 text-tinta-2">{{ $subheading }}</p>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </div>
    </x-osim.wadah>
</div>
