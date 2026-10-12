@props(['pengumuman' => [], 'batas' => null])

@php
    use Illuminate\Support\Carbon;

    $daftar = collect($pengumuman)->sortByDesc('tanggal')->values();
    $daftar = $batas ? $daftar->take($batas) : $daftar;
@endphp

{{-- Daftar pengumuman OSIM, terbaru di atas. --}}
<ol {{ $attributes->class('divide-y divide-dashed divide-garis') }}>
    @forelse ($daftar as $item)
        @php($tanggal = Carbon::parse($item['tanggal']))
        <li wire:key="pengumuman-{{ $item['id'] }}">
            <a href="{{ route('pengumuman.detail', $item['id']) }}" wire:navigate class="group flex gap-4 px-5 py-4 transition hover:bg-hijau-50">
                <time datetime="{{ $item['tanggal'] }}" class="flex w-11 shrink-0 flex-col items-center pt-0.5 text-center">
                    <span class="font-display angka text-2xl font-black leading-none text-hijau-700">{{ $tanggal->format('d') }}</span>
                    <span class="font-sempit text-[11px] font-bold uppercase tracking-wider text-tinta-2">{{ $tanggal->translatedFormat('M') }}</span>
                </time>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-sempit rounded-md bg-hijau-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-hijau-800">{{ $item['kategori'] }}</span>
                        @if ($loop->first && $tanggal->greaterThanOrEqualTo(now()->subDays(7)->startOfDay()))
                            <span class="font-sempit rounded-md bg-emas-400 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-hijau-950">Baru</span>
                        @endif
                    </div>
                    <h3 class="mt-1.5 font-bold leading-snug text-tinta group-hover:text-hijau-700 group-hover:underline">{{ $item['judul'] }}</h3>
                    <p class="mt-1 line-clamp-2 text-sm text-tinta-2">{{ $item['ringkasan'] }}</p>
                    <p class="mt-1.5 text-xs font-medium text-tinta-2">Oleh {{ $item['penulis'] }}</p>
                </div>
            </a>
        </li>
    @empty
        <li class="flex flex-col items-center gap-2 px-6 py-10 text-center">
            <flux:icon.megaphone class="size-8 text-hijau-300" />
            <p class="text-tinta-2">Belum ada pengumuman dari OSIM.</p>
        </li>
    @endforelse
</ol>
