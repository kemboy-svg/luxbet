<?php

namespace App\Policies;

use App\Domain\GameProviders\Models\GameSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Players only see and play their own sessions. Others answer "not found".
 */
class GameSessionPolicy
{
    public function view(User $user, GameSession $session): Response
    {
        return $session->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function play(User $user, GameSession $session): Response
    {
        return $this->view($user, $session);
    }
}
