{{-- Pratinjau kecil gambar yang akan diganti, di dialog Ganti gambar. --}}
@php($struktur = \App\Support\DataTiruanStruktur::aktif())
<div class="flex items-center gap-3 rounded-lg bg-emas-300/30 p-3 ring-1 ring-garis">
    <img src="{{ $struktur['gambar'] }}" alt="Bagan saat ini" class="h-16 w-20 shrink-0 rounded-md bg-white object-contain ring-1 ring-garis" />
    <p class="text-sm text-tinta"><strong>Gambar saat ini</strong> ({{ $struktur['rasio'] }}) akan diganti dengan gambar baru.</p>
</div>
