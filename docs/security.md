# Security

Follows OWASP ASVS principles and current Laravel guidance. This page records
what is in place; later milestones extend it.

## In place (Milestone 1)

- Real-money launch gate enforced server-side and fail-closed
  ([regulatory-launch-gate.md](regulatory-launch-gate.md)).
- Request correlation IDs on every request and in logs; inbound IDs accepted
  only if they are valid UUIDs.
- API error envelope that never exposes stack traces, SQL or secrets.
- API rate limiting (60/min per user or IP); Fortify rate limits on login and
  two-factor challenges.
- Sessions in Redis, encrypted (`SESSION_ENCRYPT=true`), HTTP-only,
  SameSite=lax; set `SESSION_SECURE_COOKIE=true` behind HTTPS.
- CSRF protection on all browser mutations (Laravel default).
- Strong password rules in production (`AppServiceProvider`).
- Health checks log exception classes only, never connection details.
- Local Docker ports bound to `127.0.0.1`; PHP container runs as `www-data`.
- Dependency audit in CI.
- No secrets in the repository; `.env` is git-ignored.

## In place (Milestone 2)

- Least-privilege roles and permissions (see table below). `super_admin` has
  no blanket Gate bypass and no permission can change the launch gate.
- Admin area (`/admin`) requires sign-in, verified email, an active account,
  the `admin.access` permission and confirmed two-factor authentication
  (`EnsureStaffHasTwoFactor`). Each page and action adds its own permission
  check; player-status changes also go through `UserPolicy`.
- Admin navigation is only rendered for staff; the server enforces access
  regardless.
- Suspended, closed and pending accounts cannot sign in (status revealed only
  after a correct password), are signed out on their next request, and have
  remember-me tokens revoked.
- Registration rate limited (5 per minute per IP); usernames validated and
  unique ignoring case; 18+ confirmation and terms acceptance recorded.
- Append-only audit log for registrations and status changes, enforced by a
  PostgreSQL trigger, with secret-like metadata redacted.
- Accounts are closed rather than hard-deleted.

### Roles

| Role                 | Permissions                                                                                                                 |
| -------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `player`             | none                                                                                                                        |
| `support_agent`      | admin.access, players.view                                                                                                  |
| `compliance_officer` | admin.access, players.view, players.suspend, compliance.view, compliance.review, audit.view, reports.view, launch_gate.view |
| `game_manager`       | admin.access, games.view, games.manage, providers.view, providers.manage                                                    |
| `finance_viewer`     | admin.access, demo_wallet.view, reports.view                                                                                |
| `super_admin`        | all of the above except compliance.review, plus demo_wallet.adjust and staff.manage                                         |

Roles are defined in `App\Domain\Identity\Authorization\Role`. There is no
self-service way to become staff: staff accounts are created and their roles
changed on the Staff screen (see Milestone 6 below).

## In place (Milestone 3)

- Demo balances change only inside a database transaction with the wallet
  row locked, so concurrent requests cannot corrupt them.
- Idempotency keys stop repeated requests (double clicks, provider retries)
  from applying twice; database unique constraints back this up.
- Ledger and wallet rules enforced by PostgreSQL check constraints and an
  append-only trigger, not only by application code.
- Players can only see their own wallet; other wallets return 404 (no IDOR,
  no existence leak). Staff reading wallets need `demo_wallet.view` and 2FA.
- Session-based JSON API with CSRF protection on state changes.
- Staff adjustments need `demo_wallet.adjust`, a reason and an idempotency
  key; staff cannot adjust their own or a colleague's credits.

## In place (Milestone 5)

- Provider webhooks: signature verified with a constant-time compare before
  anything is stored; 5-minute replay window; 64 KB body limit; strict
  schema; only demo event types; receipts unique per event ID.
- Player, wallet and balance values in provider payloads are never trusted;
  the wallet comes from our own session record.
- Launch URLs restricted to allowlisted HTTPS hosts or this app's origin
  (no arbitrary iframe or redirect targets); return URLs same-origin only.
- The simulator game screen is reachable only through a signed, expiring URL
  and only by the session's owner.
- Production provider endpoints are never playable in demo mode, and
  production provider credentials are never loaded outside production mode.
- Provider secrets and URLs are hidden from every browser response.

## In place (Milestone 6)

- Staff management (`/admin/staff`) needs `staff.manage` and a password
  re-confirmation. Rules live in `ManageStaff` so they hold for any caller:
    - staff accounts are separate from player accounts; a player account can
      never be given a staff role, and the player role is never granted there;
    - nobody can change their own roles;
    - at least one `super_admin` always remains (checked under a row lock);
    - new staff accounts get a random password and a reset link, never a
      password chosen by someone else;
    - every change is audited (`staff.created`, `staff.role_granted`,
      `staff.role_revoked`) with a required reason.
- Read-only viewers, each behind its own permission: player detail
  (`players.view`; ledger needs `demo_wallet.view`, audit trail needs
  `audit.view`), demo ledger (`demo_wallet.view`), provider events
  (`providers.view`), audit log (`audit.view`) and system health
  (`reports.view`). The admin overview only includes blocks the viewer may
  see.
- System health reports pass/warn/fail only; no hosts, connection strings or
  credentials.
- Players only ever see their own sessions, favourites and settings.
- Marketing emails are opt-in (off by default) and consent changes are
  audited.

## In place (Milestone 7)

- Security headers on every response (`SetSecurityHeaders`):
  Content-Security-Policy with a per-request script nonce (no inline or
  third-party scripts, `object-src 'none'`, `frame-ancestors 'self'`),
  HSTS over HTTPS, `X-Frame-Options`, `X-Content-Type-Options`,
  `Referrer-Policy`, `Permissions-Policy` (payment, camera, microphone,
  geolocation off) and `Cross-Origin-Opener-Policy`.
- `luxbet:preflight` blocks unsafe releases (see
  [deployment.md](deployment.md)). It prints variable names only, never
  values.
- Route-table tests (`RouteInventoryTest`) and an
  [access-control review](access-control-review.md).
- CI is green and required: Pint, Larastan (level 7), PHPUnit on
  PostgreSQL, lint, type checks, `composer audit` and `npm audit`.
- Staging hardening: HTTPS-only, private database and Redis, non-root
  containers, firewall, key-only SSH, encrypted backups with restore drills.

## Planned

| Control                                                           | When                                     |
| ----------------------------------------------------------------- | ---------------------------------------- |
| Outbound HTTP client with SSRF checks for a real provider adapter | When a provider is chosen                |
| Uptime monitoring and error tracking                              | Before production                        |
| Independent penetration test                                      | Milestone 8 (commissioned, not self-run) |
