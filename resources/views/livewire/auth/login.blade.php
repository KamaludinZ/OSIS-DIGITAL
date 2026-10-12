<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] #[\Livewire\Attributes\Title('Masuk')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Login hanya untuk admin, pengurus, panitia, dan akun kandidat. Siswa & GTK
        // memilih dengan kode pilih dan bertanya lewat verifikasi NISN/NIK.
        $pengguna = Auth::user();

        if ($pengguna->role === \App\Enums\Peran::Kandidat && $pengguna->candidate_id) {
            RateLimiter::clear($this->throttleKey());
            Session::regenerate();

            // Kandidat langsung ke tanya jawab miliknya untuk menanggapi pertanyaan.
            $this->redirect(route('kandidat.profil', ['id' => $pengguna->candidate_id, 'tab' => 'tanya-jawab']), navigate: false);

            return;
        }

        if (! $pengguna->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Login hanya untuk admin, pengurus, panitia, dan kandidat. Untuk memilih, gunakan kode pilih di Bilik Suara.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: '/admin', navigate: false);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="overflow-hidden rounded-[14px] bg-white shadow-[0_18px_40px_-18px_rgb(4_41_26/0.5)] ring-1 ring-garis">
    <div class="flex items-center justify-between gap-3 bg-hijau-100 px-6 py-2.5">
        <x-osim.label class="text-hijau-800">Kartu Masuk Pengurus &amp; Panitia</x-osim.label>
        <img src="/images/brand/logo-mtsn2.png" alt="" class="size-6" />
    </div>

    <div class="p-6 sm:p-8">
        <h1 class="font-display text-3xl font-black text-tinta">Masuk pengurus</h1>
        <p class="mt-1 text-tinta-2">Khusus admin, pengurus OSIM, panitia pemilihan, dan kandidat.</p>

        @if (session('status'))
            <p class="mt-4 rounded-lg bg-hijau-100 px-3 py-2 text-sm font-semibold text-hijau-800" role="status">{{ session('status') }}</p>
        @endif

        <form wire:submit="login" class="mt-6 flex flex-col gap-5">
            <div>
                <label for="email" class="font-bold text-tinta">Email sekolah</label>
                <input id="email" wire:model="email" type="email" name="email" required autofocus autocomplete="email" placeholder="Email sekolahmu"
                       @class(['mt-1.5 h-12 w-full rounded-xl border-2 bg-hijau-50/50 px-4 text-tinta placeholder:text-tinta-2/60 focus:bg-white focus:outline-none', 'border-cap focus:border-cap' => $errors->has('email'), 'border-garis focus:border-hijau-600' => ! $errors->has('email')])
                       @error('email') aria-invalid="true" aria-describedby="email-galat" @enderror />
                @error('email')
                    <p id="email-galat" class="mt-1.5 flex items-start gap-1.5 text-sm font-bold text-cap" role="alert"><flux:icon.exclamation-circle variant="micro" class="mt-0.5 shrink-0" /> {{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ lihat: false }">
                <label for="password" class="font-bold text-tinta">Kata sandi</label>
                <div class="relative mt-1.5">
                    <input id="password" wire:model="password" :type="lihat ? 'text' : 'password'" type="password" name="password" required autocomplete="current-password"
                           class="h-12 w-full rounded-xl border-2 border-garis bg-hijau-50/50 px-4 pr-12 text-tinta focus:border-hijau-600 focus:bg-white focus:outline-none" />
                    <button type="button" x-on:click="lihat = ! lihat" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-tinta-2 hover:text-hijau-700" :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                        <flux:icon.eye variant="mini" x-show="! lihat" />
                        <flux:icon.eye-slash variant="mini" x-show="lihat" x-cloak />
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-sm font-bold text-cap" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2.5 text-tinta">
                <input type="checkbox" wire:model="remember" class="size-5 rounded border-2 border-garis text-hijau-600 accent-hijau-600" />
                Ingat saya di perangkat ini
            </label>

            <button type="submit" wire:loading.attr="disabled" wire:target="login"
                    class="inline-flex h-13 items-center justify-center gap-2 rounded-xl bg-emas-400 py-3.5 text-lg font-black text-hijau-950 shadow-[0_4px_10px_-3px_var(--color-emas-500)] hover:bg-emas-300 active:translate-y-0.5 disabled:opacity-70">
                <span wire:loading.remove wire:target="login">Masuk</span>
                <span wire:loading wire:target="login">Memeriksa…</span>
                <flux:icon.arrow-right variant="mini" wire:loading.remove wire:target="login" />
            </button>
        </form>
    </div>

    <div class="osim-perforasi" style="--osim-takik: var(--color-hijau-50)"></div>

    <div class="flex gap-3 p-6 text-sm text-tinta-2 sm:px-8">
        <flux:icon.information-circle variant="mini" class="mt-0.5 shrink-0 text-hijau-600" />
        <p>
            <strong class="text-tinta">Siswa atau guru?</strong> Tidak perlu login. Memilih cukup dengan kode pilih 6 digit di
            <a href="{{ route('bilik-suara') }}" class="font-bold text-hijau-700 underline" wire:navigate>Bilik Suara</a>,
            dan bertanya ke kandidat cukup dengan NISN/NIK + tanggal lahir.
            <br><strong class="text-tinta">Belum punya akun atau lupa kata sandi?</strong> Hubungi admin madrasah.
        </p>
    </div>
</div>
