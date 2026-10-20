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
        // Satu penentu mode per permintaan (dipakai layout, middleware, dan Ruang OSIM).
        $this->app->scoped(\App\Services\LayananModePemilihan::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Aksi Livewire di halaman pemilihan ikut diperiksa ulang terhadap sakelar mode.
        \Illuminate\Support\Facades\View::composer('components.layouts.siswa', \App\View\Composers\NavigasiPublik::class);

        \Livewire\Livewire::addPersistentMiddleware([\App\Http\Middleware\WajibModePemilihan::class]);

        Gate::policy(User::class, \App\Policies\UserPolicy::class);
        Gate::policy(\App\Models\Announcement::class, \App\Policies\AnnouncementPolicy::class);
        Gate::policy(\App\Models\Voter::class, \App\Policies\VoterPolicy::class);

        foreach ([\App\Models\Election::class, \App\Models\Candidate::class, \App\Models\CampaignMaterial::class, \App\Models\CampaignSchedule::class, \App\Models\CandidateQuestion::class] as $model) {
            Gate::policy($model, \App\Policies\KelolaPemilihanPolicy::class);
        }

        foreach ([\App\Models\Division::class, \App\Models\Member::class, \App\Models\WorkProgram::class, \App\Models\Event::class, \App\Models\OsisPeriod::class] as $model) {
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

        // Saran hanya ditanggapi pengurus yang masih menjabat (users.is_aktif, akun aktif),
        // dan hanya saran milik periode aktif; saran periode arsip hanya bisa dibaca.
        Gate::define('tanggapi-saran', fn (User $pengguna, ?\Illuminate\Database\Eloquent\Model $saran = null) => $pengguna->menjabat()
            && ($aktif = \App\Models\OsisPeriod::aktif()) !== null
            && ($saran === null || (int) $saran->periode_id === $aktif->id));

        // Sakelar mode pemilihan mengubah menu publik seluruh situs: panitia, admin,
        // dan pengurus yang masih menjabat. Pengurus tidak menjabat hanya melihat statusnya.
        Gate::define('ubah-mode-pemilihan', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Panitia, \App\Enums\Peran::Admin], true)
            || $pengguna->menjabat());

        Gate::define('publikasikan-hasil', fn (User $pengguna) => in_array($pengguna->role, [\App\Enums\Peran::Panitia, \App\Enums\Peran::Admin], true));

    }
}
