<x-filament-panels::page>
    @php
        $statusLabel = ['jalan' => ['Sedang berjalan', 'bg-emas-400 text-hijau-950'], 'rencana' => ['Direncanakan', 'bg-white text-tinta-2 ring-1 ring-garis'], 'selesai' => ['Selesai', 'bg-hijau-600 text-white']];
    @endphp

    {{-- Papan angka --}}
    <dl class="grid grid-cols-2 overflow-hidden rounded-[14px] bg-hijau-950 text-white lg:grid-cols-4">
        @foreach ($angka as [$label, $nilai])
            <div class="flex items-center justify-between gap-3 border-white/10 px-5 py-4 [&:not(:last-child)]:border-r max-lg:[&:nth-child(2)]:border-r-0 max-lg:[&:nth-child(-n+2)]:border-b">
                <dt class="text-sm font-semibold text-hijau-200">{{ $label }}</dt>
                <dd class="rounded-md bg-hijau-900 px-3 py-1 text-2xl font-black tabular-nums text-emas-300" style="font-family: var(--font-display); font-variation-settings: 'wdth' 75">{{ $nilai }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Pengurus inti --}}
        <x-filament::section heading="Pengurus inti" class="xl:col-span-2">
            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach ($inti as $p)
                    <li class="flex items-center gap-3 rounded-xl bg-hijau-50 p-3 ring-1 ring-garis">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-hijau-600 text-sm font-black text-white">{{ \Illuminate\Support\Str::of($p['nama'])->explode(' ')->map(fn ($k) => mb_substr($k, 0, 1))->take(2)->implode('') }}</span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-tinta-2">{{ $p['jabatan'] }}</p>
                            <p class="truncate font-bold text-tinta">{{ $p['nama'] }}</p>
                            <p class="text-sm text-tinta-2">Kelas {{ $p['kelas'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        {{-- Jadwal kegiatan --}}
        <x-filament::section heading="Jadwal terdekat">
            <ol class="divide-y divide-dashed divide-garis">
                @foreach ($jadwal as $j)
                    @php($t = \Illuminate\Support\Carbon::parse($j['tanggal']))
                    <li class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                        <span class="flex h-12 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-hijau-50 ring-1 ring-garis">
                            <span class="text-lg font-black leading-none text-hijau-700 tabular-nums">{{ $t->format('j') }}</span>
                            <span class="text-[10px] font-bold uppercase text-tinta-2">{{ $t->translatedFormat('M') }}</span>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-bold text-tinta">{{ $j['nama'] }}</p>
                            <p class="truncate text-sm text-tinta-2">{{ $j['tempat'] }} · {{ $j['divisi'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </x-filament::section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Divisi --}}
        <x-filament::section heading="Divisi" :description="count($divisi).' divisi aktif'">
            <ul class="divide-y divide-dashed divide-garis">
                @foreach ($divisi as $d)
                    <li class="flex items-center justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-tinta">{{ $d['nama'] }}</p>
                            <p class="truncate text-sm text-tinta-2">Koordinator: {{ $d['koordinator'] }}</p>
                        </div>
                        <div class="flex shrink-0 gap-2 text-xs font-bold tabular-nums">
                            <span class="rounded-md bg-hijau-100 px-2 py-1 text-hijau-900">{{ $d['anggota'] }} anggota</span>
                            <span class="rounded-md bg-emas-300/60 px-2 py-1 text-hijau-950">{{ $d['program'] }} program</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-filament::section>

        {{-- Program kerja per status --}}
        <x-filament::section heading="Program kerja">
            <div class="flex flex-col gap-4">
                @foreach (['jalan', 'rencana', 'selesai'] as $status)
                    @continue(! isset($program[$status]))
                    <div>
                        <p class="mb-2 inline-block rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wider {{ $statusLabel[$status][1] }}">{{ $statusLabel[$status][0] }} · {{ count($program[$status]) }}</p>
                        <ul class="flex flex-col gap-1.5">
                            @foreach ($program[$status] as $p)
                                <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 ring-1 ring-garis">
                                    <span class="font-semibold text-tinta">{{ $p['nama'] }}</span>
                                    <span class="shrink-0 text-sm text-tinta-2">{{ $p['divisi'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
