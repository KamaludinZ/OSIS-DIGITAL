@props(['jadwal' => []])

@php
    use Illuminate\Support\Carbon;

    // Urutkan berdasarkan tanggal + jam; tandai yang sudah lewat.
    $daftar = collect($jadwal)
        ->map(function ($j) {
            $mulai = Carbon::parse($j['tanggal'].' '.$j['waktu']);

            return $j + ['mulai' => $mulai, 'lewat' => $mulai->isPast() && ! $mulai->isToday(), 'hari_ini' => $mulai->isToday()];
        })
        ->sortBy('mulai')
        ->values();
    $mendatang = $daftar->firstWhere('lewat', false);
@endphp

<section {{ $attributes->class('flex flex-col gap-3') }} aria-labelledby="judul-jadwal">
    <h2 id="judul-jadwal" class="font-display text-2xl font-black text-tinta flex items-center gap-2">
        <flux:icon.calendar-days variant="mini" class="text-hijau-300" /> Jadwal Kampanye
    </h2>

    @if ($daftar->isEmpty())
        <div class="flex flex-col items-center rounded-[14px] border border-2 border-dashed border-garis px-6 py-12 text-center" role="status">
            <flux:icon.calendar-days class="size-10 text-hijau-300" />
            <p class="text-tinta-2 mt-2">Belum ada jadwal kampanye untuk {{ \App\Support\Istilah::calon() }} ini.</p>
        </div>
    @else
        <ol class="relative flex flex-col gap-3">
            @foreach ($daftar as $item)
                <li wire:key="jadwal-{{ $item['id'] }}" @class([
                    'flex items-center gap-4 rounded-[14px] border p-4',
                    'border-garis bg-hijau-50 opacity-70' => $item['lewat'],
                    'border-hijau-600 bg-white ring-1 ring-hijau-600' => $mendatang && $item['id'] === $mendatang['id'],
                    'border-garis bg-white' => ! $item['lewat'] && ! ($mendatang && $item['id'] === $mendatang['id']),
                ])>
                    <time datetime="{{ $item['mulai']->toIso8601String() }}" class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-hijau-50">
                        <span class="text-lg font-bold leading-tight text-tinta">{{ $item['mulai']->format('j') }}</span>
                        <span class="text-xs uppercase text-tinta-2">{{ $item['mulai']->translatedFormat('M') }}</span>
                    </time>

                    <div class="min-w-0 flex-1">
                        <div @class(['font-semibold', 'text-tinta-2 line-through decoration-tinta-2' => $item['lewat'], 'text-tinta' => ! $item['lewat']])>
                            {{ $item['tempat'] }}
                        </div>
                        <p class="text-tinta-2 text-sm">{{ $item['mulai']->translatedFormat('l') }}, pukul {{ $item['mulai']->format('H:i') }} WIB</p>
                    </div>

                    @if ($item['lewat'])
                        <span class="font-sempit shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-hijau-50 text-tinta-2 ring-1 ring-garis">Sudah lewat</span>
                    @elseif ($item['hari_ini'])
                        <span class="font-sempit shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-hijau-600 text-white">Hari ini</span>
                    @elseif ($mendatang && $item['id'] === $mendatang['id'])
                        <span class="font-sempit shrink-0 rounded-md px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider bg-emas-400 text-hijau-950">Berikutnya</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif
</section>
