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

## Planned

| Control | Milestone |
| --- | --- |
| Roles, permissions, policies; MFA required for staff | 2 |
| Append-only audit log | 2–3 |
| Webhook signature verification, replay window, idempotency | 5 |
| Outbound URL allowlists (SSRF), launch-URL and return-URL allowlists (open redirect) | 5 |
| Security headers and Content Security Policy at the app layer | 7 |
| Penetration test and access-control review | 7 |
