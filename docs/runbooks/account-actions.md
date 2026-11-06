# Account actions

## Suspend or close a player

Admin → Players → open the player → **Account status** (needs
`players.suspend`). A reason is required and the change is audited. The
player is signed out on their next request and cannot sign in while
suspended. Closing is permanent from the player's point of view; data is
kept, not deleted.

## Remove a staff member's access

Admin → Staff → **Remove role** for each of their roles (needs
`staff.manage`; you cannot change your own roles, and the last super admin
cannot be removed). With no staff roles left they lose admin access
immediately. If the account may be compromised, also:

```bash
docker compose exec app php artisan tinker
>>> $u = App\Models\User::where('email', 'them@example.com')->first();
>>> $u->forceFill(['status' => 'suspended'])->save();
```

Then review Admin → Audit log filtered by their email for recent actions.

## Lost two-factor device (staff)

Staff cannot use `/admin` without 2FA. After verifying identity out of band,
another super admin can reset it:

```bash
docker compose exec app php artisan tinker
>>> App\Models\User::where('email', 'them@example.com')->first()
...     ->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
```

They must set up 2FA again before using the admin area. Record it in the
incident log.
