{{-- Bidang hijau madrasah selebar layar, menyambung dari kepala halaman.
     Padding atas/bawah bawaan bisa diganti lewat class pt-* / pb-*. --}}
@php
    $kelas = (string) $attributes->get('class');
    $pbSendiri = preg_match('/(^|\s)(sm:)?pb-/', $kelas);
    $ptSendiri = preg_match('/(^|\s)(sm:)?pt-/', $kelas);
@endphp
<section {{ $attributes->class(['osim-guilloche bg-hijau-600 text-white', 'pb-16' => ! $pbSendiri, 'pt-6 sm:pt-10' => ! $ptSendiri]) }}>
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        {{ $slot }}
    </div>
</section>
