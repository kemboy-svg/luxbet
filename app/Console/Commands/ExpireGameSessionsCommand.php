<?php

namespace App\Console\Commands;

use App\Domain\GameProviders\Actions\ExpireGameSessions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('luxbet:expire-game-sessions')]
#[Description('Mark demo game sessions past their expiry time as expired')]
class ExpireGameSessionsCommand extends Command
{
    public function handle(ExpireGameSessions $expire): int
    {
        $count = $expire();

        $this->components->info("Expired {$count} demo game session(s).");

        return self::SUCCESS;
    }
}
