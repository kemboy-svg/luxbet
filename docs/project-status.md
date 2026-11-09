# Project status and handover

LuxBet is complete as a **demo-only** casino platform. Every milestone in
`instructions.md` has been delivered; the remaining items need the owner,
a server or qualified professionals.

## Milestones

| #   | Milestone                   | Status                                          | Main evidence                                                                                               |
| --- | --------------------------- | ----------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| —   | Designs                     | Done                                            | Design canvas ("Glam — v2")                                                                                 |
| 1   | Foundation                  | Done                                            | Laravel 13 + Vue/Inertia, Docker, CI, ADRs in `docs/decisions/`                                             |
| 2   | Identity and access         | Done                                            | Roles and permissions, staff 2FA, account status (`docs/security.md`)                                       |
| 3   | Demo wallet                 | Done                                            | Append-only, idempotent ledger (`docs/demo-mode.md`)                                                        |
| 4   | Game catalogue              | Done                                            | Lobby, categories, game pages                                                                               |
| 5   | Provider integration        | Done                                            | Adapter contract, signed webhooks, local simulator (`docs/provider-integration.md`)                         |
| 6   | Player and admin interfaces | Done                                            | Dashboard, favourites, history, responsible play; admin viewers, health, staff management                   |
| 7   | Security, QA and staging    | Done in code; staging to be created             | Security headers, preflight, route inventory, access-control review, DigitalOcean deploy, backups, runbooks |
| 8   | Regulatory readiness        | Evidence pack done; professional review pending | `docs/regulatory-readiness/`                                                                                |

Real money stays disabled by four independent locks, checked on every deploy.

## How we know it works

- **CI on every change:** formatting, lint, TypeScript, Pint, Larastan
  (level 7), about 270 PHPUnit tests on PostgreSQL, dependency audits, a
  production Docker image build, and a real-browser test that signs in and
  plays slot, blackjack and baccarat rounds, checking every balance change
  and failing on console or CSP errors.
- **Game maths:** each simulator game's return and win frequency are checked
  over 60,000–120,000 simulated rounds, and table-game results are checked
  against the cards dealt (`docs/simulator-games.md`).

## Owner to-do list

1. **Commit `composer.lock`** from your machine so builds use the versions
   you tested: `git add composer.lock && git commit -m "Add composer.lock"`.
2. **Create staging** on DigitalOcean: `docs/runbooks/staging-setup.md`.
3. **Run the first restore drill** on staging and record it
   (`docs/runbooks/backups-and-restore.md`).
4. **Walk the manual access-control checklist** on staging
   (`docs/access-control-review.md`).
5. **Add uptime monitoring** (DigitalOcean alerts on `/up`) before inviting
   testers.
6. **Commission the Milestone 8 reviews** (legal, AML, penetration test) if
   real-money operation is ever considered: `docs/regulatory-readiness/`.
7. **Review Dependabot PRs.** Merge only green ones; major-version bumps
   (e.g. TypeScript 7, PHPUnit 13, VueUse 15) need a deliberate upgrade.

## Known limitations

- Email verification is switched off (`LUXBET_REQUIRE_EMAIL_VERIFICATION`);
  turn it on once a mail provider is configured.
- The only game provider is the local simulator. A real provider needs its
  official documentation and agreement (`docs/provider-integration.md`).
- Crash, roulette and live-casino categories are shown as "coming soon".
- Blackjack plays the hand automatically (no hit/stand buttons, doubles or
  splits) so one click deals a whole round.
- Interface languages: English, with French as a stored preference only (no
  translations yet).
