<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.siswa')] class extends Component {
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function updatePassword(): void
    {
        // Batasi tebakan kata sandi saat ini: 5 percobaan per menit per akun.
        $kunci = 'ganti-sandi:'.Auth::id();

        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw ValidationException::withMessages([
                'current_password' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.',
            ]);
        }

        RateLimiter::hit($kunci, 60);

        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::min(8), 'confirmed', 'different:current_password'],
            ], [
                'password.different' => 'Kata sandi baru harus berbeda dari yang lama.',
                'current_password.current_password' => 'Kata sandi saat ini salah.',
                'password.confirmed' => 'Ulangi kata sandi baru dengan sama persis.',
            ], [
                'current_password' => 'Kata sandi saat ini',
                'password' => 'Kata sandi baru',
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        RateLimiter::clear($kunci);

        // Model User meng-hash kata sandi lewat cast "hashed".
        Auth::user()->update(['password' => $validated['password']]);

        // Sesi ini tetap masuk; sesi di perangkat lain berakhir (AuthenticateSession).
        session()->put('password_hash_'.Auth::getDefaultDriver(), Auth::user()->getAuthPassword());

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('password-updated');
    }

    public function rendering($view): void
    {
        $view->title('Ganti Kata Sandi');
    }
}; ?>

<x-settings.layout heading="Ganti kata sandi" subheading="Gunakan minimal 8 karakter dan jangan dibagikan ke siapa pun.">
    <form wire:submit="updatePassword" class="flex flex-col gap-5">
        @foreach ([
            ['current_password', 'Kata sandi saat ini', 'current-password'],
            ['password', 'Kata sandi baru', 'new-password'],
            ['password_confirmation', 'Ulangi kata sandi baru', 'new-password'],
        ] as [$kolom, $label, $isiOtomatis])
            <div>
                <label for="{{ $kolom }}" class="font-bold text-tinta">{{ $label }}</label>
                <input id="{{ $kolom }}" wire:model="{{ $kolom }}" type="password" required autocomplete="{{ $isiOtomatis }}"
                       @class(['mt-1.5 h-12 w-full rounded-xl border-2 bg-hijau-50/50 px-4 text-tinta focus:bg-white focus:outline-none', 'border-cap' => $errors->has($kolom), 'border-garis focus:border-hijau-600' => ! $errors->has($kolom)]) />
                @error($kolom) <p class="mt-1.5 flex items-center gap-1.5 text-sm font-bold text-cap" role="alert"><flux:icon.exclamation-circle variant="micro" /> {{ $message }}</p> @enderror
            </div>
        @endforeach

        <div class="flex flex-wrap items-center gap-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="updatePassword"
                    class="inline-flex items-center gap-2 rounded-xl bg-hijau-600 px-5 py-3 font-bold text-white shadow-[0_4px_10px_-3px_var(--color-hijau-900)] hover:bg-hijau-700 disabled:opacity-60">
                <flux:icon.key variant="micro" /> Ganti kata sandi
            </button>
            <x-action-message on="password-updated" class="text-sm font-bold text-hijau-700">Kata sandi diganti.</x-action-message>
        </div>
    </form>
</x-settings.layout>
