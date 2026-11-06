# Demo mode

The initial release is a non-cash demo platform.

## For players

- A persistent notice, **DEMO MODE — NO REAL-MONEY GAMBLING**, is shown on
  every page. It is rendered by the server in `resources/views/app.blade.php`,
  so frontend state cannot hide it.
- Demo credits are abstract credits, not MUR, USD, EUR or KES. They cannot be
  bought, transferred, withdrawn, redeemed or converted to money or prizes.
- There are no deposit, withdrawal or cash-out buttons. Where a user might
  expect one, the UI explains that the platform is a demo.
- Games come from a provider's demo mode or from a clearly labelled local
  simulator, which is never described as a certified casino game.
- Categories without a tested provider integration (live casino, poker,
  crash) are shown as "coming soon", never as playable.

## Demo wallet

- Every player has one demo wallet in `XXX` (ISO 4217 "no currency"), funded
  with 10,000 credits at registration.
- Every balance change goes through `RecordDemoTransaction`: one database
  transaction, wallet row locked `FOR UPDATE`, one append-only ledger row,
  balances computed on the server.
- Repeating a request with the same idempotency key changes nothing and
  returns the original transaction; reusing a key for a different request is
  refused.
- Zero, negative or oversized amounts, overdrafts and inactive wallets are
  refused with nothing changed.
- Players can reset to the allowance once a day when below it.
- Staff with `demo_wallet.adjust` can credit or debit with a reason; each
  adjustment is audited and labelled "no cash value".
- There is no withdrawal, transfer, purchase or conversion operation.

## For developers

- Label every balance and transaction as demo.
- Never add `/deposit`, `/withdraw`, `/cashout` or similar routes. A test
  fails if any route URI matches those words.
- Never collect card or bank details, or route funds.
- Never use production provider endpoints or credentials outside an approved
  production deployment.
- Use only fictional data in factories and seeders.
- `LUXBET_ENVIRONMENT_MODE` labels the environment (`demo`, `staging`,
  `production`); it does not enable anything.
