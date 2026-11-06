<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\ManageStaff;
use App\Domain\Identity\Authorization\Role;
use App\Domain\Identity\Exceptions\StaffManagementException;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff accounts and their roles (staff.manage). The rules themselves live
 * in ManageStaff; this controller only validates input.
 */
class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $actor */
        $actor = $request->user();

        $staff = User::query()
            ->role(Role::staffRoleNames())
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'two_factor' => $user->hasConfirmedTwoFactor(),
                'roles' => $user->getRoleNames()->values()->all(),
                'is_self' => $user->is($actor),
            ])
            ->all();

        return Inertia::render('admin/Staff', [
            'staff' => $staff,
            'roles' => array_map(
                fn (string $role): array => ['value' => $role, 'label' => Role::from($role)->label()],
                Role::staffRoleNames(),
            ),
        ]);
    }

    public function store(Request $request, ManageStaff $manage): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'role' => ['required', Rule::in(Role::staffRoleNames())],
            'reason' => ['required', 'string', 'min:5', 'max:200'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $manage->create(
                $actor,
                (string) $validated['name'],
                (string) $validated['email'],
                Role::from((string) $validated['role']),
                trim((string) $validated['reason']),
            );
        } catch (StaffManagementException $e) {
            return back()->withErrors(['email' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Staff account created. A password link has been sent.']);

        return back();
    }

    public function grant(Request $request, User $staff, ManageStaff $manage): RedirectResponse
    {
        return $this->change($request, $staff, fn (User $actor, Role $role, string $reason) => $manage->grant($actor, $staff, $role, $reason), 'Role granted.');
    }

    public function revoke(Request $request, User $staff, ManageStaff $manage): RedirectResponse
    {
        return $this->change($request, $staff, fn (User $actor, Role $role, string $reason) => $manage->revoke($actor, $staff, $role, $reason), 'Role removed.');
    }

    /**
     * @param  callable(User, Role, string): void  $apply
     */
    private function change(Request $request, User $staff, callable $apply, string $message): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::in(Role::staffRoleNames())],
            'reason' => ['required', 'string', 'min:5', 'max:200'],
        ]);

        /** @var User $actor */
        $actor = $request->user();

        try {
            $apply($actor, Role::from((string) $validated['role']), trim((string) $validated['reason']));
        } catch (StaffManagementException $e) {
            return back()->withErrors(['role' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }
}
