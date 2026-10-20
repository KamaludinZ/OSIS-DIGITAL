<x-filament-panels::page>
    {{-- Ringkasan riwayat saran per periode; arsip tetap bisa dibaca lewat filter Periode --}}
    <section aria-label="Riwayat saran per periode" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($riwayat as $r)
            <button type="button" wire:click="lihatPeriode('{{ $r['periode'] }}')" @class([
                'flex items-center justify-between gap-3 rounded-xl px-4 py-3 text-left ring-1 transition hover:ring-hijau-600',
                'bg-hijau-50 ring-hijau-600' => $r['dipilih'],
                'bg-white ring-garis' => ! $r['dipilih'],
            ])>
                <span>
                    <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-tinta-2">{{ $r['aktif'] ? 'Periode aktif' : 'Arsip' }}</span>
                    <span class="block text-lg font-black text-tinta">{{ $r['periode'] }}</span>
                </span>
                <span class="text-right text-sm tabular-nums text-tinta-2">
                    <strong class="text-tinta">{{ $r['total'] }}</strong> saran<br>
                    {{ $r['ditanggapi'] }} ditanggapi
                </span>
            </button>
        @endforeach
    </section>

    {{ $this->table }}
</x-filament-panels::page>
