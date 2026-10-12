<x-filament-panels::page>
    @php
        $persen = fn (int $a, int $b) => $b > 0 ? round($a / $b * 100, 1) : 0;
        $total = $data['total'];
        $memilih = $data['memilih'];
    @endphp

    <div wire:poll.15s.visible class="flex flex-col gap-6">
        {{-- Papan partisipasi keseluruhan --}}
        <section class="overflow-hidden rounded-[14px] bg-hijau-950 text-white">
            <dl class="grid grid-cols-1 divide-y divide-white/10 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                @foreach ([['Pemilih berhak', $total], ['Sudah memilih', $memilih], ['Belum memilih', $total - $memilih]] as [$label, $nilai])
                    <div class="flex items-center justify-between gap-3 px-5 py-4">
                        <dt class="text-sm font-semibold text-hijau-200">{{ $label }}</dt>
                        <dd class="rounded-md bg-hijau-900 px-3 py-1 text-2xl font-black tabular-nums text-emas-300" style="font-family: var(--font-display); font-variation-settings: 'wdth' 75">{{ number_format($nilai, 0, ',', '.') }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="border-t border-white/10 px-5 py-4">
                <div class="flex justify-between text-sm font-semibold">
                    <span class="text-hijau-100">Partisipasi</span>
                    <span class="tabular-nums text-emas-300">{{ str_replace('.', ',', $persen($memilih, $total)) }}%</span>
                </div>
                <div class="mt-2 h-3 overflow-hidden rounded-full bg-hijau-900" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $persen($memilih, $total) }}" aria-label="Partisipasi keseluruhan">
                    <div class="h-full rounded-full bg-emas-400 transition-[width] duration-500" style="width: {{ $persen($memilih, $total) }}%"></div>
                </div>
            </div>
        </section>

        {{-- Per kelompok --}}
        <x-filament::section heading="Per kelas & GTK" description="Meter kuning = partisipasi di bawah 50%. Klik baris untuk melihat siapa yang belum memilih.">
            @if (empty($data['kelompok']))
                <p class="py-6 text-center text-tinta-2">Belum ada pemilih terverifikasi. Impor data di menu Data Pemilih.</p>
            @else
                <ol class="flex flex-col gap-2">
                    @foreach ($data['kelompok'] as $k)
                        @php($p = $persen($k['memilih'], $k['total']))
                        @php($nama = $belum[$k['nama']] ?? [])
                        <li x-data="{ buka: false }" wire:key="kelompok-{{ $k['nama'] }}" class="rounded-xl ring-1 ring-garis">
                            <button type="button" x-on:click="buka = ! buka" :aria-expanded="buka" @disabled(empty($nama))
                                    class="grid w-full grid-cols-[5.5rem_1fr_auto] items-center gap-3 px-3 py-2.5 text-left enabled:hover:bg-hijau-50 sm:grid-cols-[6rem_1fr_7rem_4.5rem_8.5rem]">
                                <span class="font-bold text-tinta">{{ $k['nama'] }}</span>
                                <div class="h-2.5 overflow-hidden rounded-full bg-hijau-100" aria-hidden="true">
                                    <div @class(['h-full rounded-full transition-[width] duration-500', 'bg-hijau-600' => $p >= 50, 'bg-emas-400' => $p < 50]) style="width: {{ max($p, 1) }}%"></div>
                                </div>
                                <span class="hidden text-right text-sm tabular-nums text-tinta-2 sm:block">{{ $k['memilih'] }} / {{ $k['total'] }} orang</span>
                                <span class="text-right font-black tabular-nums text-hijau-800">{{ str_replace('.', ',', $p) }}%</span>
                                <span class="hidden items-center justify-end gap-1 text-sm font-bold text-hijau-700 sm:flex">
                                    @if (empty($nama))
                                        Lengkap
                                    @else
                                        {{ count($nama) }} belum
                                        <svg class="size-4 transition" :class="buka && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                    @endif
                                </span>
                            </button>
                            @if ($nama)
                                <div x-show="buka" x-cloak class="border-t border-dashed border-garis px-3 py-3">
                                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.14em] text-tinta-2">Belum memilih · {{ $k['nama'] }}</p>
                                    <ul class="grid gap-x-6 gap-y-1 text-sm text-tinta sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($nama as $n)
                                            <li>{{ $n }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-filament::section>

        <p class="text-xs text-tinta-2">Diperbarui {{ $diperbarui->translatedFormat('j F Y, H:i:s') }} WIB. Halaman ini hanya menunjukkan siapa yang sudah memilih, tidak pernah pilihannya.</p>
    </div>
</x-filament-panels::page>
