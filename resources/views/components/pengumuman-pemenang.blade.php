@props(['kandidat' => [], 'suaraMasuk' => 0])

@php
    $urut = collect($kandidat)->sortByDesc('suara')->values();
    $teratas = $urut->first();
    $seri = $teratas && $urut->where('suara', $teratas['suara'])->count() > 1;
    $persen = $teratas && $suaraMasuk > 0 ? str_replace('.', ',', (string) round($teratas['suara'] / $suaraMasuk * 100, 1)) : '0';
@endphp

@if ($teratas && $teratas['suara'] > 0)
    @if ($seri)
        <section {{ $attributes->class('flex items-start gap-3 rounded-[14px] bg-emas-200 p-5 ring-1 ring-emas-400') }} role="status">
            <flux:icon.scale class="size-6 shrink-0 text-hijau-900" />
            <div>
                <h2 class="font-display text-xl font-black text-hijau-950">Perolehan suara teratas seri</h2>
                <p class="mt-1 text-hijau-950">Pemenang akan ditetapkan oleh panitia sesuai tata tertib pemilihan.</p>
            </div>
        </section>
    @else
        {{-- Kartu pengumuman resmi: pasangan terpilih bercap stempel --}}
        <section {{ $attributes->class('relative overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis') }} aria-labelledby="judul-pemenang">
            <div class="flex items-center justify-between gap-3 bg-hijau-900 px-5 py-2.5 text-white">
                <x-osim.label class="text-emas-300">Pengumuman Resmi · Ketua &amp; Wakil Ketua OSIM Terpilih</x-osim.label>
                <img src="/images/brand/logo-mtsn2.png" alt="" class="size-6" />
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-[auto_1fr] sm:items-center sm:p-6">
                <div class="flex items-end gap-3">
                    <span class="font-display angka text-[7rem] font-black leading-[0.8] text-hijau-600" aria-label="Nomor urut {{ $teratas['nomor_urut'] }}">{{ $teratas['nomor_urut'] }}</span>
                    <x-osim.pasfoto class="aspect-[4/3] w-40" :foto="$teratas['foto_url'] ?? null" alt="Foto {{ ($teratas['berpasangan'] ?? true) ? 'pasangan calon' : 'calon ketua' }} terpilih nomor {{ $teratas['nomor_urut'] }}" />
                </div>
                <div class="relative min-w-0 pr-24 sm:pr-36">
                    <span class="font-sempit inline-flex items-center gap-1 rounded-md bg-emas-400 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-hijau-950"><flux:icon.trophy variant="micro" /> Terpilih</span>
                    <h2 id="judul-pemenang" class="font-display mt-2 text-3xl font-black leading-tight text-tinta">
                        {{ ($teratas['nama_tampil'] ?? trim($teratas['nama_ketua'].(filled($teratas['nama_wakil'] ?? null) ? ' & '.$teratas['nama_wakil'] : ''))) }}
                    </h2>
                    <p class="mt-2 text-tinta-2">
                        Memperoleh <strong class="text-tinta">{{ number_format($teratas['suara'], 0, ',', '.') }} suara ({{ $persen }}%)</strong>
                        dari {{ number_format($suaraMasuk, 0, ',', '.') }} suara sah.
                    </p>
                    <x-osim.stempel class="osim-cap-masuk pointer-events-none absolute -right-2 -top-4 size-28 -rotate-12 mix-blend-multiply sm:size-36" tengah="TERPILIH" />
                </div>
            </div>
        </section>
    @endif
@endif
