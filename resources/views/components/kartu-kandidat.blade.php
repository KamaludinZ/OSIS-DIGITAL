@props(['calon', 'terpilih' => false])

{{-- Kartu pasangan calon, dibentuk seperti kartu peserta madrasah. --}}
<article {{ $attributes->class([
    'relative flex flex-col overflow-hidden rounded-[14px] bg-white transition',
    'shadow-[0_2px_10px_-3px_var(--color-garis),0_10px_24px_-14px_rgb(4_41_26/0.35)] ring-1 ring-garis' => ! $terpilih,
    'shadow-[0_14px_30px_-12px_rgb(15_122_61/0.55)] ring-[3px] ring-hijau-600' => $terpilih,
]) }}>
    @php($berpasangan = $calon['berpasangan'] ?? filled($calon['nama_wakil'] ?? null))
    <h3 class="sr-only">{{ $berpasangan ? 'Pasangan Calon' : 'Calon Ketua' }} {{ $calon['nomor_urut'] }}: {{ $berpasangan ? $calon['nama_ketua'].' dan '.$calon['nama_wakil'] : $calon['nama_ketua'] }}</h3>

    {{-- Kepala kartu --}}
    <div @class(['flex items-center justify-between px-4 py-2', 'bg-hijau-600 text-white' => $terpilih, 'bg-hijau-100 text-hijau-800' => ! $terpilih])>
        <span class="font-sempit text-[11px] font-bold uppercase tracking-[0.16em]">{{ $berpasangan ? 'Kartu Pasangan Calon' : 'Kartu Calon Ketua' }}</span>
        @if ($terpilih)
            <span class="flex items-center gap-1 text-xs font-bold"><flux:icon.check-circle variant="micro" /> Pilihanmu</span>
        @else
            <img src="/images/brand/logo-mtsn2.png" alt="" class="size-5 opacity-80" />
        @endif
    </div>

    {{-- Foto calon (lebar) dengan nomor urut di pojok --}}
    <div class="relative p-4 pb-0">
        <x-osim.pasfoto class="aspect-[4/3] w-full" :foto="$calon['foto_url'] ?? null" alt="Foto {{ $berpasangan ? $calon['nama_ketua'].' dan '.$calon['nama_wakil'] : $calon['nama_ketua'] }}" />
        <span class="absolute left-6 top-6 flex items-baseline gap-1 rounded-lg bg-hijau-900 px-2.5 py-1 text-white shadow-[0_4px_10px_-3px_rgb(4_41_26/0.5)]" aria-label="Nomor urut {{ $calon['nomor_urut'] }}">
            <span class="font-sempit text-[10px] font-bold uppercase tracking-wider text-hijau-200">No.</span>
            <span class="font-display angka text-2xl font-black leading-none text-emas-300">{{ $calon['nomor_urut'] }}</span>
        </span>
    </div>

    <dl @class(['grid gap-x-4 gap-y-3 p-4', 'grid-cols-2' => $berpasangan])>
        <div class="min-w-0">
            <dt><x-osim.label>Calon Ketua</x-osim.label></dt>
            <dd class="mt-0.5 font-bold leading-tight text-tinta">{{ $calon['nama_ketua'] }}</dd>
            <dd class="text-sm text-tinta-2">{{ $calon['kelas_ketua'] }}</dd>
        </div>
        @if ($berpasangan)
            <div class="min-w-0">
                <dt><x-osim.label>Calon Wakil</x-osim.label></dt>
                <dd class="mt-0.5 font-bold leading-tight text-tinta">{{ $calon['nama_wakil'] }}</dd>
                <dd class="text-sm text-tinta-2">{{ $calon['kelas_wakil'] }}</dd>
            </div>
        @endif
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
