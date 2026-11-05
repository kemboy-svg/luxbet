<?php

use App\Domain\Identity\Actions\SyncRolesAndPermissions;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the baseline roles and permissions so every installation (and
 * every test database) has them. Later changes to the Role/Permission enums
 * are applied with `php artisan luxbet:sync-permissions`.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(SyncRolesAndPermissions::class)();
    }

    public function down(): void
    {
        // Rolled back together with the permission tables.
    }
};
