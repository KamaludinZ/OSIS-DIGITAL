@props(['agenda' => [], 'batas' => null])

@php
    use Illuminate\Support\Carbon;

    // Hanya kegiatan yang belum selesai, diurutkan dari yang paling dekat.
    $daftar = collect($agenda)
        ->filter(fn ($k) => Carbon::parse($k['tanggal_selesai'])->endOfDay()->isFuture())
        ->sortBy('tanggal_mulai')
        ->values();
    $daftar = $batas ? $daftar->take($batas) : $daftar;
@endphp

{{-- Papan agenda: baris tetap, yang berlangsung hari ini menyala. --}}
<ol {{ $attributes->class('divide-y divide-dashed divide-garis') }}>
    @forelse ($daftar as $kegiatan)
        @php
            $mulai = Carbon::parse($kegiatan['tanggal_mulai']);
            $selesai = Carbon::parse($kegiatan['tanggal_selesai']);
            $berlangsung = now()->between($mulai->copy()->startOfDay(), $selesai->copy()->endOfDay());
        @endphp
        <li wire:key="agenda-{{ $kegiatan['id'] }}" @class(['bg-emas-200/50' => $berlangsung])>
            <a href="{{ route('kegiatan.detail', $kegiatan['id']) }}" wire:navigate class="group flex items-start gap-3.5 px-5 py-3.5">
                <time datetime="{{ $kegiatan['tanggal_mulai'] }}" @class([
                    'flex h-14 w-12 shrink-0 flex-col items-center justify-center rounded-lg',
                    'bg-hijau-600 text-white' => $berlangsung,
                    'bg-hijau-50 text-hijau-800 ring-1 ring-garis' => ! $berlangsung,
                ])>
                    <span class="font-display angka text-xl font-black leading-none">{{ $mulai->format('j') }}</span>
                    <span class="font-sempit text-[11px] font-bold uppercase tracking-wider">{{ $mulai->translatedFormat('M') }}</span>
                </time>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-bold leading-snug text-tinta group-hover:text-hijau-700 group-hover:underline">{{ $kegiatan['nama'] }}</span>
                        @if ($berlangsung)
                            <span class="font-sempit rounded-md bg-hijau-600 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-white">Hari ini</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-sm text-tinta-2">
                        {{ $mulai->isSameDay($selesai) ? $mulai->translatedFormat('l, j F') : $mulai->translatedFormat('j').'–'.$selesai->translatedFormat('j F') }}
                        · {{ $kegiatan['tempat'] }}
                    </p>
                    <p class="text-xs font-medium text-tinta-2/80">{{ $kegiatan['divisi'] }}</p>
                </div>
            </a>
        </li>
    @empty
        <li class="px-6 py-10 text-center text-tinta-2">Belum ada kegiatan terjadwal.</li>
    @endforelse
</ol>
