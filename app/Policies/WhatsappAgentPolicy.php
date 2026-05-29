<?php

namespace App\Policies;

use App\Models\User;
use JeffersonGoncalves\WhatsappWidget\Models\WhatsappAgent;

class WhatsappAgentPolicy
{
    private function isAdmin(User $user): bool
    {
        return (bool) $user->getAttribute('is_admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, WhatsappAgent $whatsappAgent): bool
    {
        return $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, WhatsappAgent $whatsappAgent): bool
    {
        return $this->isAdmin($user);
    }

    public function delete(User $user, WhatsappAgent $whatsappAgent): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function restore(User $user, WhatsappAgent $whatsappAgent): bool
    {
        return $this->isAdmin($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDelete(User $user, WhatsappAgent $whatsappAgent): bool
    {
        return $this->isAdmin($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function replicate(User $user, WhatsappAgent $whatsappAgent): bool
    {
        return $this->isAdmin($user);
    }

    public function reorder(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
