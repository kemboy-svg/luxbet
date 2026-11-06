# Access-control review

Milestone 7 review of every route against the permission model in
[security.md](security.md). Code review completed on 2026-11-06 against
`main` after Milestone 6. Re-run this review whenever routes, roles or policies change.

`tests/Feature/Security/RouteInventoryTest.php` keeps the structural rules
true automatically:

- every `/admin` route has `auth`, `verified`, `can:admin.access` and
  `staff.mfa`, plus its own permission (route middleware, FormRequest or
  policy);
- every state-changing route requires sign-in, except the sign-in flows and
  signed provider webhooks;
- no route looks like a deposit, withdrawal, payment or cash-out endpoint;
- staff management requires `staff.manage` and password re-confirmation.

## Public

| Route                                                          | Protection                                                  | Notes                                           |
| -------------------------------------------------------------- | ----------------------------------------------------------- | ----------------------------------------------- |
| `GET /`, `/games`, `/games/{slug}`                             | none                                                        | Catalogue only; no provider URLs or secrets     |
| `GET /api/v1/health`, `/api/v1/games*`                         | `throttle:api`                                              | Same public catalogue data                      |
| `POST /api/v1/webhooks/providers/{slug}`                       | HMAC signature, replay window, body limit, `throttle:600,1` | Verified before anything is stored              |
| Sign-in, registration, password reset, 2FA challenge, passkeys | Fortify rate limits, `ThrottleRegistration`                 | Account status checked after a correct password |

## Players (signed in)

| Route                                             | Protection                                                            | Ownership rule                                    |
| ------------------------------------------------- | --------------------------------------------------------------------- | ------------------------------------------------- |
| `GET /dashboard`, `/favourites`, `/play-history`  | `auth`, `verified`                                                    | Queries scoped to `user_id` of the signed-in user |
| `POST/DELETE /games/{slug}/favourite`             | `auth`, `verified`, `throttle:60,1`                                   | Own favourites only; listed games only            |
| `GET /demo-wallet`, `POST /demo-wallet/reset`     | `auth`, `verified`; reset `throttle:10,1` + `DemoWalletPolicy::reset` | Own wallet only                                   |
| `POST /games/{slug}/play`                         | `auth`, `verified`, `throttle:20,1`                                   | Creates a session for the signed-in user          |
| `GET /play/{session}`                             | `auth`, `verified`, `signed` + `GameSessionPolicy`                    | Owner only; signed, expiring URL                  |
| `POST /play/{session}/end`                        | `auth`, `verified` + `GameSessionPolicy`                              | Owner only                                        |
| `/settings/*`                                     | `auth` (+ `verified` for security, responsible play, account closure) | Own account only; closure needs the password      |
| `/api/v1/demo-wallets*`, `/api/v1/game-sessions*` | `api.session` (CSRF), `auth`, `verified`, throttles + policies        | Other users' wallets and sessions return 404      |

## Staff (`/admin`, all need `auth`, `verified`, `can:admin.access`, `staff.mfa`)

| Route                                                          | Permission                                              | Extra rules                                                                                       |
| -------------------------------------------------------------- | ------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `GET /admin`                                                   | `admin.access`                                          | Each block shown only with its own permission                                                     |
| `GET /admin/players`, `/admin/players/{id}`                    | `players.view`                                          | Detail page: ledger needs `demo_wallet.view`, audit trail `audit.view`; staff accounts return 404 |
| `PATCH /admin/players/{id}/status`                             | `players.suspend` (FormRequest + `UserPolicy`)          | Not self, not staff; reason required; audited                                                     |
| `POST /admin/players/{id}/demo-wallet/adjustments`             | `demo_wallet.adjust` (FormRequest + `DemoWalletPolicy`) | Not self, not staff; reason and idempotency key; capped; audited                                  |
| `GET /admin/games`, `PATCH /admin/games/{id}`                  | `games.view` / `games.manage`                           | Only whitelisted fields; audited                                                                  |
| `GET /admin/providers`, `PATCH .../status`                     | `providers.view` / `providers.manage`                   | Cannot activate or switch to production; audited                                                  |
| `GET /admin/provider-events`                                   | `providers.view`                                        | Metadata only; payloads never stored                                                              |
| `GET /admin/demo-transactions`                                 | `demo_wallet.view`                                      | Read-only                                                                                         |
| `GET /admin/audit-log`                                         | `audit.view`                                            | Read-only; metadata redacted at write time                                                        |
| `GET /admin/system-health`                                     | `reports.view`                                          | Pass/warn/fail only; no hosts or credentials                                                      |
| `GET/POST /admin/staff`, `POST/DELETE /admin/staff/{id}/roles` | `staff.manage` + `password.confirm`                     | Not self; never player accounts; never the player role; one super admin always remains; audited   |
| `GET /admin/launch-control`                                    | `launch_gate.view`                                      | Read-only. **No route, command or permission changes the gate**                                   |

## Findings

| #   | Finding                                                                                                                       | Severity | Status                                       |
| --- | ----------------------------------------------------------------------------------------------------------------------------- | -------- | -------------------------------------------- |
| 1   | API returned `HTTP_ERROR` instead of `NOT_FOUND` when a policy denied "as not found" (no data leaked; status was already 404) | Low      | Fixed in Milestone 7                         |
| 2   | No Content Security Policy or HSTS at the application layer                                                                   | Medium   | Fixed: `SetSecurityHeaders`                  |
| 3   | No automated guard against new admin routes missing a permission                                                              | Medium   | Fixed: `RouteInventoryTest`                  |
| 4   | Deploys could start with debug on, plain HTTP or stray payment keys                                                           | Medium   | Fixed: `luxbet:preflight` blocks the release |
| 5   | Static-analysis errors had made CI red since Milestone 5, so failures went unnoticed                                          | Medium   | Fixed; CI must stay green before merging     |
| 6   | Staff created through the UI start unverified and without 2FA; they cannot use `/admin` until both are done                   | Info     | By design                                    |

No critical or high-severity issues are open.

## Manual checks to repeat on staging

The automated tests cover these; repeat them by hand on staging before each
release candidate and record the date here.

- [ ] Sign in as each role and open every admin page: allowed pages load,
      others return 403 (`AdminViewersTest`, `AdminAccessTest`).
- [ ] Try another player's wallet, session and play URLs: 404 or invalid
      signature (`DemoWalletApiTest`, `LaunchTest`).
- [ ] Confirm no admin action works against your own account
      (`PlayerStatusTest`, `StaffManagementTest`).
- [ ] Check page source and API responses for provider URLs, secrets,
      password hashes, 2FA secrets or idempotency keys: none present.
- [ ] Check response headers on staging (CSP, HSTS, X-Frame-Options) with
      the browser dev tools or <https://securityheaders.com>.

| Date | Reviewer | Result |
| ---- | -------- | ------ |
|      |          |        |
