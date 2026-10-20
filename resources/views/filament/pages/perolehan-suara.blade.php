<x-filament-panels::page>
    <div wire:poll.15s>
        @if (! $rekap)
            <x-filament::section>
                <p class="text-sm text-tinta-2">Belum ada pemilihan.</p>
            </x-filament::section>
        @else
            @php
                $total = max(1, $rekap['suara_masuk']);
                $partisipasi = $rekap['total_pemilih'] ? round($rekap['suara_masuk'] / $rekap['total_pemilih'] * 100, 1) : 0;
                $statusLabel = ['draft' => 'Belum dibuka', 'berjalan' => 'Sedang berjalan', 'selesai' => 'Ditutup'][$rekap['status']] ?? $rekap['status'];
            @endphp

            <dl class="grid grid-cols-2 overflow-hidden rounded-[14px] bg-hijau-950 text-white lg:grid-cols-4">
                @foreach ([['Status', $statusLabel], ['Suara masuk', number_format($rekap['suara_masuk'], 0, ',', '.')], ['Partisipasi', str_replace('.', ',', $partisipasi).'%'], ['Hasil publik', $rekap['hasil_publik'] ? 'Ya' : 'Belum']] as [$label, $nilai])
                    <div class="flex items-center justify-between gap-3 border-white/10 px-5 py-4 [&:not(:last-child)]:border-r">
                        <dt class="text-sm font-semibold text-hijau-200">{{ $label }}</dt>
                        <dd class="rounded-md bg-hijau-900 px-3 py-1 text-lg font-black tabular-nums text-emas-300">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($pemenang && $rekap['status'] === 'selesai')
                <p class="mt-4 rounded-xl bg-emas-300/40 px-4 py-3 font-bold text-tinta ring-1 ring-garis">
                    @switch($pemenang['status'])
                        @case('menang') Unggul: nomor {{ $pemenang['kandidat']['nomor_urut'] }} — {{ $pemenang['kandidat']['nama_tampil'] }} ({{ $pemenang['kandidat']['suara'] }} suara). @break
                        @case('seri') Perolehan teratas seri; panitia perlu menetapkan langkah berikutnya. @break
                        @default Belum ada suara masuk.
                    @endswitch
                </p>
            @endif

            <x-filament::section :heading="$rekap['judul']" class="mt-6">
                <ol class="flex flex-col gap-3">
                    @foreach (collect($rekap['kandidat'])->sortByDesc('suara') as $k)
                        @php($persen = round($k['suara'] / $total * 100, 1))
                        <li>
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="font-bold text-tinta">No. {{ $k['nomor_urut'] }} · {{ $k['nama_tampil'] }}</span>
                                <span class="tabular-nums text-sm text-tinta-2"><strong class="text-tinta">{{ $k['suara'] }}</strong> suara · {{ str_replace('.', ',', $persen) }}%</span>
                            </div>
                            <div class="mt-1 h-3 overflow-hidden rounded-full bg-hijau-50 ring-1 ring-garis" role="img" aria-label="{{ $persen }} persen">
                                <div class="h-full rounded-full bg-hijau-600" style="width: {{ $persen }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-4 text-xs text-tinta-2">Diperbarui {{ \App\Support\Tanggal::singkat(\Illuminate\Support\Carbon::parse($rekap['diperbarui'])) }} · {{ number_format($rekap['total_pemilih'], 0, ',', '.') }} pemilih terdaftar</p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
