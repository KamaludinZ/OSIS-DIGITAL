<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Voter;

/** Data pemilih (NISN/NIK, tanggal lahir, kode pilih) hanya untuk panitia dan admin. */
class VoterPolicy
{
    private function boleh(User $pengguna): bool
    {
        return $pengguna->can('kelola-pemilih');
    }

    public function viewAny(User $pengguna): bool { return $this->boleh($pengguna); }

    public function view(User $pengguna, Voter $voter): bool { return $this->boleh($pengguna); }

    public function create(User $pengguna): bool { return $this->boleh($pengguna); }

    public function update(User $pengguna, Voter $voter): bool { return $this->boleh($pengguna); }

    public function delete(User $pengguna, Voter $voter): bool { return $this->boleh($pengguna); }

    public function deleteAny(User $pengguna): bool { return $this->boleh($pengguna); }
}
