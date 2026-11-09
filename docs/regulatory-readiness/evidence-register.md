# Technical evidence register

Evidence produced by the development team, for reviewers. All paths are in
this repository.

| #   | Evidence                                                                       | Where                                                                                                               |
| --- | ------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| E1  | Real-money launch gate: fail-closed design, four independent locks             | `docs/regulatory-launch-gate.md`, `app/Domain/Compliance/LaunchGate/`, migration `launch_control_real_money_locked` |
| E2  | Tests proving the gate stays closed and no write route exists                  | `tests/Unit/Compliance/LaunchGateEvaluationTest.php`, `tests/Feature/Compliance/LaunchGateTest.php`                 |
| E3  | No real-money routes; admin routes all protected                               | `tests/Feature/Security/RouteInventoryTest.php`                                                                     |
| E4  | Deploy-time checks: gate disabled, no payment credentials                      | `app/Console/Commands/PreflightCommand.php`, `deploy/scripts/deploy.sh`                                             |
| E5  | Demo ledger: append-only, idempotent, no cash value                            | `docs/demo-mode.md`, `docs/database-schema.md`, `tests/Feature/DemoWallet/`                                         |
| E6  | Audit log: append-only, redacted                                               | `app/Domain/Audit/`, `tests/Feature/Audit/`                                                                         |
| E7  | Roles, permissions, staff MFA, staff management rules                          | `docs/security.md`, `docs/access-control-review.md`, `tests/Feature/Admin/`                                         |
| E8  | Provider integration: signed webhooks, replay protection, launch URL allowlist | `docs/provider-integration.md`, `tests/Feature/GameProviders/`                                                      |
| E9  | Simulator is labelled and never certified                                      | `docs/simulator-games.md`, demo banners on every page (`LaunchGateTest`)                                            |
| E10 | Responsible-play features                                                      | `app/Http/Controllers/Settings/ResponsiblePlayController.php`, `tests/Feature/Settings/ResponsiblePlayTest.php`     |
| E11 | Security headers and CSP                                                       | `app/Http/Middleware/SetSecurityHeaders.php`, `tests/Feature/Security/SecurityHeadersTest.php`                      |
| E12 | CI: tests, static analysis, dependency audits on every change                  | `.github/workflows/tests.yml`, `docs/testing.md`                                                                    |
| E13 | Staging hardening, backups, restore drills                                     | `docs/deployment.md`, `docs/runbooks/`                                                                              |
| E14 | Architecture decisions                                                         | `docs/decisions/`                                                                                                   |

## Evidence still to be produced on staging

| #   | Evidence                                  | Runbook                                                  |
| --- | ----------------------------------------- | -------------------------------------------------------- |
| S1  | Successful restore drill report           | `docs/runbooks/backups-and-restore.md`                   |
| S2  | Manual access-control checklist completed | `docs/access-control-review.md`                          |
| S3  | Response-header scan of the staging site  | `docs/access-control-review.md`                          |
| S4  | Independent penetration test report       | [independent-assessments.md](independent-assessments.md) |
