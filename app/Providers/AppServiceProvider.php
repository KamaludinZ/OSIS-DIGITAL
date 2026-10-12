<?php

namespace App\Providers;

use App\Models\Election;
use App\Models\User;
use App\Services\LayananPemilihan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\Announcement::class, \App\Policies\AnnouncementPolicy::class);

        foreach ([\App\Models\Division::class, \App\Models\Member::class, \App\Models\WorkProgram::class, \App\Models\Event::class] as $model) {
            Gate::policy($model, \App\Policies\KelolaOrganisasiPolicy::class);
        }

        Gate::define('kelola-publikasi', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Pengurus, \App\Enums\Peran::Admin], true));

        // Perolehan per kandidat: publik setelah dipublikasikan; panitia & admin selalu.
        Gate::define('lihat-perolehan', fn (?User $pengguna, Election $pemilihan) => $pemilihan->hasil_publik
            || in_array($pengguna?->role, [\App\Enums\Peran::Panitia, \App\Enums\Peran::Admin], true));

        Gate::define('kelola-organisasi', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Pengurus, \App\Enums\Peran::Admin], true));

        Gate::define('kelola-pemilih', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Panitia, \App\Enums\Peran::Admin], true));

        Gate::define('kelola-pemilihan', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Panitia, \App\Enums\Peran::Admin], true));

        Gate::define('kelola-akun', fn (User $pengguna) => $pengguna->role === \App\Enums\Peran::Admin);

        // Kandidat hanya menanggapi pertanyaan untuk dirinya sendiri.
        Gate::define('jawab-pertanyaan', fn (User $pengguna, \App\Models\CandidateQuestion $tanya) => $pengguna->role === \App\Enums\Peran::Kandidat
            && $pengguna->candidate_id !== null && $pengguna->candidate_id === $tanya->candidate_id);

        Gate::define('publikasikan-hasil', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Panitia, \App\Enums\Peran::Admin], true));

    }
}
