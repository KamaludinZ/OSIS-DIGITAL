@props(['kandidat' => [], 'suaraMasuk' => 0, 'terpilih' => null])

@php
    $baris = collect($kandidat)->sortBy('nomor_urut')->values();
    $persen = fn (int $n) => $suaraMasuk > 0 ? round($n / $suaraMasuk * 100, 1) : 0;
    $format = fn (float $p) => str_replace('.', ',', (string) $p).'%';
@endphp

{{--
    Papan perolehan suara: satu baris per kandidat dengan sel tetap
    (nomor, nama + batang, suara, persen). Batang = persen dari suara masuk.
--}}
<figure {{ $attributes->class('flex flex-col gap-4') }}>
    <ol class="flex flex-col gap-2" role="list">
        @foreach ($baris as $calon)
            @php($p = $persen($calon['suara']))
            <li wire:key="grafik-{{ $calon['id'] }}"
                @class(['grid grid-cols-[3rem_1fr_auto] items-center gap-3 rounded-xl p-2.5 pr-4 sm:grid-cols-[3.5rem_1fr_7rem_5rem] sm:gap-4', 'bg-emas-200/70 ring-2 ring-emas-400' => $terpilih === $calon['id'], 'bg-hijau-50 ring-1 ring-garis' => $terpilih !== $calon['id']])
                aria-label="Nomor {{ $calon['nomor_urut'] }}: {{ number_format($calon['suara'], 0, ',', '.') }} suara, {{ $format($p) }}">
                <span class="font-display angka flex size-12 items-center justify-center rounded-lg bg-hijau-900 text-2xl font-black text-emas-300 sm:size-14 sm:text-3xl">{{ $calon['nomor_urut'] }}</span>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold leading-tight text-tinta">{{ ($calon['nama_tampil'] ?? trim($calon['nama_ketua'].(filled($calon['nama_wakil'] ?? null) ? ' & '.$calon['nama_wakil'] : ''))) }}</span>
                        @if ($terpilih === $calon['id'])
                            <span class="font-sempit inline-flex items-center gap-1 rounded-md bg-emas-400 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-hijau-950"><flux:icon.trophy variant="micro" /> Terpilih</span>
                        @endif
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white ring-1 ring-garis" aria-hidden="true">
                        <div class="h-full rounded-full bg-[var(--color-grafik)] transition-[width] duration-700 ease-out" style="width: max({{ $p }}%, 2px)"></div>
                    </div>
                    <span class="mt-1 block text-sm tabular-nums text-tinta-2 sm:hidden">{{ number_format($calon['suara'], 0, ',', '.') }} suara</span>
                </div>

                <span class="angka hidden text-right font-semibold text-tinta-2 sm:block">{{ number_format($calon['suara'], 0, ',', '.') }} suara</span>
                <span class="font-display angka text-right text-2xl font-black text-hijau-800">{{ $format($p) }}</span>
            </li>
        @endforeach
    </ol>

    <figcaption class="text-sm text-tinta-2">
        @if ($suaraMasuk > 0)
            Persentase dari {{ number_format($suaraMasuk, 0, ',', '.') }} suara masuk.
        @else
            Belum ada suara masuk.
        @endif
    </figcaption>

    {{-- Tampilan tabel (aksesibilitas) --}}
    <details class="group rounded-lg border border-garis text-sm">
        <summary class="cursor-pointer select-none px-4 py-2 font-medium text-tinta">Lihat sebagai tabel</summary>
        <table class="w-full border-t border-garis">
            <thead class="text-left text-tinta-2">
                <tr>
                    <th class="px-4 py-2 font-medium">No.</th>
                    <th class="px-4 py-2 font-medium">{{ ucfirst(\App\Support\Istilah::calon()) }}</th>
                    <th class="px-4 py-2 text-right font-medium">Suara</th>
                    <th class="px-4 py-2 text-right font-medium">Persentase</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-garis tabular-nums">
                @foreach ($baris as $calon)
                    <tr>
                        <td class="px-4 py-2">{{ $calon['nomor_urut'] }}</td>
                        <td class="px-4 py-2">{{ ($calon['nama_tampil'] ?? trim($calon['nama_ketua'].(filled($calon['nama_wakil'] ?? null) ? ' & '.$calon['nama_wakil'] : ''))) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($calon['suara'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">{{ $format($persen($calon['suara'])) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
