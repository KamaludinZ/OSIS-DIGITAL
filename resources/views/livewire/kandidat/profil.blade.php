<?php

use App\Services\LayananPemilihan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    #[\Livewire\Attributes\Locked]
    public array $calon = [];

    /** Kandidat sebelum & sesudah (urut nomor) untuk navigasi. */
    #[\Livewire\Attributes\Locked]
    public ?array $sebelumnya = null;

    #[\Livewire\Attributes\Locked]

    public ?array $berikutnya = null;

    #[\Livewire\Attributes\Locked]

    public ?string $statusPemilihan = null;

    #[\Livewire\Attributes\Locked]

    public array $materi = [];

    #[\Livewire\Attributes\Locked]

    public array $jadwal = [];

    #[\Livewire\Attributes\Locked]

    public array $tanyaJawab = [];

    public string $pertanyaanBaru = '';

    #[\Livewire\Attributes\Locked]

    public bool $pertanyaanTerkirim = false;

    /** Bagian yang sedang dibuka: profil | materi | jadwal | tanya-jawab. */
    #[Url(except: 'profil', history: true)]
    public string $tab = 'profil';

    /** Halaman asal untuk tombol kembali: kandidat | bilik-suara. */
    #[Url(except: 'kandidat')]
    public string $dari = 'kandidat';

    public function mount(int $id, LayananPemilihan $layanan): void
    {
        $this->calon = $layanan->cariKandidat($id) ?? abort(404);

        $semua = collect($layanan->daftarKandidat())->values();
        $posisi = $semua->search(fn ($c) => $c['id'] === $this->calon['id']);
        $this->sebelumnya = $posisi > 0 ? $semua[$posisi - 1] : null;
        $this->berikutnya = $semua[$posisi + 1] ?? null;

        $this->statusPemilihan = $layanan->ringkasanPemilihan()['status'] ?? null;

        $this->materi = app(\App\Services\LayananKampanye::class)->materi($this->calon['id']);
        $this->jadwal = app(\App\Services\LayananKampanye::class)->jadwal($this->calon['id']);
        $this->tanyaJawab = app(\App\Services\LayananKampanye::class)->tanyaJawab($this->calon['id']);

        if (! array_key_exists($this->tab, $this->daftarTab())) {
            $this->tab = 'profil';
        }

        if (! in_array($this->dari, ['kandidat', 'bilik-suara'], true)) {
            $this->dari = 'kandidat';
        }

        if ($this->kandidatSaya()) {
            $this->tanggapan = collect($this->tanyaJawab)->mapWithKeys(fn ($q) => [$q['id'] => $q['jawaban'] ?? ''])->all();
        }


    }

    /** Verifikasi identitas penanya (tanpa akun): NISN/NIK + tanggal lahir. */
    public string $jenisPenanya = 'siswa';

    public string $nomorInduk = '';

    public string $tanggalLahir = '';

    /** Penanya: pemilih terverifikasi di sesi ini, atau akun pengurus/panitia/admin (bukan akun kandidat). */
    public function penanya(): \App\Models\Voter|\App\Models\User|null
    {
        $akun = auth()->user();

        return app(\App\Services\SesiPemilih::class)->penanya()
            ?? ($akun?->role === \App\Enums\Peran::Kandidat ? null : $akun);
    }

    /** Akun kandidat yang sedang masuk adalah pemilik profil ini? */
    public function kandidatSaya(): bool
    {
        $akun = auth()->user();

        return $akun?->role === \App\Enums\Peran::Kandidat && $akun->candidate_id === $this->calon['id'];
    }

    /** Draf tanggapan kandidat per pertanyaan (id => teks). */
    public array $tanggapan = [];

    #[\Livewire\Attributes\Locked]

    public ?int $tanggapanTersimpan = null;

    public function tanggapi(int $id): void
    {
        $tanya = \App\Models\CandidateQuestion::where('candidate_id', $this->calon['id'])->tampil()->findOrFail($id);

        $this->resetErrorBag('tanggapan.'.$id);
        $this->validate(['tanggapan.'.$id => ['nullable', 'string', 'max:1000']], [], ['tanggapan.'.$id => 'Tanggapan']);

        try {
            $baru = app(\App\Services\LayananKampanye::class)->jawabPertanyaan(auth()->user() ?? abort(403), $tanya, $this->tanggapan[$id] ?? '');
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            abort(403);
        }

        $this->tanyaJawab = collect($this->tanyaJawab)->map(fn ($q) => $q['id'] === $id ? $baru : $q)->all();
        $this->tanggapanTersimpan = $id;
    }

    public function verifikasiPenanya(): void
    {
        $this->validate([
            'jenisPenanya' => ['required', 'in:siswa,gtk'],
            'nomorInduk' => ['required', 'string', 'max:20'],
            'tanggalLahir' => ['required', 'date'],
        ], [], ['nomorInduk' => $this->jenisPenanya === 'gtk' ? 'NIK' : 'NISN', 'tanggalLahir' => 'Tanggal lahir']);

        app(\App\Services\SesiPemilih::class)->verifikasiPenanya($this->jenisPenanya, $this->nomorInduk, $this->tanggalLahir, 'nomorInduk');
        $this->reset('nomorInduk', 'tanggalLahir');
    }

    public function kirimPertanyaan(): void
    {
        $penanya = $this->penanya();

        if (! $penanya) {
            return;
        }

        $this->validate(
            ['pertanyaanBaru' => ['required', 'string', 'min:10', 'max:500']],
            [
                'pertanyaanBaru.required' => 'Tulis pertanyaanmu terlebih dahulu.',
                'pertanyaanBaru.min' => 'Pertanyaan minimal :min karakter.',
                'pertanyaanBaru.max' => 'Pertanyaan maksimal :max karakter.',
            ],
        );

        $baru = app(\App\Services\LayananKampanye::class)->kirimPertanyaan(
            $penanya,
            \App\Models\Candidate::findOrFail($this->calon['id']),
            $this->pertanyaanBaru,
        );

        // Pertanyaan duplikat mengembalikan data lama; jangan tampilkan dua kali.
        if (! collect($this->tanyaJawab)->contains('id', $baru['id'])) {
            array_unshift($this->tanyaJawab, $baru);
        }

        $this->reset('pertanyaanBaru');
        $this->pertanyaanTerkirim = true;
    }

    public function daftarTab(): array
    {
        return [
            'profil' => ['Profil & Visi Misi', 'identification'],
            'materi' => ['Materi Kampanye', 'photo'],
            'jadwal' => ['Jadwal Kampanye', 'calendar-days'],
            'tanya-jawab' => ['Tanya Jawab', 'chat-bubble-left-right'],
        ];
    }

    public function rendering($view): void
    {
        $view->title(($this->calon['berpasangan'] ? 'Profil Paslon ' : 'Profil Calon ').$this->calon['nomor_urut']);
    }
}; ?>

<div>
    {{-- Kepala profil: kartu peserta besar di atas pita hijau --}}
    <x-osim.pita class="pb-0 pt-4">
        @if ($dari === 'bilik-suara')
            <a href="{{ route('bilik-suara') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold text-hijau-50 hover:bg-white/10">
                <flux:icon.arrow-left variant="micro" /> Kembali ke Bilik Suara
            </a>
        @else
            <a href="{{ route('kandidat.index') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold text-hijau-50 hover:bg-white/10">
                <flux:icon.arrow-left variant="micro" /> Semua kandidat
            </a>
        @endif

        {{-- Foto lebar (4:3) dengan nomor urut menempel di sudut; nama ketua & wakil per baris agar tidak menjadi blok raksasa. --}}
        <div class="mt-4 grid items-center gap-6 pb-8 md:grid-cols-[minmax(0,22rem)_1fr] md:gap-10 lg:grid-cols-[minmax(0,26rem)_1fr]">
            <div class="relative mx-auto w-full max-w-md md:max-w-none">
                <x-osim.pasfoto gelap class="aspect-[4/3] w-full ring-4 ring-white/15 shadow-[0_18px_40px_-18px_rgb(4_41_26/0.7)]" :foto="$calon['foto_url']" alt="Foto {{ $calon['berpasangan'] ? $calon['nama_ketua'].' dan '.$calon['nama_wakil'] : $calon['nama_ketua'] }}" />
                <span class="absolute -left-2 -top-3 flex items-baseline gap-1 rounded-xl bg-hijau-950 px-3 py-1.5 shadow-[0_6px_14px_-4px_rgb(4_41_26/0.7)] ring-2 ring-emas-400 sm:-left-3" aria-hidden="true">
                    <span class="font-sempit text-xs font-bold uppercase tracking-wider text-hijau-200">No.</span>
                    <span class="font-display angka text-5xl font-black leading-none text-emas-300 sm:text-6xl">{{ $calon['nomor_urut'] }}</span>
                </span>
            </div>
            <div class="min-w-0">
                <h1 class="font-display flex flex-col gap-3 font-black">
                    <span class="sr-only">{{ $calon['berpasangan'] ? 'Pasangan Calon' : 'Calon Ketua' }} {{ $calon['nomor_urut'] }}: {{ $calon['nama_tampil'] }}</span>
                    @foreach (array_filter([
                        [$calon['berpasangan'] ? 'Calon Ketua' : 'Calon Ketua OSIM', $calon['nama_ketua'], $calon['kelas_ketua']],
                        $calon['berpasangan'] ? ['Calon Wakil', $calon['nama_wakil'], $calon['kelas_wakil']] : null,
                    ]) as [$peran, $nama, $kelas])
                        <span class="block" aria-hidden="true">
                            <span class="font-sempit block text-xs font-bold uppercase tracking-[0.16em] text-emas-300">{{ $peran }}@if ($kelas) <span class="text-hijau-200">· {{ $kelas }}</span>@endif</span>
                            <span class="mt-1 block text-balance text-[1.6rem] leading-[1.05] sm:text-3xl lg:text-[2.5rem]">{{ $nama }}</span>
                        </span>
                    @endforeach
                </h1>
                @if (! empty($calon['slogan']))
                    <p class="mt-5 inline-block rounded-lg bg-emas-400 px-3 py-1.5 font-bold italic text-hijau-950">“{{ $calon['slogan'] }}”</p>
                @endif
            </div>
        </div>

        {{-- Tab mading bagian profil --}}
        <nav aria-label="Bagian profil kandidat" class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" x-data x-init="$el.querySelector('[aria-current]')?.scrollIntoView({ inline: 'center', block: 'nearest' })">
            <div class="flex min-w-max gap-1.5">
                @foreach ($this->daftarTab() as $kunci => [$label, $ikon])
                    <button type="button" wire:click="$set('tab', '{{ $kunci }}')"
                            @class([
                                'flex items-center gap-2 whitespace-nowrap rounded-t-xl px-4 pb-2.5 pt-3 text-sm font-bold transition',
                                'bg-hijau-50 text-hijau-900' => $tab === $kunci,
                                'bg-hijau-900/30 text-hijau-50 hover:bg-hijau-900/50' => $tab !== $kunci,
                            ])
                            @if ($tab === $kunci) aria-current="page" @endif>
                        <flux:icon :name="$ikon" variant="mini" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </nav>
    </x-osim.pita>

    <x-osim.wadah class="flex flex-col gap-6 pt-6">
        @if ($tab === 'profil')
            <section class="rounded-[14px] bg-white p-6 ring-1 ring-garis">
                <h2 class="font-display text-xl font-black text-tinta">Latar Belakang</h2>
                <p class="mt-2 max-w-[70ch] text-tinta-2">{{ $calon['latar_belakang'] }}</p>
            </section>

            <section class="grid gap-6 md:grid-cols-5">
                <div class="rounded-[14px] bg-hijau-600 p-6 text-white md:col-span-2">
                    <h2 class="font-sempit text-sm font-bold uppercase tracking-[0.14em] text-emas-300">Visi</h2>
                    <p class="font-display mt-3 text-2xl font-extrabold leading-snug">{{ $calon['visi'] }}</p>
                </div>

                <div class="rounded-[14px] bg-white p-6 ring-1 ring-garis md:col-span-3">
                    <h2 class="font-sempit text-sm font-bold uppercase tracking-[0.14em] text-hijau-700">Misi</h2>
                    <ol class="mt-3 divide-y divide-dashed divide-garis">
                        @foreach ($calon['misi'] as $i => $misi)
                            <li class="flex gap-4 py-3">
                                <span class="font-display angka w-8 shrink-0 text-3xl font-black leading-none text-emas-500">{{ $i + 1 }}</span>
                                <span class="text-tinta">{{ $misi }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @elseif ($tab === 'materi')
            <x-materi-kampanye :materi="$materi" />
        @elseif ($tab === 'jadwal')
            <x-jadwal-kampanye :jadwal="$jadwal" />
        @else
            {{-- Tanya jawab kandidat --}}
            <section aria-labelledby="judul-tanya" class="flex flex-col gap-4">
                <h2 id="judul-tanya" class="font-display text-2xl font-black text-tinta">Tanya Jawab</h2>

                <div class="rounded-[14px] bg-white p-5 ring-1 ring-garis">
                    @if ($this->kandidatSaya())
                        @php($menunggu = collect($tanyaJawab)->whereNull('jawaban')->count())
                        <div class="flex items-start gap-3">
                            <span class="font-display angka flex size-10 shrink-0 items-center justify-center rounded-lg bg-hijau-600 text-xl font-black text-emas-300">{{ $calon['nomor_urut'] }}</span>
                            <div>
                                <h3 class="font-bold text-tinta">Kamu masuk sebagai kandidat nomor {{ $calon['nomor_urut'] }}</h3>
                                <p class="text-sm text-tinta-2">
                                    {{ $menunggu ? $menunggu.' pertanyaan menunggu tanggapanmu.' : 'Semua pertanyaan sudah kamu tanggapi.' }}
                                    Tanggapan tampil untuk semua siswa dan GTK dengan label nomor urutmu.
                                </p>
                            </div>
                        </div>
                    @elseif (auth()->user()?->role === \App\Enums\Peran::Kandidat)
                        <p class="flex items-center gap-2 text-sm text-tinta-2"><flux:icon.information-circle variant="mini" class="text-hijau-600" /> Akun kandidat hanya bisa menanggapi pertanyaan di profilnya sendiri.</p>
                    @elseif ($penanya = $this->penanya())
                        <p class="mb-3 text-sm text-tinta-2">Bertanya sebagai <strong class="text-tinta">{{ $penanya instanceof \App\Models\Voter ? $penanya->nama.' · '.$penanya->keterangan() : $penanya->name }}</strong></p>
                        <form wire:submit="kirimPertanyaan" class="flex flex-col gap-3">
                            <label for="pertanyaan-baru" class="font-bold text-tinta">Tanyakan sesuatu kepada {{ $calon['berpasangan'] ? 'Paslon' : 'Calon' }} {{ $calon['nomor_urut'] }}</label>
                            <textarea id="pertanyaan-baru" wire:model="pertanyaanBaru" rows="3" maxlength="500"
                                      placeholder="Contoh: Bagaimana cara menjalankan program ini tanpa mengganggu jam belajar?"
                                      class="w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-4 py-3 text-tinta placeholder:text-tinta-2/70 focus:border-hijau-600 focus:bg-white focus:outline-none"></textarea>
                            @error('pertanyaanBaru')
                                <p class="flex items-center gap-1.5 text-sm font-bold text-cap" role="alert"><flux:icon.exclamation-circle variant="micro" /> {{ $message }}</p>
                            @enderror
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-tinta-2">Pertanyaan tampil dengan namamu setelah dikirim.</p>
                                <button type="submit" wire:loading.attr="disabled" wire:target="kirimPertanyaan"
                                        class="inline-flex items-center gap-2 rounded-xl bg-hijau-600 px-5 py-2.5 font-bold text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)] hover:bg-hijau-700 disabled:opacity-60">
                                    <flux:icon.paper-airplane variant="micro" /> Kirim
                                </button>
                            </div>
                            @if ($pertanyaanTerkirim)
                                <p class="flex items-center gap-1.5 text-sm font-bold text-hijau-700" role="status"><flux:icon.check-circle variant="micro" /> Pertanyaan terkirim. Kandidat akan menjawabnya secepatnya.</p>
                            @endif
                        </form>
                    @else
                        <form wire:submit="verifikasiPenanya" class="flex flex-col gap-4">
                            <div>
                                <h3 class="font-bold text-tinta">Konfirmasi dulu identitasmu</h3>
                                <p class="text-sm text-tinta-2">Tidak perlu akun. Cukup cocokkan dengan data madrasah.</p>
                            </div>
                            <fieldset class="grid grid-cols-2 gap-2">
                                <legend class="sr-only">Saya adalah</legend>
                                @foreach (['siswa' => 'Siswa', 'gtk' => 'Guru / Tendik (GTK)'] as $nilai => $label)
                                    <label @class(['cursor-pointer rounded-xl px-3 py-2.5 text-center text-sm font-bold ring-1', 'bg-hijau-600 text-white ring-hijau-600' => $jenisPenanya === $nilai, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => $jenisPenanya !== $nilai])>
                                        <input type="radio" wire:model.live="jenisPenanya" value="{{ $nilai }}" class="sr-only" /> {{ $label }}
                                    </label>
                                @endforeach
                            </fieldset>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="nomor-induk" class="text-sm font-bold text-tinta">{{ $jenisPenanya === 'gtk' ? 'NIK' : 'NISN' }}</label>
                                    <input id="nomor-induk" wire:model="nomorInduk" type="text" inputmode="numeric" autocomplete="off" maxlength="20"
                                           class="mt-1 h-11 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-3 text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                                </div>
                                <div>
                                    <label for="tanggal-lahir" class="text-sm font-bold text-tinta">Tanggal lahir</label>
                                    <input id="tanggal-lahir" wire:model="tanggalLahir" type="date"
                                           class="mt-1 h-11 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-3 text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                                </div>
                            </div>
                            @error('nomorInduk') <p class="flex items-center gap-1.5 text-sm font-bold text-cap" role="alert"><flux:icon.exclamation-circle variant="micro" /> {{ $message }}</p> @enderror
                            @error('tanggalLahir') <p class="text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                            <button type="submit" wire:loading.attr="disabled" wire:target="verifikasiPenanya"
                                    class="inline-flex w-fit items-center gap-2 rounded-xl bg-hijau-600 px-5 py-2.5 font-bold text-white hover:bg-hijau-700 disabled:opacity-60">
                                <flux:icon.shield-check variant="micro" /> Konfirmasi &amp; mulai bertanya
                            </button>
                        </form>
                    @endif
                </div>

                @forelse ($tanyaJawab as $item)
                    <article wire:key="tanya-{{ $item['id'] }}" class="rounded-[14px] bg-white p-5 ring-1 ring-garis">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-semibold text-tinta-2">
                            <span>{{ $item['penanya'] }} · {{ \Illuminate\Support\Carbon::parse($item['created_at'])->diffForHumans() }}</span>
                            @unless ($item['jawaban'])
                                <span class="font-sempit rounded-md bg-emas-200 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-hijau-950">Menunggu jawaban</span>
                            @endunless
                        </div>
                        <p class="mt-2 text-lg font-bold leading-snug text-tinta">{{ $item['pertanyaan'] }}</p>

                        @if ($this->kandidatSaya())
                            <form wire:submit="tanggapi({{ $item['id'] }})" class="mt-3 flex flex-col gap-2 rounded-xl bg-hijau-50 p-3 ring-1 ring-garis">
                                <label for="tanggapan-{{ $item['id'] }}" class="text-xs font-bold text-hijau-700">{{ $item['jawaban'] ? 'Tanggapanmu' : 'Tulis tanggapanmu' }}</label>
                                <textarea id="tanggapan-{{ $item['id'] }}" wire:model="tanggapan.{{ $item['id'] }}" rows="3" maxlength="1000"
                                          class="w-full rounded-lg border-2 border-garis bg-white px-3 py-2 text-tinta focus:border-hijau-600 focus:outline-none"></textarea>
                                @error('tanggapan.'.$item['id']) <p class="text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="submit" wire:loading.attr="disabled" wire:target="tanggapi({{ $item['id'] }})"
                                            class="inline-flex items-center gap-2 rounded-lg bg-hijau-600 px-4 py-2 text-sm font-bold text-white hover:bg-hijau-700 disabled:opacity-60">
                                        <flux:icon.chat-bubble-left-ellipsis variant="micro" /> {{ $item['jawaban'] ? 'Perbarui tanggapan' : 'Kirim tanggapan' }}
                                    </button>
                                    @if ($tanggapanTersimpan === $item['id'])
                                        <span class="flex items-center gap-1 text-sm font-bold text-hijau-700" role="status"><flux:icon.check-circle variant="micro" /> {{ $item['jawaban'] ? 'Tanggapan tersimpan' : 'Tanggapan ditarik' }}</span>
                                    @endif
                                </div>
                            </form>
                        @elseif ($item['jawaban'])
                            <div class="mt-3 rounded-xl bg-hijau-50 px-4 py-3 ring-1 ring-garis">
                                <div class="flex items-center gap-2 text-xs font-bold text-hijau-700">
                                    <span class="font-display angka flex size-5 items-center justify-center rounded bg-hijau-600 text-[11px] font-black text-white">{{ $calon['nomor_urut'] }}</span>
                                    Jawaban {{ $calon['berpasangan'] ? 'Paslon' : 'Calon' }} {{ $calon['nomor_urut'] }}
                                </div>
                                <p class="mt-1 text-tinta">{{ $item['jawaban'] }}</p>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="flex flex-col items-center rounded-[14px] border-2 border-dashed border-garis px-6 py-10 text-center" role="status">
                        <flux:icon.chat-bubble-left-right class="size-10 text-hijau-300" />
                        <p class="mt-2 text-tinta-2">Belum ada pertanyaan. Jadilah yang pertama bertanya!</p>
                    </div>
                @endforelse
            </section>
        @endif

        {{-- Navigasi antar pasangan calon --}}
        <nav aria-label="{{ $calon['berpasangan'] ? 'Pasangan calon' : 'Calon ketua' }} lain" class="flex flex-col gap-4 border-t border-dashed border-garis pt-6 sm:flex-row sm:items-center">
            <div class="grid flex-1 grid-cols-2 gap-3 sm:max-w-md">
                @if ($sebelumnya)
                    <a href="{{ route('kandidat.profil', ['id' => $sebelumnya['id'], 'dari' => $dari === 'kandidat' ? null : $dari]) }}" wire:navigate
                       class="flex items-center gap-3 rounded-xl bg-white p-3 ring-1 ring-garis hover:ring-hijau-500">
                        <span class="font-display angka text-3xl font-black text-hijau-600">{{ $sebelumnya['nomor_urut'] }}</span>
                        <span class="min-w-0">
                            <span class="flex items-center gap-0.5 text-xs font-semibold text-tinta-2"><flux:icon.chevron-left variant="micro" /> {{ $sebelumnya['berpasangan'] ? 'Paslon' : 'Calon' }} {{ $sebelumnya['nomor_urut'] }}</span>
                            <span class="block truncate text-sm font-bold text-tinta">{{ $sebelumnya['nama_ketua'] }}</span>
                        </span>
                    </a>
                @else
                    <span></span>
                @endif

                @if ($berikutnya)
                    <a href="{{ route('kandidat.profil', ['id' => $berikutnya['id'], 'dari' => $dari === 'kandidat' ? null : $dari]) }}" wire:navigate
                       class="flex items-center justify-end gap-3 rounded-xl bg-white p-3 text-right ring-1 ring-garis hover:ring-hijau-500">
                        <span class="min-w-0">
                            <span class="flex items-center justify-end gap-0.5 text-xs font-semibold text-tinta-2">{{ $berikutnya['berpasangan'] ? 'Paslon' : 'Calon' }} {{ $berikutnya['nomor_urut'] }} <flux:icon.chevron-right variant="micro" /></span>
                            <span class="block truncate text-sm font-bold text-tinta">{{ $berikutnya['nama_ketua'] }}</span>
                        </span>
                        <span class="font-display angka text-3xl font-black text-hijau-600">{{ $berikutnya['nomor_urut'] }}</span>
                    </a>
                @endif
            </div>

            @if ($statusPemilihan === 'berjalan')
                <x-tombol-memilih class="w-full sm:ml-auto sm:w-auto" />
            @endif
        </nav>
    </x-osim.wadah>
</div>
