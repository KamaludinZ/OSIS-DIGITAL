@props(['calon', 'terpilih' => false])

{{-- Kartu pasangan calon, dibentuk seperti kartu peserta madrasah. --}}
<article {{ $attributes->class([
    'relative flex flex-col overflow-hidden rounded-[14px] bg-white transition',
    'shadow-[0_2px_10px_-3px_var(--color-garis),0_10px_24px_-14px_rgb(4_41_26/0.35)] ring-1 ring-garis' => ! $terpilih,
    'shadow-[0_14px_30px_-12px_rgb(15_122_61/0.55)] ring-[3px] ring-hijau-600' => $terpilih,
]) }}>
    <h3 class="sr-only">Pasangan Calon {{ $calon['nomor_urut'] }}: {{ $calon['nama_ketua'] }} dan {{ $calon['nama_wakil'] }}</h3>

    {{-- Kepala kartu --}}
    <div @class(['flex items-center justify-between px-4 py-2', 'bg-hijau-600 text-white' => $terpilih, 'bg-hijau-100 text-hijau-800' => ! $terpilih])>
        <span class="font-sempit text-[11px] font-bold uppercase tracking-[0.16em]">Kartu Pasangan Calon</span>
        @if ($terpilih)
            <span class="flex items-center gap-1 text-xs font-bold"><flux:icon.check-circle variant="micro" /> Pilihanmu</span>
        @else
            <img src="/images/brand/logo-mtsn2.png" alt="" class="size-5 opacity-80" />
        @endif
    </div>

    <div class="flex gap-4 p-4">
        {{-- Pas foto --}}
        <x-osim.pasfoto class="w-24 shrink-0 sm:w-28" :foto="$calon['foto_url'] ?? null" alt="Foto {{ $calon['nama_ketua'] }} dan {{ $calon['nama_wakil'] }}" />

        {{-- Nomor urut raksasa --}}
        <div class="flex min-w-0 flex-1 flex-col items-center justify-center rounded-xl bg-hijau-50 ring-1 ring-garis">
            <x-osim.label class="text-[10px]">No. Urut</x-osim.label>
            <span class="font-display angka text-7xl font-black leading-none text-hijau-700 sm:text-8xl" aria-label="Nomor urut {{ $calon['nomor_urut'] }}">{{ $calon['nomor_urut'] }}</span>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-x-4 gap-y-3 px-4 pb-4">
        <div class="min-w-0">
            <dt><x-osim.label>Calon Ketua</x-osim.label></dt>
            <dd class="mt-0.5 font-bold leading-tight text-tinta">{{ $calon['nama_ketua'] }}</dd>
            <dd class="text-sm text-tinta-2">{{ $calon['kelas_ketua'] }}</dd>
        </div>
        <div class="min-w-0">
            <dt><x-osim.label>Calon Wakil</x-osim.label></dt>
            <dd class="mt-0.5 font-bold leading-tight text-tinta">{{ $calon['nama_wakil'] }}</dd>
            <dd class="text-sm text-tinta-2">{{ $calon['kelas_wakil'] }}</dd>
        </div>
    </dl>

    @if (! empty($calon['slogan']))
        <p class="mx-4 mb-4 rounded-lg bg-emas-200/60 px-3 py-2 text-sm font-semibold italic text-hijau-900">“{{ $calon['slogan'] }}”</p>
    @endif

    @if (trim($slot) !== '')
        <div class="osim-perforasi" style="--osim-takik: var(--color-hijau-50)"></div>
        <div class="flex flex-1 flex-col px-4 pb-4 pt-4">
            {{ $slot }}
        </div>
    @endif
</article>
