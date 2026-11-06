<?php

namespace App\Console\Commands;

use App\Domain\Identity\Actions\SyncRolesAndPermissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('luxbet:sync-permissions')]
#[Description('Sync roles and permissions in the database with the code definitions')]
class SyncPermissionsCommand extends Command
{
    public function handle(SyncRolesAndPermissions $sync): int
    {
        $sync();

        $this->components->info('Roles and permissions are in sync.');

        return self::SUCCESS;
    }
}
