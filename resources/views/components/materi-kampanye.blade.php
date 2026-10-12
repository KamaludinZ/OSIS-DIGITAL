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
    @if ($poster->isNotEmpty())
        <section aria-labelledby="judul-poster" class="flex flex-col gap-3">
            <h2 id="judul-poster" class="font-display text-2xl font-black text-tinta flex items-center gap-2">
                <flux:icon.photo variant="mini" class="text-hijau-300" /> Poster
            </h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                @foreach ($poster as $item)
                    <a wire:key="poster-{{ $item['id'] }}" href="{{ $item['url_konten'] }}" target="_blank" rel="noopener"
                       class="group overflow-hidden rounded-[14px] bg-white ring-1 ring-garis">
                        <img src="{{ $item['url_konten'] }}" alt="{{ $item['deskripsi'] }}" loading="lazy"
                             class="aspect-[3/4] w-full object-cover transition group-hover:scale-[1.02]" />
                        <span class="block px-3 py-2 text-sm text-tinta-2">{{ $item['deskripsi'] }}</span>
                    </a>
                @endforeach
            </div>
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
