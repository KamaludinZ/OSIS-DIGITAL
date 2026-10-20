<x-filament-panels::page>
    {{-- Periode aktif --}}
    @if ($aktif)
        <section class="flex flex-col gap-4 rounded-[14px] bg-hijau-950 p-5 text-white sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-hijau-200">Periode aktif</p>
                <p class="text-3xl font-black text-emas-300" style="font-family: var(--font-display); font-variation-settings: 'wdth' 75">{{ $aktif['nama'] }}</p>
                <p class="text-sm text-hijau-200">{{ \App\Filament\Pages\KelolaPeriode::rentang($aktif) }}</p>
            </div>
            <dl class="grid grid-cols-3 gap-2 text-center">
                @foreach (['pengurus' => 'Pengurus', 'kandidat' => 'Kandidat', 'saran' => 'Saran'] as $kunci => $label)
                    <div class="rounded-lg bg-hijau-900 px-3 py-2">
                        <dd class="text-xl font-black tabular-nums text-white">{{ $aktif[$kunci] }}</dd>
                        <dt class="text-xs font-semibold text-hijau-200">{{ $label }}</dt>
                    </div>
                @endforeach
            </dl>
        </section>
    @else
        <section class="rounded-[14px] bg-emas-300/40 p-5 ring-1 ring-garis">
            <p class="font-bold text-tinta">Belum ada periode aktif</p>
            <p class="text-sm text-tinta-2">Aktifkan salah satu periode agar data kandidat, saran, struktur, dan media sosial punya tempat.</p>
        </section>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
