{{-- Label isian kartu: huruf kapital kecil ber-spasi. Warna bawaan bisa diganti lewat class text-*. --}}
@php($warnaSendiri = str_contains((string) $attributes->get('class'), 'text-'))
<span {{ $attributes->class(['font-sempit block text-[11px] font-bold uppercase tracking-[0.14em]', 'text-tinta-2' => ! $warnaSendiri]) }}>{{ $slot }}</span>
