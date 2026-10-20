<x-filament-panels::page>
    {{-- Periode aktif --}}
    @if ($aktif)
        <section class="flex flex-col gap-4 rounded-[14px] bg-hijau-950 p-5 text-white sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-hijau-200">Periode aktif</p>
                <p class="text-3xl font-black text-emas-300" style="font-family: var(--font-display); font-variation-settings: 'wdth' 75">{{ $aktif->nama }}</p>
                <p class="text-sm text-hijau-200">{{ $aktif->rentang() }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-2 text-center">
                @foreach (['members_count' => 'Pengurus', 'candidates_count' => 'Kandidat'] as $kunci => $label)
                    <div class="rounded-lg bg-hijau-900 px-3 py-2">
                        <dd class="text-xl font-black tabular-nums text-white">{{ $aktif->{$kunci} }}</dd>
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

    {{-- Mode pemilihan menentukan menu publik; pengurus melihat statusnya di sini. --}}
    @include('filament.mode-pemilihan')

    {{-- Penanda pengurus yang masih menjabat di periode aktif --}}
    @php($menjabat = collect($pengurus)->where('is_aktif', true)->count())
    <x-filament::section heading="Status pengurus" :description="$menjabat.' dari '.count($pengurus).' akun pengurus masih menjabat. Hanya pengurus menjabat yang bisa menanggapi saran periode berjalan.'">
        <ul class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($pengurus as $p)
                <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 ring-1 ring-garis {{ $p['is_aktif'] ? 'bg-hijau-50' : '' }}">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-tinta">{{ $p['nama'] }}</p>
                        <p class="truncate text-sm text-tinta-2">{{ $p['email'] }}</p>
                    </div>
                    <x-filament::badge :color="$p['is_aktif'] ? 'success' : 'gray'" :icon="$p['is_aktif'] ? 'heroicon-m-check-badge' : 'heroicon-m-minus-circle'" class="shrink-0">
                        {{ $p['is_aktif'] ? 'Menjabat' : 'Tidak menjabat' }}
                    </x-filament::badge>
                </li>
            @endforeach
        </ul>
    </x-filament::section>
</x-filament-panels::page>
