{{-- Pratinjau bilah menu publik untuk satu mode. $aktif: mode yang dipratinjau, $sorot: sedang tampil di situs. --}}
<figure @class(['rounded-xl p-3 ring-1', 'bg-hijau-50 ring-hijau-600' => $sorot ?? false, 'bg-white ring-garis' => ! ($sorot ?? false)])>
    <figcaption class="mb-2 flex items-center justify-between gap-2 text-xs font-bold text-tinta-2">
        <span>{{ $aktif ? 'Saat mode aktif' : 'Saat mode nonaktif' }}</span>
        @if ($sorot ?? false)
            <span class="rounded-full bg-hijau-600 px-2 py-0.5 text-[10px] uppercase tracking-wider text-white">Sedang tampil</span>
        @endif
    </figcaption>
    <ul class="flex flex-wrap gap-1.5" aria-label="Menu publik {{ $aktif ? 'mode aktif' : 'mode nonaktif' }}">
        @foreach (\App\Support\MenuPublik::untuk($aktif) as $item)
            <li class="flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-tinta ring-1 ring-garis">
                <x-filament::icon :icon="'heroicon-o-'.$item['ikon']" class="size-4 text-hijau-700" />
                {{ $item['label'] }}
            </li>
        @endforeach
    </ul>
</figure>
