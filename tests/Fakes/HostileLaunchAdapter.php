<?php

namespace Tests\Fakes;

use App\Domain\GameProviders\Adapters\LocalSimulatorAdapter;
use App\Domain\GameProviders\Data\LaunchResult;
use App\Domain\GameProviders\Models\GameSession;

/**
 * A misbehaving adapter that returns a launch URL off the allowlist.
 */
class HostileLaunchAdapter extends LocalSimulatorAdapter
{
    public function createDemoSession(GameSession $session, string $returnUrl): LaunchResult
    {
        return new LaunchResult('https://evil.example.test/steal?session='.$session->session_reference);
    }
}
