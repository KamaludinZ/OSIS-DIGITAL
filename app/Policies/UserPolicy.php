<?php

namespace App\Policies;

use App\Models\User;

/** Akun hanya dibuat, diubah, dan dihapus oleh admin; admin tidak bisa menghapus akunnya sendiri. */
class UserPolicy
{
    public function viewAny(User $pengguna): bool { return $pengguna->can('kelola-akun'); }

    public function view(User $pengguna, User $akun): bool { return $pengguna->can('kelola-akun'); }

    public function create(User $pengguna): bool { return $pengguna->can('kelola-akun'); }

    public function update(User $pengguna, User $akun): bool { return $pengguna->can('kelola-akun'); }

    public function delete(User $pengguna, User $akun): bool { return $pengguna->can('kelola-akun') && ! $pengguna->is($akun); }

    public function deleteAny(User $pengguna): bool { return $pengguna->can('kelola-akun'); }
}
