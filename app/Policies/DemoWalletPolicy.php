<?php

namespace App\Policies;

use App\Domain\DemoWallet\Models\DemoWallet;
use App\Domain\Identity\Authorization\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Players see and reset only their own demo wallet. Other players' wallets
 * answer "not found" so their existence is not revealed. Staff with
 * demo_wallet.view can read any wallet, but only with two-factor enabled.
 */
class DemoWalletPolicy
{
    public function view(User $user, DemoWallet $wallet): Response
    {
        $isOwner = $wallet->user_id === $user->id;
        $isStaffViewer = $user->can(Permission::DemoWalletView->value) && $user->hasConfirmedTwoFactor();

        return $isOwner || $isStaffViewer
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function reset(User $user, DemoWallet $wallet): Response
    {
        return $wallet->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    public function adjust(User $user, DemoWallet $wallet): bool
    {
        return $user->can(Permission::DemoWalletAdjust->value)
            && $wallet->user_id !== $user->id;
    }
}
