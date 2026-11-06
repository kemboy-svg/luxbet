<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Exceptions\StaffManagementException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Creates staff accounts and grants or revokes staff roles.
 *
 * Rules, enforced here regardless of the caller:
 * - staff accounts are separate from player accounts: player accounts can
 *   never hold a staff role, and the player role is never granted here;
 * - nobody changes their own roles;
 * - at least one super_admin always remains;
 * - every change is audited.
 */
class ManageStaff
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Creates a staff account with an unusable random password and sends a
     * password-reset link so the new staff member sets their own.
     */
    public function create(User $actor, string $name, string $email, Role $role, string $reason): User
    {
        $this->assertStaffRole($role);

        $user = DB::transaction(function () use ($actor, $name, $email, $role, $reason): User {
            if (User::query()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->exists()) {
                throw new StaffManagementException('An account with that email already exists.');
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Str::password(48),
            ]);
            $user->assignRole($role->value);

            $this->audit->record('staff.created', subject: $user, actor: $actor, reason: $reason, metadata: [
                'role' => $role->value,
            ]);

            return $user;
        });

        Password::sendResetLink(['email' => $user->email]);

        return $user;
    }

    public function grant(User $actor, User $target, Role $role, string $reason): void
    {
        $this->assertStaffRole($role);
        $this->assertNotSelf($actor, $target);

        if ($target->hasRole(Role::Player->value)) {
            throw new StaffManagementException('Player accounts cannot hold staff roles. Create a separate staff account.');
        }

        if ($target->hasRole($role->value)) {
            throw new StaffManagementException('That account already has this role.');
        }

        DB::transaction(function () use ($actor, $target, $role, $reason): void {
            $target->assignRole($role->value);

            $this->audit->record('staff.role_granted', subject: $target, actor: $actor, reason: $reason, metadata: [
                'role' => $role->value,
            ]);
        });
    }

    public function revoke(User $actor, User $target, Role $role, string $reason): void
    {
        $this->assertStaffRole($role);
        $this->assertNotSelf($actor, $target);

        DB::transaction(function () use ($actor, $target, $role, $reason): void {
            if ($role === Role::SuperAdmin) {
                // Lock the super-admin assignments so two concurrent revokes
                // cannot both see "two remain" and leave none.
                $roleId = RoleModel::findByName(Role::SuperAdmin->value, SyncRolesAndPermissions::GUARD)->getKey();
                $holders = DB::table(config('permission.table_names.model_has_roles'))
                    ->where('role_id', $roleId)
                    ->lockForUpdate()
                    ->pluck(config('permission.column_names.model_morph_key'));

                if ($holders->count() <= 1 && $holders->contains($target->id)) {
                    throw new StaffManagementException('At least one super admin must remain.');
                }
            }

            if (! $target->hasRole($role->value)) {
                throw new StaffManagementException('That account does not have this role.');
            }

            $target->removeRole($role->value);

            $this->audit->record('staff.role_revoked', subject: $target, actor: $actor, reason: $reason, metadata: [
                'role' => $role->value,
            ]);
        });
    }

    private function assertStaffRole(Role $role): void
    {
        if (! $role->isStaff()) {
            throw new StaffManagementException('Only staff roles can be managed here.');
        }
    }

    private function assertNotSelf(User $actor, User $target): void
    {
        if ($actor->is($target)) {
            throw new StaffManagementException('You cannot change your own roles.');
        }
    }
}
