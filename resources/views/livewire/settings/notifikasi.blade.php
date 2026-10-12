<?php

use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    /** jenis => aktif, hanya jenis yang relevan untuk peran akun. */
    public array $pilihan = [];

    public function mount(): void
    {
        $akun = Auth::user();
        $this->pilihan = collect($akun->jenisNotifikasi())->mapWithKeys(fn ($j) => [$j => $akun->mauMenerima($j)])->all();
    }

    public function simpan(): void
    {
        $akun = Auth::user();

        foreach ($akun->jenisNotifikasi() as $jenis) {
            $akun->aturNotifikasi($jenis, (bool) ($this->pilihan[$jenis] ?? false));
        }

        $this->dispatch('preferensi-tersimpan');
    }

    public function with(): array
    {
        return ['jenis' => NotificationPreference::JENIS, 'jenisAkun' => Auth::user()->jenisNotifikasi()];
    }

    public function rendering($view): void
    {
        $view->title('Pengaturan Pemberitahuan');
    }
}; ?>

<x-settings.layout heading="Pemberitahuan akun" subheading="Pilih kabar yang ingin kamu terima di akun ini. Pemberitahuan tampil di lonceng.">
    <form wire:submit="simpan" class="flex flex-col gap-5">
        <div class="flex flex-col gap-2">
            @foreach ($jenisAkun as $kunci)
                <label wire:key="pref-{{ $kunci }}" class="flex cursor-pointer items-start gap-3 rounded-xl bg-hijau-50 px-4 py-3 ring-1 ring-garis has-[:checked]:ring-2 has-[:checked]:ring-hijau-600">
                    <input type="checkbox" wire:model="pilihan.{{ $kunci }}" class="mt-0.5 size-5 shrink-0 accent-hijau-600" />
                    <span>
                        <span class="block font-bold text-tinta">{{ $jenis[$kunci][0] }}</span>
                        <span class="block text-sm text-tinta-2">{{ $jenis[$kunci][1] }}</span>
                    </span>
                </label>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="simpan"
                    class="inline-flex items-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)] hover:bg-hijau-700 disabled:opacity-60">
                <flux:icon.check variant="micro" /> Simpan preferensi
            </button>
            <x-action-message on="preferensi-tersimpan">Preferensi tersimpan.</x-action-message>
        </div>
    </form>
</x-settings.layout>
