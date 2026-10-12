{{-- Pratinjau impor data pemilih (sebelum disimpan). --}}
<div class="flex flex-col gap-3 rounded-xl bg-hijau-50 p-4 ring-1 ring-garis">
    <p class="text-sm font-bold text-tinta">Pratinjau · {{ $p['total'] }} baris terbaca</p>

    <dl class="grid grid-cols-3 gap-2 text-center text-sm">
        <div class="rounded-lg bg-white p-2 ring-1 ring-garis"><dt class="text-tinta-2">Baru</dt><dd class="text-lg font-black tabular-nums text-hijau-800">{{ $p['baru'] }}</dd></div>
        <div class="rounded-lg bg-white p-2 ring-1 ring-garis"><dt class="text-tinta-2">Diperbarui</dt><dd class="text-lg font-black tabular-nums text-hijau-800">{{ $p['perbarui'] }}</dd></div>
        <div class="rounded-lg bg-white p-2 ring-1 ring-garis"><dt class="text-tinta-2">Dilewati</dt><dd @class(['text-lg font-black tabular-nums', 'text-danger-600' => count($p['gagal']), 'text-tinta-2' => ! count($p['gagal'])])>{{ count($p['gagal']) }}</dd></div>
    </dl>

    @if ($p['contoh'])
        <div class="overflow-x-auto rounded-lg bg-white ring-1 ring-garis">
            <table class="w-full text-left text-xs">
                <thead class="bg-hijau-100 text-tinta">
                    <tr><th class="px-2 py-1.5">#</th><th class="px-2 py-1.5">{{ $jenis === 'gtk' ? 'NIK' : 'NISN' }}</th><th class="px-2 py-1.5">Nama</th><th class="px-2 py-1.5">{{ $jenis === 'gtk' ? 'Jabatan' : 'Kelas' }}</th><th class="px-2 py-1.5">Lahir</th></tr>
                </thead>
                <tbody class="divide-y divide-garis tabular-nums">
                    @foreach ($p['contoh'] as $b)
                        <tr @class(['bg-danger-50 text-danger-700' => $b['galat']])>
                            <td class="px-2 py-1">{{ $b['baris'] }}</td>
                            <td class="px-2 py-1">{{ $b['nomor_induk'] ?? '—' }}</td>
                            <td class="px-2 py-1">{{ $b['nama'] ?: '—' }}</td>
                            <td class="px-2 py-1">{{ $b['keterangan'] ?? '—' }}</td>
                            <td class="px-2 py-1">{{ $b['galat'] ?? \Illuminate\Support\Carbon::parse($b['tanggal_lahir'])->translatedFormat('j M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($p['gagal'])
        <details class="text-xs text-danger-700">
            <summary class="cursor-pointer font-bold">{{ count($p['gagal']) }} baris akan dilewati</summary>
            <ul class="mt-1 list-disc pl-5">
                @foreach (array_slice($p['gagal'], 0, 20) as $g) <li>{{ $g }}</li> @endforeach
            </ul>
        </details>
    @endif
</div>
