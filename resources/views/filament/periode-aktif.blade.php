{{-- Penanda periode aktif di sidebar Ruang OSIM: semua data yang dikelola masuk ke periode ini. --}}
@php
    $aktif = \App\Support\DataTiruanPeriode::aktif();
    $tautan = \App\Filament\Pages\KelolaPeriode::canAccess() ? \App\Filament\Pages\KelolaPeriode::getUrl() : null;
@endphp
<div class="osim-periode-aktif relative mx-3 mb-2 flex items-center justify-between gap-3 rounded-lg bg-white/10 px-3 py-2 ring-1 ring-white/15 has-[a:hover]:bg-white/15">
    <span class="leading-tight">
        <span class="block text-[11px] font-bold uppercase tracking-[0.14em] text-hijau-200">Periode aktif</span>
        <span class="block text-lg font-black text-emas-300" style="font-family: var(--font-display); font-variation-settings: 'wdth' 75">{{ $aktif['nama'] ?? 'Belum ada' }}</span>
    </span>
    @if ($tautan)
        <a href="{{ $tautan }}" class="absolute inset-0 rounded-lg" aria-label="Kelola periode OSIM"></a>
        <x-filament::icon icon="heroicon-m-chevron-right" class="size-4 text-hijau-200" />
    @endif
</div>
