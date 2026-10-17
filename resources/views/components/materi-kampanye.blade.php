@props(['materi' => []])

@php
    $grup = collect($materi)->groupBy('tipe');
    $poster = $grup->get('poster', collect());
    $video = $grup->get('video', collect());
    $janji = $grup->get('janji', collect());

    // Ubah tautan YouTube biasa menjadi alamat embed.
    $embedYoutube = function (?string $url): ?string {
        if (! $url || ! preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([\w-]{11})~', $url, $m)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$m[1];
    };
@endphp

<div {{ $attributes->class('flex flex-col gap-6') }}>
    @if ($poster->isEmpty() && $video->isEmpty() && $janji->isEmpty())
        <section class="flex flex-col items-center rounded-[14px] border border-2 border-dashed border-garis px-6 py-12 text-center" role="status">
            <flux:icon.photo class="size-10 text-hijau-300" />
            <h2 class="font-display text-2xl font-black text-tinta mt-3">Belum ada materi kampanye</h2>
            <p class="text-tinta-2 mt-1">{{ ucfirst(\App\Support\Istilah::calon()) }} ini belum mengunggah poster, video, atau janji kerja.</p>
        </section>
    @endif

    {{-- Janji kerja --}}
    @if ($janji->isNotEmpty())
        <section aria-labelledby="judul-janji" class="rounded-[14px] bg-white ring-1 ring-garis p-6">
            <h2 id="judul-janji" class="font-display text-2xl font-black text-tinta flex items-center gap-2">
                <flux:icon.hand-raised variant="mini" class="text-hijau-300" /> Janji Kerja
            </h2>
            <ol class="mt-4 space-y-3">
                @foreach ($janji as $item)
                    <li wire:key="janji-{{ $item['id'] }}" class="flex gap-3">
                        <flux:icon.check-circle variant="mini" class="mt-0.5 shrink-0 text-hijau-600" />
                        <span class="text-tinta">{{ $item['deskripsi'] }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Poster --}}
    {{-- Diklik membuka popup (bukan tab baru) agar berkas poster tidak dibuka/diunduh langsung. --}}
    @if ($poster->isNotEmpty())
        <section aria-labelledby="judul-poster" class="flex flex-col gap-3"
                 x-data="{
                     poster: @js($poster->map(fn ($p) => ['src' => $p['url_konten'], 'alt' => $p['deskripsi'] ?? ''])->values()),
                     i: 0,
                     buka(n) { this.i = n; this.$refs.popup.showModal(); },
                     geser(arah) { this.i = (this.i + arah + this.poster.length) % this.poster.length; },
                 }">
            <h2 id="judul-poster" class="font-display text-2xl font-black text-tinta flex items-center gap-2">
                <flux:icon.photo variant="mini" class="text-hijau-300" /> Poster
            </h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach ($poster as $item)
                    <button type="button" wire:key="poster-{{ $item['id'] }}" x-on:click="buka({{ $loop->index }})"
                            aria-haspopup="dialog" aria-label="Perbesar poster{{ filled($item['deskripsi']) ? ': '.$item['deskripsi'] : '' }}"
                            class="group overflow-hidden rounded-[14px] bg-white text-left ring-1 ring-garis transition hover:ring-hijau-600 focus-visible:ring-hijau-600">
                        <span class="osim-lindung relative block overflow-hidden">
                            <img src="{{ $item['url_konten'] }}" alt="{{ $item['deskripsi'] }}" loading="lazy" draggable="false"
                                 class="aspect-[3/4] w-full object-cover transition group-hover:scale-[1.02]" />
                            <span class="absolute bottom-2 right-2 z-[2] flex size-8 items-center justify-center rounded-lg bg-hijau-950/70 text-white opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">
                                <flux:icon.magnifying-glass-plus variant="mini" />
                            </span>
                        </span>
                        <span class="block px-3 py-2 text-sm text-tinta-2">{{ $item['deskripsi'] }}</span>
                    </button>
                @endforeach
            </div>

            <dialog x-ref="popup" aria-label="Poster kampanye"
                    x-on:click.self="$el.close()"
                    x-on:keydown.arrow-left="geser(-1)" x-on:keydown.arrow-right="geser(1)"
                    class="osim-popup m-auto max-h-none max-w-none bg-transparent p-0 backdrop:bg-hijau-950/90 backdrop:backdrop-blur-sm">
                <figure class="flex flex-col items-center gap-3 px-4 py-4">
                    <div class="flex w-full items-center justify-between gap-3 text-white">
                        <span class="font-sempit text-sm font-bold uppercase tracking-[0.14em] text-hijau-200" x-show="poster.length > 1">
                            Poster <span class="angka" x-text="i + 1"></span> dari <span class="angka" x-text="poster.length"></span>
                        </span>
                        <button type="button" x-on:click="$refs.popup.close()" autofocus
                                class="ml-auto flex items-center gap-1.5 rounded-lg bg-white/10 px-3 py-2 text-sm font-bold hover:bg-white/20">
                            <flux:icon.x-mark variant="micro" /> Tutup
                        </button>
                    </div>
                    <div class="relative flex items-center gap-2">
                        <button type="button" x-show="poster.length > 1" x-on:click="geser(-1)" aria-label="Poster sebelumnya"
                                class="absolute left-1 z-[3] flex size-11 items-center justify-center rounded-full bg-hijau-950/70 text-white hover:bg-hijau-950 sm:static sm:bg-white/10 sm:hover:bg-white/20">
                            <flux:icon.chevron-left variant="mini" />
                        </button>
                        <div class="osim-lindung relative overflow-hidden rounded-[14px] bg-hijau-900">
                            <img :src="poster[i].src" :alt="poster[i].alt" draggable="false"
                                 class="block h-auto max-h-[78dvh] w-auto min-w-[min(80vw,20rem)] max-w-[min(92vw,40rem)] object-contain" />
                        </div>
                        <button type="button" x-show="poster.length > 1" x-on:click="geser(1)" aria-label="Poster berikutnya"
                                class="absolute right-1 z-[3] flex size-11 items-center justify-center rounded-full bg-hijau-950/70 text-white hover:bg-hijau-950 sm:static sm:bg-white/10 sm:hover:bg-white/20">
                            <flux:icon.chevron-right variant="mini" />
                        </button>
                    </div>
                    <figcaption class="max-w-[48rem] text-center text-sm text-hijau-50" x-text="poster[i].alt" x-show="poster[i].alt"></figcaption>
                </figure>
            </dialog>
        </section>
    @endif

    {{-- Video --}}
    @if ($video->isNotEmpty())
        <section aria-labelledby="judul-video" class="flex flex-col gap-3">
            <h2 id="judul-video" class="font-display text-2xl font-black text-tinta flex items-center gap-2">
                <flux:icon.play-circle variant="mini" class="text-hijau-300" /> Video
            </h2>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($video as $item)
                    <figure wire:key="video-{{ $item['id'] }}" class="overflow-hidden rounded-[14px] bg-white ring-1 ring-garis">
                        @if ($embed = $embedYoutube($item['url_konten']))
                            <iframe src="{{ $embed }}" title="{{ $item['deskripsi'] }}" class="aspect-video w-full" loading="lazy"
                                    allow="accelerometer; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                        @elseif ($item['url_konten'])
                            <video src="{{ $item['url_konten'] }}" controls preload="metadata" class="aspect-video w-full bg-black"></video>
                        @else
                            <div class="flex aspect-video w-full flex-col items-center justify-center gap-2 bg-hijau-50 text-hijau-300">
                                <flux:icon.video-camera-slash class="size-10" />
                                <span class="text-sm">Video belum tersedia</span>
                            </div>
                        @endif
                        <figcaption class="px-4 py-3 text-sm text-tinta-2">{{ $item['deskripsi'] }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif
</div>
