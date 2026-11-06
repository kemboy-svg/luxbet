# ADR-002: Demo wallet is separate from any future cash ledger

- Status: Accepted
- Date: 2026-11-05

## Context

Players need a balance to try games, but the platform must never hold, move
or imply real money. Reusing one ledger for demo credits and, later, regulated
cash would risk demo credits becoming convertible or being confused with money.

## Decision

- Demo credits live in dedicated `demo_wallets` and `demo_wallet_transactions`
  tables, named explicitly as demo data (Milestone 3).
- Demo credits are abstract credits stored as integer minor units, not
  currency amounts, and every balance and transaction is labelled DEMO.
- The demo ledger has no withdrawal, transfer, purchase or conversion
  operation, and must never be reused as a cash ledger.
- Any future regulated cash ledger is a separate, double-entry module built
  only after legal approval (instructions.md §5.2).

## Consequences

- No code path can turn demo credits into money.
- A future cash ledger starts from a clean design rather than inheriting
  demo-specific shortcuts.
