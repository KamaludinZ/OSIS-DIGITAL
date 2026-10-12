<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Data organisasi OSIM (divisi, anggota, program kerja, kegiatan)
 * hanya boleh dikelola pengurus dan admin. Panitia & tamu ditolak di server.
 */
class KelolaOrganisasiPolicy
{
    private function boleh(User $pengguna): bool
    {
        return $pengguna->can('kelola-organisasi');
    }

    public function viewAny(User $pengguna): bool { return $this->boleh($pengguna); }

    public function view(User $pengguna, Model $model): bool { return $this->boleh($pengguna); }

    public function create(User $pengguna): bool { return $this->boleh($pengguna); }

    public function update(User $pengguna, Model $model): bool { return $this->boleh($pengguna); }

    public function delete(User $pengguna, Model $model): bool { return $this->boleh($pengguna); }

    public function deleteAny(User $pengguna): bool { return $this->boleh($pengguna); }
}
