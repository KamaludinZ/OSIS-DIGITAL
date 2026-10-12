<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Data pemilihan (periode, kandidat, materi & jadwal kampanye, moderasi
 * tanya jawab) hanya dikelola panitia dan admin.
 */
class KelolaPemilihanPolicy
{
    private function boleh(User $pengguna): bool
    {
        return $pengguna->can('kelola-pemilihan');
    }

    public function viewAny(User $pengguna): bool { return $this->boleh($pengguna); }

    public function view(User $pengguna, Model $model): bool { return $this->boleh($pengguna); }

    public function create(User $pengguna): bool { return $this->boleh($pengguna); }

    public function update(User $pengguna, Model $model): bool { return $this->boleh($pengguna); }

    public function delete(User $pengguna, Model $model): bool { return $this->boleh($pengguna); }

    public function deleteAny(User $pengguna): bool { return $this->boleh($pengguna); }

    public function reorder(User $pengguna): bool { return $this->boleh($pengguna); }
}
