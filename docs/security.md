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

| Role | Permissions |
| --- | --- |
| `player` | none |
| `support_agent` | admin.access, players.view |
| `compliance_officer` | admin.access, players.view, players.suspend, compliance.view, compliance.review, audit.view, reports.view, launch_gate.view |
| `game_manager` | admin.access, games.view, games.manage, providers.view, providers.manage |
| `finance_viewer` | admin.access, demo_wallet.view, reports.view |
| `super_admin` | all of the above except compliance.review, plus demo_wallet.adjust and staff.manage |

Roles are defined in `App\Domain\Identity\Authorization\Role`. Staff
accounts are assigned roles with tinker or a future staff-management screen
(`staff.manage`); there is no self-service way to become staff.

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

## Planned

| Control | Milestone |
| --- | --- |
| Staff management screen and audit log viewer | 6 |
| Webhook signature verification, replay window, idempotency | 5 |
| Outbound URL allowlists (SSRF), launch-URL and return-URL allowlists (open redirect) | 5 |
| Security headers and Content Security Policy at the app layer | 7 |
| Penetration test and access-control review | 7 |
