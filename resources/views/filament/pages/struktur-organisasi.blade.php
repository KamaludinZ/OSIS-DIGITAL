<x-filament-panels::page>
    <x-filament::section heading="Pratinjau halaman publik" :description="$struktur ? 'Diperbarui '.$struktur->updated_at->translatedFormat('j F Y, H:i').($struktur->pengunggah ? ' oleh '.$struktur->pengunggah->name : '').' · rasio '.$struktur->rasio : null">
        @if ($struktur)
            <div class="mx-auto max-w-3xl overflow-hidden rounded-xl ring-1 ring-garis">
                <x-bagan-struktur :gambar="$struktur->urlGambar()" :rasio="$struktur->rasio" />
            </div>
        @else
            <div class="flex flex-col items-center gap-2 rounded-xl bg-hijau-50 p-10 text-center ring-1 ring-garis">
                <x-filament::icon icon="heroicon-o-rectangle-group" class="size-10 text-hijau-600" />
                <p class="font-bold text-tinta">Belum ada bagan untuk periode ini</p>
                <p class="text-sm text-tinta-2">Unggah gambar bagan agar tampil di halaman publik Struktur Organisasi.</p>
            </div>
        @endif
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
