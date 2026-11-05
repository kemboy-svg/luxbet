<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Authorization\Permission;
use App\Domain\Identity\Authorization\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes the database match the Role and Permission enums exactly.
 * Idempotent; safe to run on every deploy.
 */
class SyncRolesAndPermissions
{
    public const GUARD = 'web';

    public function __construct(private readonly PermissionRegistrar $registrar) {}

    public function __invoke(): void
    {
        $this->registrar->forgetCachedPermissions();

        DB::transaction(function (): void {
            foreach (Permission::cases() as $permission) {
                PermissionModel::findOrCreate($permission->value, self::GUARD);
            }

            PermissionModel::query()
                ->where('guard_name', self::GUARD)
                ->whereNotIn('name', Permission::names())
                ->delete();

            foreach (Role::cases() as $role) {
                RoleModel::findOrCreate($role->value, self::GUARD)->syncPermissions(
                    array_map(fn (Permission $permission): string => $permission->value, $role->permissions()),
                );
            }
        });

        $this->registrar->forgetCachedPermissions();
    }
}
