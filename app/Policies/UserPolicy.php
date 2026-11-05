<?php

namespace App\Policies;

use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use App\Models\User;

/**
 * Staff actions on player accounts. Staff accounts are managed separately
 * (staff.manage), never through player tools.
 */
class UserPolicy
{
    public function viewAnyPlayers(User $actor): bool
    {
        return $actor->can(Permission::PlayersView->value);
    }

    public function updatePlayerStatus(User $actor, User $target): bool
    {
        return $actor->can(Permission::PlayersSuspend->value)
            && ! $actor->is($target)
            && $target->hasRole(Role::Player->value)
            && ! $target->isStaff();
    }
}
