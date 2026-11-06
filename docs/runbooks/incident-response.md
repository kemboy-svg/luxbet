# Incident response

## Severity

| Level | Examples                                                                                  | Response               |
| ----- | ----------------------------------------------------------------------------------------- | ---------------------- |
| SEV1  | Site down; suspected breach; launch gate not DISABLED; any sign of real money being taken | Immediately, all hands |
| SEV2  | Major feature broken (sign-in, play), provider events failing, backups failing            | Same day               |
| SEV3  | Minor bug, single user issue                                                              | Normal queue           |

## First 15 minutes

1. **Contain.** If data or money could be at risk, put the site in
   maintenance mode: `docker compose exec app php artisan down`. For a
   compromised staff account, see [account-actions.md](account-actions.md).
2. **Check the gate:** `docker compose exec app php artisan luxbet:launch-gate`
   must say DISABLED. If not, stop the web container
   (`docker compose stop web`) and treat it as SEV1.
3. **Gather facts** before changing anything:
    - `docker compose ps`, `docker compose logs --since 1h app web queue`
    - Admin → System health, Admin → Provider events, Admin → Audit log
    - Look for the `X-Request-Id` from the user's report in the logs.
4. **Open an incident note** (time, what you saw, what you did). Keep
   adding to it.

## Common causes

| Symptom                 | Check                            | Fix                                                                   |
| ----------------------- | -------------------------------- | --------------------------------------------------------------------- |
| 502 from Caddy          | `docker compose ps app`          | `docker compose up -d app`; check logs for boot errors (often `.env`) |
| HTTPS certificate error | `docker compose logs web`        | DNS must point at the Droplet; ports 80/443 open                      |
| Everyone signed out     | Redis restarted                  | Expected; no action                                                   |
| Queue jobs stuck        | System health → failed jobs      | `docker compose restart queue`; `php artisan queue:failed`            |
| Sessions not expiring   | System health → overdue sessions | `docker compose restart scheduler`                                    |
| Disk full               | `df -h`, `docker system df`      | `docker image prune -a`, check backup retention                       |

## Suspected security incident

1. Maintenance mode. Do not delete logs or containers.
2. Snapshot the Droplet in the DigitalOcean panel (evidence).
3. Rotate secrets: `APP_KEY` (signs everyone out), DB and Redis passwords,
   the deploy SSH key, the GHCR token, Spaces keys. Redeploy.
4. Review the audit log for staff actions and role changes.
5. If personal data may be exposed, record what, whose and when. Your data
   protection obligations (for example the Mauritius Data Protection Act
   2017 or Kenya's Data Protection Act 2019) may require notifying the
   regulator and the affected users within a fixed time. Get advice.

## After the incident

Write a short review within a week: timeline, root cause, what went well,
what to change. Add tests or checks so it cannot recur silently.
