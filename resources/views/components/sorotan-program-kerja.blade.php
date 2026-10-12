@props(['programKerja' => []])

@php
    $unggulan = collect($programKerja)->where('unggulan', true)->values();
    $label = ['rencana' => ['Direncanakan', 'bg-white text-tinta-2 ring-1 ring-garis'], 'jalan' => ['Sedang berjalan', 'bg-emas-400 text-hijau-950'], 'selesai' => ['Selesai', 'bg-hijau-600 text-white']];
@endphp

{{-- Program kerja unggulan OSIM: baris mading bergaris, status di kolom kanan. --}}
<ol {{ $attributes->class('divide-y divide-dashed divide-garis overflow-hidden rounded-[14px] bg-white ring-1 ring-garis') }}>
    @forelse ($unggulan as $program)
        @php([$teksStatus, $kelas] = $label[$program['status']] ?? $label['rencana'])
        <li wire:key="proker-{{ $program['id'] }}" class="grid gap-3 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:gap-6">
            <div class="min-w-0">
                <h3 class="font-display text-xl font-extrabold leading-tight text-tinta">{{ $program['nama'] }}</h3>
                <p class="mt-1 text-sm text-tinta-2">{{ $program['deskripsi'] }}</p>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                    <span class="font-semibold text-tinta-2">{{ $program['divisi'] }}</span>
                    @if (! empty($program['capaian']))
                        <span class="flex items-center gap-1.5 font-bold text-hijau-800"><flux:icon.trophy variant="micro" class="text-emas-500" /> {{ $program['capaian'] }}</span>
                    @endif
                </p>
            </div>
            <span class="font-sempit w-fit shrink-0 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wider {{ $kelas }}">{{ $teksStatus }}</span>
        </li>
    @empty
        <li class="p-6 text-center text-tinta-2">Belum ada program kerja unggulan.</li>
    @endforelse
</ol>
