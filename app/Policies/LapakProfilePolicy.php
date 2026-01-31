<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LapakProfile;

class LapakProfilePolicy
{
    /**
     * Semua user boleh akses halaman (data difilter di query)
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Admin boleh lihat semua
     * User hanya boleh lihat lapak miliknya
     */
    public function view(User $user, LapakProfile $lapak): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $lapak->user_id === $user->id;
    }

    /**
     * ❌ Admin tidak boleh update
     * ✅ User hanya bisa update lapaknya sendiri
     */
    public function update(User $user, LapakProfile $lapak): bool
    {
        if ($user->is_admin) {
            return false;
        }

        return $lapak->user_id === $user->id;
    }
}
