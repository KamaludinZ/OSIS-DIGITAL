<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-2">
        {{-- Kartu pemilih --}}
        <x-filament::section heading="Kartu pemilih" description="Lembar siap cetak & gunting berisi kode pilih 6 angka.">
            <div class="flex flex-col gap-4">
                <p class="text-sm text-tinta-2"><strong class="text-tinta">{{ number_format($totalKartu, 0, ',', '.') }}</strong> kartu siap dicetak.
                    @if ($tanpaKode)
                        <span class="font-semibold text-tinta">{{ $tanpaKode }} pemilih terverifikasi belum punya kode</span> — buat kodenya di menu Data Pemilih.
                    @endif
                </p>
                <label class="flex flex-col gap-1 text-sm font-bold text-tinta">
                    Kelompok
                    <select wire:model.live="kelompok" class="rounded-lg border-garis text-sm">
                        <option value="">Semua pemilih</option>
                        @foreach ($kelompokList as $nama => $jumlah)
                            @continue($jumlah === 0)
                            <option value="{{ $nama }}">{{ $nama === 'gtk' ? 'GTK' : 'Kelas '.$nama }} ({{ $jumlah }})</option>
                        @endforeach
                    </select>
                </label>
                <x-filament::button tag="a" :href="$this->urlKartu()" target="_blank" icon="heroicon-m-printer" :disabled="$totalKartu === 0">
                    Buka lembar kartu
                </x-filament::button>
                <figure>
                    <figcaption class="mb-1 text-xs font-bold uppercase tracking-[0.14em] text-tinta-2">Pratinjau siap gunting (data contoh)</figcaption>
                    <iframe src="{{ route('kartu-pemilih', ['contoh' => 1]) }}" title="Pratinjau kartu pemilih" loading="lazy"
                            class="h-80 w-full rounded-lg bg-white ring-1 ring-garis"></iframe>
                </figure>
            </div>
        </x-filament::section>

        {{-- Laporan hasil --}}
        <x-filament::section heading="Laporan hasil" :description="$pemilihan ? $pemilihan->judul : 'Belum ada pemilihan.'">
            @if (! $bolehLaporan)
                <p class="text-sm text-tinta-2">Laporan hasil hanya untuk panitia dan admin.</p>
            @elseif ($pemilihan)
                <ul class="flex flex-col gap-3">
                    <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 ring-1 ring-garis">
                        <span><strong class="text-tinta">Berita acara</strong><br><span class="text-sm text-tinta-2">Rekap resmi siap cetak & ditandatangani.</span></span>
                        <x-filament::button tag="a" :href="route('hasil.laporan')" target="_blank" color="gray" icon="heroicon-m-printer">Cetak</x-filament::button>
                    </li>
                    <li class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 ring-1 ring-garis">
                        <span><strong class="text-tinta">Data perolehan (CSV)</strong><br><span class="text-sm text-tinta-2">Untuk diolah di spreadsheet.</span></span>
                        <x-filament::button tag="a" :href="route('hasil.laporan.csv')" color="gray" icon="heroicon-m-arrow-down-tray">Unduh</x-filament::button>
                    </li>
                </ul>
                <p class="mt-3 text-xs text-tinta-2">Laporan publik mengikuti status publikasi hasil; panitia & admin selalu melihat perolehan lengkap.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
