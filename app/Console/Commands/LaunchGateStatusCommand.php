<?php

namespace App\Console\Commands;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Read-only report of the real-money launch gate. There is intentionally
 * no command, route or setting that changes it.
 */
#[Signature('luxbet:launch-gate')]
#[Description('Show the read-only status of the real-money launch gate')]
class LaunchGateStatusCommand extends Command
{
    public function handle(LaunchGate $gate): int
    {
        $status = $gate->status();

        $this->components->twoColumnDetail('Real-money mode', $status->realMoneyEnabled ? '<fg=red>ENABLED</>' : '<fg=green>DISABLED</>');
        $this->components->twoColumnDetail('Environment mode', $status->environmentMode->value);
        $this->components->twoColumnDetail('Launch-control record', $status->recordPresent ? 'present (v'.$status->recordVersion.')' : 'missing');
        $this->components->twoColumnDetail('Review due', $status->reviewDueAt?->toDateString() ?? '—');

        $this->newLine();
        $this->components->info('Blocking reasons:');
        $this->components->bulletList($status->blockingReasons);

        return self::SUCCESS;
    }
}
