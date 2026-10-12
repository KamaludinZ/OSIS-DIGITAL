<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

/** Pengumuman dikelola pengurus OSIM dan admin. */
class AnnouncementPolicy
{
    public function viewAny(User $pengguna): bool { return $pengguna->can('kelola-publikasi'); }

    public function view(User $pengguna, Announcement $a): bool { return $pengguna->can('kelola-publikasi'); }

    public function create(User $pengguna): bool { return $pengguna->can('kelola-publikasi'); }

    public function update(User $pengguna, Announcement $a): bool { return $pengguna->can('kelola-publikasi'); }

    public function delete(User $pengguna, Announcement $a): bool { return $pengguna->can('kelola-publikasi'); }

    public function deleteAny(User $pengguna): bool { return $pengguna->can('kelola-publikasi'); }
}
