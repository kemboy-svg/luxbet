# ADR-003: Real money disabled by default and locked off

- Status: Accepted
- Date: 2026-11-05

## Context

Operating online gambling for real money requires licences, regulator
engagement, provider and payment approvals and compliance programmes that do
not exist for this project. The software must not be able to drift into
real-money operation through a setting, seed, fixture or admin action.

## Decision

A server-enforced, fail-closed launch gate (`App\Domain\Compliance\LaunchGate`)
guards anything that could touch real money. In this release it is locked off
four ways: a literal `false` in `config/luxbet.php` (no environment-variable
bypass), a PostgreSQL check constraint, a model guard, and the absence of the
required multi-person approval workflow. Status is read-only for every role;
no route, command or UI can change it. Details:
[regulatory-launch-gate.md](../regulatory-launch-gate.md).

## Consequences

- Real-money routes, wallets and payment flows are not built in the MVP.
- Lifting the lock needs a separately approved project change, a new
  migration and legal sign-off; it cannot happen through configuration alone.
- Tests prove the gate stays closed for missing, malformed or expired data.
