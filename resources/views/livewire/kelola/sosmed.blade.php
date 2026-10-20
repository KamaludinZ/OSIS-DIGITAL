<?php

use App\Enums\PlatformSosmed;
use App\Models\SocialPost;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.siswa')] #[Title('Kelola Media Sosial')] class extends Component {
    use WithFileUploads;

    public string $platform = 'youtube';

    public string $judul = '';

    public string $tautan = '';

    public string $orientasi = 'potret';

    public $thumbnail = null;

    public function mount(): void
    {
        $this->authorize('kelola-publikasi');
    }

    public function updatedPlatform(string $nilai): void
    {
        $this->orientasi = PlatformSosmed::from($nilai)->orientasiBawaan();
        $this->resetErrorBag('tautan');
    }

    public function simpan(): void
    {
        $this->authorize('kelola-publikasi');

        $data = $this->validate([
            'platform' => ['required', Rule::enum(PlatformSosmed::class)],
            'judul' => ['required', 'string', 'min:5', 'max:120'],
            'tautan' => ['required', 'url', 'max:500', function ($attr, $nilai, $gagal) {
                if (! PlatformSosmed::from($this->platform)->cocokDengan($nilai)) {
                    $gagal('Tautan harus https dan berasal dari '.implode(' / ', PlatformSosmed::from($this->platform)->domain()).'.');
                }
            }],
            'orientasi' => ['required', 'in:potret,lanskap'],
            'thumbnail' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        SocialPost::create([
            ...collect($data)->except('thumbnail')->all(),
            'thumbnail' => $this->thumbnail->store('sosmed', 'public'),
            'pengunggah_id' => auth()->id(),
        ]);

        $this->reset('judul', 'tautan', 'thumbnail');
        session()->flash('status', 'Konten berhasil ditambahkan ke Beranda.');
    }

    public function ubahTayang(int $id): void
    {
        $this->authorize('kelola-publikasi');
        $post = SocialPost::findOrFail($id);
        $post->update(['dipublikasikan' => ! $post->dipublikasikan]);
    }

    public function hapus(int $id): void
    {
        $this->authorize('kelola-publikasi');
        $post = SocialPost::findOrFail($id);
        Storage::disk('public')->delete($post->thumbnail);
        $post->delete();
    }

    public function with(): array
    {
        return ['daftar' => SocialPost::latest()->latest('id')->get()];
    }
}; ?>

<div>
    <x-osim.pita class="pt-6 pb-20 sm:pt-10">
        <h1 class="font-display text-4xl font-black sm:text-5xl">Kelola Media Sosial</h1>
        <p class="mt-2 max-w-xl text-hijau-50">Atur akun resmi OSIM tiap periode, lalu tambahkan konten YouTube (video atau Shorts), Instagram, atau TikTok agar tampil di Beranda.</p>
    </x-osim.pita>

    <x-osim.wadah class="-mt-12 mb-6">
        <livewire:kelola.akun-sosmed />
    </x-osim.wadah>

    <x-osim.wadah class="grid gap-6 lg:grid-cols-[24rem_1fr] lg:items-start">
        {{-- Formulir --}}
        <form wire:submit="simpan" class="flex flex-col gap-4 rounded-[14px] bg-white p-5 shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis sm:p-6">
            <h2 class="font-display text-xl font-black text-tinta">Konten baru</h2>

            @if (session('status'))
                <p class="flex items-center gap-1.5 rounded-lg bg-hijau-100 px-3 py-2 text-sm font-bold text-hijau-800" role="status"><flux:icon.check-circle variant="micro" /> {{ session('status') }}</p>
            @endif

            <fieldset>
                <legend class="font-bold text-tinta">Jenis media sosial</legend>
                <div class="mt-1.5 grid grid-cols-3 gap-2">
                    @foreach (PlatformSosmed::cases() as $p)
                        <label @class(['flex cursor-pointer flex-col items-center gap-1 rounded-xl px-2 py-2.5 text-center text-xs font-bold ring-1 transition', 'bg-hijau-600 text-white ring-hijau-600' => $platform === $p->value, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => $platform !== $p->value])>
                            <input type="radio" wire:model.live="platform" value="{{ $p->value }}" class="sr-only" />
                            <x-osim.ikon-sosmed :platform="$p->value" class="size-5" />
                            {{ $p->label() }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <label for="judul" class="font-bold text-tinta">Judul</label>
                <input id="judul" wire:model="judul" type="text" maxlength="120" placeholder="Contoh: Keseruan Classmeeting Hari Pertama"
                       class="mt-1.5 h-11 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-3 text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                @error('judul') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="tautan" class="font-bold text-tinta">Tautan konten</label>
                <input id="tautan" wire:model="tautan" type="url" placeholder="https://…"
                       class="mt-1.5 h-11 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-3 text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                @error('tautan') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
            </div>

            <fieldset>
                <legend class="font-bold text-tinta">Orientasi thumbnail</legend>
                <div class="mt-1.5 grid grid-cols-2 gap-2">
                    @foreach (['potret' => ['Potret 9:16', 'w-5 h-8'], 'lanskap' => ['Lanskap 16:9', 'w-8 h-5']] as $nilai => [$teks, $ukuran])
                        <label @class(['flex cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-bold ring-1', 'bg-hijau-600 text-white ring-hijau-600' => $orientasi === $nilai, 'bg-white text-hijau-800 ring-garis hover:bg-hijau-50' => $orientasi !== $nilai])>
                            <input type="radio" wire:model.live="orientasi" value="{{ $nilai }}" class="sr-only" />
                            <span class="{{ $ukuran }} rounded-sm border-2 border-current" aria-hidden="true"></span>
                            {{ $teks }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div>
                <span class="font-bold text-tinta">Thumbnail</span>
                <p class="text-sm text-tinta-2">JPG, PNG, atau WEBP, maksimal 2 MB.</p>
                <label for="thumbnail" class="mt-1.5 flex cursor-pointer flex-col items-center gap-1.5 rounded-xl border-2 border-dashed border-hijau-300 bg-hijau-50 px-4 py-6 text-center transition hover:border-hijau-600 hover:bg-hijau-100 focus-within:border-hijau-600">
                    <flux:icon.photo class="size-8 text-hijau-600" />
                    <span class="font-bold text-hijau-800">{{ $thumbnail ? 'Ganti gambar' : 'Pilih gambar thumbnail' }}</span>
                    <span class="text-xs text-tinta-2">{{ $thumbnail ? $thumbnail->getClientOriginalName() : 'Ketuk untuk memilih dari perangkat' }}</span>
                    <input id="thumbnail" wire:model="thumbnail" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" />
                </label>
                <p class="mt-1 text-sm text-tinta-2" wire:loading wire:target="thumbnail">Mengunggah…</p>
                @error('thumbnail') <p class="mt-1 text-sm font-bold text-cap" role="alert">{{ $message }}</p> @enderror
                @if ($thumbnail && ! $errors->has('thumbnail'))
                    <img src="{{ $thumbnail->temporaryUrl() }}" alt="Pratinjau thumbnail"
                         class="mt-3 rounded-lg object-cover ring-1 ring-garis {{ $orientasi === 'potret' ? 'aspect-[9/16] w-32' : 'aspect-video w-full' }}" />
                @endif
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="simpan,thumbnail"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-emas-400 px-5 py-3 font-black text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300 disabled:opacity-60">
                <flux:icon.plus variant="mini" /> Tambahkan ke Beranda
            </button>
        </form>

        {{-- Daftar konten --}}
        <section aria-labelledby="judul-daftar" class="overflow-hidden rounded-[14px] bg-white ring-1 ring-garis lg:shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)]">
            <h2 id="judul-daftar" class="font-display border-b border-dashed border-garis px-5 py-4 text-xl font-black text-tinta">Konten terunggah ({{ $daftar->count() }})</h2>
            <ul class="divide-y divide-dashed divide-garis">
                @forelse ($daftar as $post)
                    <li wire:key="kelola-{{ $post->id }}" class="flex items-center gap-4 p-4">
                        <img src="{{ $post->urlThumbnail() }}" alt="" class="shrink-0 rounded-md object-cover ring-1 ring-garis {{ $post->orientasi === 'potret' ? 'h-20 w-[2.8rem]' : 'h-14 w-24' }}" />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-1.5 text-xs font-bold text-hijau-700"><x-osim.ikon-sosmed :platform="$post->platform->value" class="size-3.5" /> {{ $post->platform->label() }} · {{ $post->orientasi }}</p>
                            <p class="truncate font-bold text-tinta">{{ $post->judul }}</p>
                            <p class="angka flex items-center gap-1 text-sm text-tinta-2"><flux:icon.eye variant="micro" /> {{ number_format($post->jumlah_klik, 0, ',', '.') }} kali dilihat</p>
                        </div>
                        <div class="flex shrink-0 flex-col gap-1.5 sm:flex-row">
                            <button type="button" wire:click="ubahTayang({{ $post->id }})"
                                    class="rounded-lg px-3 py-1.5 text-sm font-bold {{ $post->dipublikasikan ? 'bg-hijau-100 text-hijau-800' : 'bg-emas-200 text-hijau-950' }}">
                                {{ $post->dipublikasikan ? 'Tayang' : 'Disembunyikan' }}
                            </button>
                            <button type="button" wire:click="hapus({{ $post->id }})" wire:confirm="Hapus konten ini dari Beranda?"
                                    class="rounded-lg px-3 py-1.5 text-sm font-bold text-cap hover:bg-cap/10">Hapus</button>
                        </div>
                    </li>
                @empty
                    <li class="p-8 text-center text-tinta-2">Belum ada konten. Tambahkan lewat formulir.</li>
                @endforelse
            </ul>
        </section>
    </x-osim.wadah>
</div>
