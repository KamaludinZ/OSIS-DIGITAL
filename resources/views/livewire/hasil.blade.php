<?php

use App\Services\LayananPemilihan;
use App\Support\DataTiruanHasil;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] #[Title('Hasil Pemilihan')] class extends Component {
    public ?array $rekap = null;

    /** Data tiruan — ?simulasi=berjalan|selesai|publik untuk mencoba tampilan. */
    #[Url]
    public ?string $simulasi = null;

    public function mount(LayananPemilihan $layanan): void
    {
        $this->muatUlang($layanan);
    }

    /** Ambil ulang rekap (tombol "Muat ulang" & polling otomatis). */
    public function muatUlang(LayananPemilihan $layanan): void
    {
        $pemilihan = $layanan->ringkasanPemilihan();

        $this->rekap = $pemilihan
            ? DataTiruanHasil::rekap($layanan->daftarKandidat(), $pemilihan, $this->simulasi)
            : null;
    }

    /** ID pemenang tunggal; null bila belum ada suara atau hasil seri. */
    public function idPemenang(): ?int
    {
        $urut = collect($this->rekap['kandidat'] ?? [])->sortByDesc('suara')->values();

        if ($urut->isEmpty() || $urut[0]['suara'] === 0 || ($urut[1]['suara'] ?? -1) === $urut[0]['suara']) {
            return null;
        }

        return $urut[0]['id'];
    }

    /** Unduh laporan hasil sebagai CSV (dibuka di Excel / Google Sheets). */
    public function unduhCsv()
    {
        if (! $this->rekap || ! $this->rekap['hasil_publik']) {
            return null;
        }

        $rekap = $this->rekap;
        $nama = 'laporan-hasil-'.\Illuminate\Support\Str::slug($rekap['judul']).'.csv';

        return response()->streamDownload(function () use ($rekap) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, ['Laporan Hasil', $rekap['judul']], ';');
            fputcsv($out, ['Dibuat', now()->translatedFormat('j F Y H:i').' WIB'], ';');
            fputcsv($out, ['Total pemilih', $rekap['total_pemilih']], ';');
            fputcsv($out, ['Suara masuk', $rekap['suara_masuk']], ';');
            fputcsv($out, ['Partisipasi (%)', str_replace('.', ',', $this->persen($rekap['suara_masuk'], $rekap['total_pemilih']))], ';');
            fputcsv($out, [], ';');
            fputcsv($out, ['No. urut', 'Calon ketua', 'Calon wakil', 'Suara', 'Persentase (%)'], ';');
            foreach (collect($rekap['kandidat'])->sortBy('nomor_urut') as $c) {
                fputcsv($out, [$c['nomor_urut'], $c['nama_ketua'], $c['nama_wakil'], $c['suara'], str_replace('.', ',', $this->persen($c['suara'], $rekap['suara_masuk']))], ';');
            }
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function persen(int $nilai, int $total): float
    {
        return $total > 0 ? round($nilai / $total * 100, 1) : 0.0;
    }
}; ?>

<div>
    @if (! $rekap)
        <x-osim.pita>
            <h1 class="font-display text-4xl font-black sm:text-5xl">Hasil Pemilihan</h1>
        </x-osim.pita>
        <x-osim.wadah class="-mt-10">
        <section class="flex flex-col items-center rounded-[14px] bg-white px-6 py-12 text-center shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis" role="status">
            <flux:icon.chart-bar class="size-10 text-hijau-300" />
            <h2 class="font-display mt-3 text-2xl font-black text-tinta">Belum ada hasil pemilihan</h2>
            <p class="mt-1 text-tinta-2">Hasil akan tampil di sini setelah pemilihan berlangsung.</p>
        </section>
        </x-osim.wadah>
    @else
        @php
            $berjalan = $rekap['status'] === 'berjalan';
            $partisipasi = $this->persen($rekap['suara_masuk'], $rekap['total_pemilih']);
        @endphp

        {{-- Judul & status --}}
        <x-osim.pita class="pt-6 sm:pt-10 pb-24">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="font-display text-4xl font-black leading-[1.02] sm:text-5xl">{{ $rekap['judul'] }}</h1>
                    <p class="mt-2 flex items-center gap-2 font-bold {{ $berjalan ? 'text-emas-300' : 'text-hijau-100' }}">
                        <flux:icon :name="$berjalan ? 'signal' : 'lock-closed'" variant="micro" />
                        {{ $berjalan ? 'Pemilihan berlangsung' : 'Pemilihan ditutup' }}
                    </p>
                </div>
                @if ($rekap['hasil_publik'])
                    <flux:dropdown position="bottom" align="end" class="print:hidden">
                        <button type="button" class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl bg-white px-4 py-2.5 font-bold text-hijau-800 hover:bg-hijau-50">
                            <flux:icon.arrow-down-tray variant="mini" /> Unduh laporan <flux:icon.chevron-down variant="micro" />
                        </button>
                        <flux:menu>
                            <flux:menu.item icon="table-cells" wire:click="unduhCsv">CSV (Excel)</flux:menu.item>
                            <flux:menu.item icon="printer" x-on:click="window.print()">Cetak / simpan PDF</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                @endif
            </div>
        </x-osim.pita>

        <x-osim.wadah class="-mt-16 flex flex-col gap-6">
        @php($publik = $rekap['hasil_publik'])

        {{-- Pengumuman pemenang (hanya setelah ditutup & hasil dipublikasikan) --}}
        @if (! $berjalan && $publik)
            <x-pengumuman-pemenang :kandidat="$rekap['kandidat']" :suara-masuk="$rekap['suara_masuk']" />
        @elseif (! $publik)
            <section class="flex flex-col items-center gap-2 rounded-[14px] border border-2 border-dashed border-garis bg-white px-6 py-10 text-center" role="status">
                <span class="flex size-12 items-center justify-center rounded-full bg-hijau-50 text-tinta-2">
                    <flux:icon :name="$berjalan ? 'clock' : 'shield-check'" class="size-6" />
                </span>
                <h2 class="font-display text-2xl font-black text-tinta">
                    {{ $berjalan ? 'Hasil belum bisa dilihat' : 'Menunggu pengumuman resmi' }}
                </h2>
                <p class="text-tinta-2 max-w-md">
                    @if ($berjalan)
                        Perolehan suara tiap pasangan calon dirahasiakan sampai pemilihan ditutup, agar tidak memengaruhi pemilih yang belum memilih.
                    @else
                        Pemilihan sudah ditutup. Panitia sedang memverifikasi hasil dan akan mempublikasikannya segera.
                    @endif
                </p>
            </section>
        @endif

        {{-- Rekap suara masuk: papan baris --}}
        <section aria-labelledby="judul-rekap" class="overflow-hidden rounded-[14px] bg-hijau-950 text-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.6)]"
                 @if ($berjalan) wire:poll.30s="muatUlang" @endif>
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                <div>
                    <h2 id="judul-rekap" class="font-display text-xl font-black">Rekap Suara Masuk</h2>
                    <p class="angka text-sm text-hijau-200" aria-live="polite">
                        Diperbarui {{ \Illuminate\Support\Carbon::parse($rekap['diperbarui'])->translatedFormat('j F Y, H:i:s') }} WIB
                        @if ($berjalan) · otomatis tiap 30 detik @endif
                    </p>
                </div>
                <button type="button" class="print:hidden inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-white/10 px-3.5 py-2 text-sm font-bold text-white hover:bg-white/20 disabled:opacity-60" wire:click="muatUlang" wire:loading.attr="disabled" wire:target="muatUlang">
                    <flux:icon.arrow-path variant="micro" wire:loading.class="animate-spin" wire:target="muatUlang" />
                    <span wire:loading.remove wire:target="muatUlang">Muat ulang</span>
                    <span wire:loading wire:target="muatUlang">Memuat…</span>
                </button>
            </div>

            <dl class="divide-y divide-white/10" wire:loading.class="opacity-60" wire:target="muatUlang">
                @foreach ([
                    ['Suara masuk', number_format($rekap['suara_masuk'], 0, ',', '.')],
                    ['Total pemilih', number_format($rekap['total_pemilih'], 0, ',', '.')],
                    ['Belum memilih', number_format($rekap['total_pemilih'] - $rekap['suara_masuk'], 0, ',', '.')],
                ] as [$label, $nilai])
                    <div class="flex items-center justify-between gap-4 px-5 py-3">
                        <dt class="font-semibold text-hijau-100">{{ $label }}</dt>
                        <dd class="font-sempit angka rounded-md bg-hijau-900 px-3 py-1 text-2xl font-black tracking-wider text-emas-300">{{ $nilai }}</dd>
                    </div>
                @endforeach
                <div class="px-5 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="font-semibold text-hijau-100">Partisipasi pemilih</dt>
                        <dd class="font-sempit angka rounded-md bg-emas-400 px-3 py-1 text-2xl font-black tracking-wider text-hijau-950">{{ str_replace('.', ',', $partisipasi) }}%</dd>
                    </div>
                    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-hijau-900" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $partisipasi }}" aria-label="Partisipasi pemilih">
                        <div class="h-full rounded-full bg-emas-400 transition-[width] duration-500" style="width: {{ $partisipasi }}%"></div>
                    </div>
                </div>
            </dl>
        </section>


        @if ($publik)
        {{-- Grafik perolehan per pasangan calon --}}
        <section aria-labelledby="judul-perolehan" class="rounded-[14px] bg-white ring-1 ring-garis p-6">
            <h2 id="judul-perolehan" class="font-display text-2xl font-black text-tinta">Perolehan Suara</h2>

            <x-grafik-perolehan class="mt-6" :kandidat="$rekap['kandidat']" :suara-masuk="$rekap['suara_masuk']"
                :terpilih="! $berjalan && $rekap['hasil_publik'] ? $this->idPemenang() : null" />
        </section>
        @endif

        </x-osim.wadah>
    @endif
</div>
