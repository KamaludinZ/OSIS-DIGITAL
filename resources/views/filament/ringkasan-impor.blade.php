{{-- Ringkasan impor data pemilih terakhir (per akun, disimpan sehari). --}}
@if ($ringkasan)
    <section aria-label="Ringkasan impor terakhir" class="rounded-xl bg-white p-4 ring-1 ring-garis">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-tinta-2">Impor terakhir · {{ $ringkasan['jenis'] === 'gtk' ? 'GTK' : 'Siswa' }}</p>
                <p class="font-bold text-tinta">{{ $ringkasan['berkas'] }} <span class="font-normal text-tinta-2">· {{ \App\Support\Tanggal::singkat(\Illuminate\Support\Carbon::parse($ringkasan['waktu'])) }}</span></p>
            </div>
            <button type="button" wire:click="tutupRingkasanImpor" class="rounded-lg px-2 py-1 text-sm font-bold text-tinta-2 hover:bg-hijau-50">Tutup</button>
        </div>
        <dl class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ([['Ditambahkan', $ringkasan['tambah'], 'bg-hijau-50 text-hijau-800'], ['Diperbarui', $ringkasan['perbarui'], 'bg-hijau-50 text-hijau-800'], ['Tidak berubah', $ringkasan['sama'], 'bg-white text-tinta ring-1 ring-garis'], ['Dilewati', count($ringkasan['gagal']), count($ringkasan['gagal']) ? 'bg-emas-300/50 text-tinta' : 'bg-white text-tinta ring-1 ring-garis']] as [$label, $nilai, $warna])
                <div class="rounded-lg px-3 py-2 {{ $warna }}">
                    <dt class="text-xs font-semibold">{{ $label }}</dt>
                    <dd class="text-xl font-black tabular-nums">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>
        @if (count($ringkasan['gagal']))
            <details class="mt-3 text-sm">
                <summary class="cursor-pointer font-bold text-tinta">Baris yang dilewati ({{ count($ringkasan['gagal']) }})</summary>
                <ul class="mt-2 list-disc space-y-0.5 pl-5 text-tinta-2">
                    @foreach ($ringkasan['gagal'] as $galat)
                        <li>{{ $galat }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </section>
@endif
