<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    public string $name = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name;
    }

    /**
     * Pengguna hanya mengubah nama tampilan. Email & peran dikelola admin
     * (akun dibuat dan direset dari Ruang OSIM → Akun Pengguna).
     */
    public function updateProfileInformation(): void
    {
        $validated = $this->validate(
            ['name' => ['required', 'string', 'max:100']],
            ['name.required' => 'Nama tidak boleh kosong.'],
        );

        Auth::user()->update($validated);

        $this->dispatch('profile-updated', name: $validated['name']);
    }

    public function rendering($view): void
    {
        $view->title('Pengaturan Akun');
    }
}; ?>

<x-settings.layout heading="Profil" subheading="Nama ini tampil di Ruang OSIM dan pada tanggapan tanya jawab.">
    <form wire:submit="updateProfileInformation" class="flex flex-col gap-5">
        <div>
            <label for="nama" class="font-bold text-tinta">Nama</label>
            <input id="nama" wire:model="name" type="text" required autocomplete="name" maxlength="100"
                   @class(['mt-1.5 h-12 w-full rounded-xl border-2 bg-hijau-50/50 px-4 text-tinta focus:bg-white focus:outline-none', 'border-cap' => $errors->has('name'), 'border-garis focus:border-hijau-600' => ! $errors->has('name')]) />
            @error('name') <p class="mt-1.5 flex items-center gap-1.5 text-sm font-bold text-cap" role="alert"><flux:icon.exclamation-circle variant="micro" /> {{ $message }}</p> @enderror
        </div>

        <dl class="grid gap-3 rounded-xl bg-hijau-50 p-4 text-sm ring-1 ring-garis sm:grid-cols-2">
            <div>
                <dt><x-osim.label>Email sekolah</x-osim.label></dt>
                <dd class="mt-0.5 break-all font-semibold text-tinta">{{ auth()->user()->email }}</dd>
            </div>
            <div>
                <dt><x-osim.label>Peran</x-osim.label></dt>
                <dd class="mt-0.5 font-semibold text-tinta">
                    {{ auth()->user()->role->label() }}@if (auth()->user()->kandidat) · No. {{ auth()->user()->kandidat->nomor_urut }}@endif
                </dd>
            </div>
            <p class="flex items-start gap-1.5 text-tinta-2 sm:col-span-2">
                <flux:icon.information-circle variant="micro" class="mt-0.5 shrink-0 text-hijau-600" />
                Email dan peran diatur oleh admin madrasah. Hubungi admin bila perlu diganti.
            </p>
        </dl>

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="updateProfileInformation"
                    class="inline-flex items-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)] hover:bg-hijau-700 disabled:opacity-60">
                <flux:icon.check variant="micro" /> Simpan
            </button>
            <x-action-message on="profile-updated" class="flex items-center gap-1.5 text-sm font-bold text-hijau-700">
                Tersimpan.
            </x-action-message>
        </div>
    </form>
</x-settings.layout>
