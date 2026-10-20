{{-- Isi dialog hanya-lihat untuk periode arsip. --}}
<div class="flex flex-col gap-4">
    <p class="flex items-center gap-2 rounded-lg bg-emas-300/40 px-3 py-2 text-sm font-semibold text-tinta ring-1 ring-garis">
        <x-filament::icon icon="heroicon-m-lock-closed" class="size-4 shrink-0" />
        Periode arsip hanya bisa dilihat. Datanya tidak tercampur dengan periode berjalan.
    </p>
    <dl class="grid grid-cols-2 gap-3 text-sm">
        <div class="col-span-2">
            <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-tinta-2">Masa jabatan</dt>
            <dd class="font-bold text-tinta">{{ $periode->rentang() }}</dd>
        </div>
        @foreach (['members_count' => 'Pengurus', 'candidates_count' => 'Kandidat pemilihan'] as $kunci => $label)
            <div class="rounded-lg bg-hijau-50 px-3 py-2 ring-1 ring-garis">
                <dt class="text-xs font-semibold text-tinta-2">{{ $label }}</dt>
                <dd class="text-xl font-black tabular-nums text-tinta">{{ $periode->{$kunci} }}</dd>
            </div>
        @endforeach
    </dl>
</div>
