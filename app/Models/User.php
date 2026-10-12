<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Peran;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser // implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    /** Akun baru langsung aktif (sama dengan bawaan kolom). */
    protected $attributes = ['aktif' => true];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'kelas',
        'candidate_id',
        'aktif',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'terakhir_masuk_pada' => 'datetime',
            'role' => Peran::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /** Ruang OSIM (panel Filament) hanya untuk pengurus, panitia, dan admin. */
    /** Kandidat yang diwakili akun berperan kandidat. */
    public function kandidat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    public function preferensiNotifikasi(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /** Jenis pemberitahuan yang relevan untuk peran ini. */
    public function jenisNotifikasi(): array
    {
        return match ($this->role) {
            Peran::Kandidat => ['pertanyaan', 'pemilihan', 'pengumuman'],
            Peran::Panitia, Peran::Admin => ['pemilihan', 'pengumuman'],
            Peran::Pengurus => ['pengumuman', 'pemilihan'],
            default => [],
        };
    }

    /** Mau menerima jenis ini? Bawaan: ya, selama relevan untuk perannya. */
    public function mauMenerima(string $jenis): bool
    {
        if (! in_array($jenis, $this->jenisNotifikasi(), true)) {
            return false;
        }

        return $this->preferensiNotifikasi->firstWhere('jenis', $jenis)?->aktif ?? true;
    }

    public function aturNotifikasi(string $jenis, bool $aktif): void
    {
        $this->preferensiNotifikasi()->updateOrCreate(['jenis' => $jenis], ['aktif' => $aktif]);
        $this->unsetRelation('preferensiNotifikasi');
    }

    /** Halaman pertama setelah masuk, sesuai peran. */
    public function halamanAwal(): string
    {
        return match (true) {
            $this->role === Peran::Kandidat && $this->candidate_id !== null => route('kandidat.profil', ['id' => $this->candidate_id, 'tab' => 'tanya-jawab']),
            in_array($this->role, [Peran::Pengurus, Peran::Panitia, Peran::Admin], true) => '/admin',
            default => route('home'),
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->aktif && in_array($this->role, [Peran::Pengurus, Peran::Panitia, Peran::Admin], true);
    }
}
