@props(['grup' => []])

@php
    $tabPertama = collect($grup)->filter(fn ($g) => count($g['konten']) > 0)->keys()->first() ?? 'youtube';
@endphp

{{--
    Konten media sosial OSIM per platform. Tiap kartu setinggi sama;
    lebarnya mengikuti orientasi (potret 9:16, lanskap 16:9).
--}}
<div {{ $attributes }} x-data="{ tab: @js($tabPertama) }">
    <div role="tablist" aria-label="Pilih media sosial" class="-mx-4 flex gap-1.5 overflow-x-auto px-4 [mask-image:linear-gradient(to_right,black_88%,transparent)] sm:mx-0 sm:px-0 sm:[mask-image:none]">
        @foreach ($grup as $kunci => $g)
            <button type="button" role="tab" id="tab-sosmed-{{ $kunci }}" aria-controls="panel-sosmed-{{ $kunci }}"
                    x-on:click="tab = '{{ $kunci }}'" :aria-selected="tab === '{{ $kunci }}'" :tabindex="tab === '{{ $kunci }}' ? 0 : -1"
                    :class="tab === '{{ $kunci }}' ? 'bg-hijau-600 text-white' : 'bg-white text-hijau-800 ring-1 ring-garis hover:bg-hijau-50'"
                    class="inline-flex shrink-0 items-center gap-2 whitespace-nowrap rounded-t-xl px-3.5 pb-2.5 pt-3 text-sm font-bold transition sm:px-4">
                <x-osim.ikon-sosmed :platform="$kunci" class="size-4" />
                {{ $g['label'] }}
                <span class="angka hidden rounded-full bg-black/10 px-1.5 text-xs sm:inline">{{ count($g['konten']) }}</span>
            </button>
        @endforeach
    </div>

    @foreach ($grup as $kunci => $g)
        <div role="tabpanel" id="panel-sosmed-{{ $kunci }}" aria-labelledby="tab-sosmed-{{ $kunci }}" x-show="tab === '{{ $kunci }}'" @if ($kunci !== $tabPertama) x-cloak @endif
             class="w-fit max-w-full rounded-[14px] rounded-tl-none bg-hijau-600 p-3 sm:p-4">
            @if (empty($g['konten']))
                <p class="px-3 py-10 text-center text-hijau-50">Belum ada konten {{ $g['label'] }} dari OSIM.</p>
            @else
                <ul class="flex snap-x snap-mandatory items-start gap-3 overflow-x-auto pb-2 [--tinggi:17rem] [scrollbar-color:var(--color-hijau-300)_transparent] sm:[--tinggi:20rem]">
                    @foreach ($g['konten'] as $item)
                        @php($lanskap = $item['orientasi'] === 'lanskap')
                        <li wire:key="sosmed-{{ $item['id'] }}" class="shrink-0 snap-start"
                            style="width: {{ $lanskap ? 'min(calc(var(--tinggi) * 16 / 9), 82vw)' : 'calc(var(--tinggi) * 9 / 16)' }}">
                            <a href="{{ route('sosmed.buka', $item['id']) }}" target="_blank" rel="noopener noreferrer"
                               class="group relative block {{ $lanskap ? 'aspect-video max-h-[var(--tinggi)] w-full' : 'h-[var(--tinggi)]' }} overflow-hidden rounded-xl bg-hijau-900 ring-1 ring-white/10 focus-visible:ring-4 focus-visible:ring-emas-400">
                                <img src="{{ $item['thumbnail'] }}" alt="" loading="lazy"
                                     class="size-full object-cover transition duration-500 group-hover:scale-[1.04]" />

                                <span class="absolute inset-x-2 top-2 flex items-start justify-between gap-2">
                                    <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-white/95 px-2 py-1 text-[11px] font-bold text-hijau-900">
                                        <x-osim.ikon-sosmed :platform="$kunci" class="size-3.5" /> {{ $g['label'] }}
                                    </span>
                                    @if ($item['contoh'])
                                        <span class="font-sempit rounded-md bg-emas-400 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-hijau-950">Contoh</span>
                                    @endif
                                </span>

                                <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                    <span class="flex size-12 items-center justify-center rounded-full bg-hijau-950/55 text-white backdrop-blur-sm transition group-hover:scale-110 group-hover:bg-emas-400 group-hover:text-hijau-950">
                                        <flux:icon.play variant="mini" class="size-6 translate-x-0.5" />
                                    </span>
                                </span>

                                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-hijau-950 via-hijau-950/80 to-transparent px-3 pb-3 pt-10">
                                    <span class="line-clamp-2 text-sm font-bold leading-snug text-white">{{ $item['judul'] }}</span>
                                    <span class="angka mt-1 flex items-center gap-1 text-xs font-semibold text-emas-300">
                                        <flux:icon.eye variant="micro" />
                                        {{ number_format($item['jumlah_klik'], 0, ',', '.') }} kali dilihat
                                    </span>
                                </span>
                                <span class="sr-only">Buka di {{ $g['label'] }} (tab baru)</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach
</div>
