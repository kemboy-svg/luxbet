# Game-provider integration

Status: **provider layer in place with the local simulator (Milestone 5).**
No real provider has been selected and no official provider documentation has
been supplied. A real provider's adapter must be built from its official
sandbox documentation; endpoints, signatures, parameters and callback fields
are never invented.

## Contract

`App\Domain\GameProviders\Contracts\GameProviderAdapter`:

| Method | Purpose |
| --- | --- |
| `createDemoSession(GameSession, returnUrl)` | Ask the provider for a demo launch URL; throw `ProviderUnavailable` on downtime |
| `verifyWebhook(GameProvider, body, headers)` | Check signature, timestamp and schema; return a `VerifiedProviderEvent` |
| `health(GameProvider)` | Reachability for the admin Providers page |

Adapters are registered per provider slug in `config/luxbet.php`
(`providers.adapters`). Only `local-simulator` exists.

## Launch flow

`StartDemoSession` (web `POST /games/{slug}/play`, API `POST
/api/v1/games/{slug}/demo-session`):

1. Account active (and email verified when required).
2. Game enabled, supports demo, provider serving demo in **sandbox**.
3. Short-lived `game_sessions` row (30 min, `mode = demo`).
4. Adapter returns a launch URL.
5. `LaunchUrlGuard`: HTTPS on an allowlisted host (`LUXBET_PROVIDER_LAUNCH_HOSTS`)
   or this app's own origin; URLs with credentials refused.
6. `ReturnUrlGuard`: return URLs must be same-origin, otherwise `/games`.

Downtime or a bad URL marks the session `failed`; nothing is charged.

## Webhooks

`POST /api/v1/webhooks/providers/{slug}` → `IngestProviderWebhook`:

1. Body over 64 KB → `413`.
2. Adapter verifies signature (constant-time compare) and a 5-minute replay
   window → `401` on failure. Nothing is stored for unverified requests.
3. Schema check; only `demo.bet`, `demo.win`, `demo.refund` accepted → `422`
   otherwise (real-money event types are refused).
4. Receipt stored in `provider_webhook_events` **before** processing,
   unique per provider + event ID. A repeat returns `duplicate` and changes
   nothing.
5. `ProcessDemoEvent` applies it to the demo ledger with idempotency key
   `provider-event:{provider}:{event}`. The wallet always comes from our
   session record; player IDs, wallet IDs or balances in the payload are
   ignored.

Rules: bets need an open session; wins may arrive late or before their bet;
refunds must match an earlier bet in the same round and never exceed it;
overdrafts are rejected. Rejected events change no balance.

Processing is synchronous today (the simulator needs the result immediately).
A real provider integration can move step 5 to a queued job; the receipt
table already supports that.

## Local simulator

- Launches a signed page in this app (`/play/{session}`), the game screen in
  the Glam style (5×3 slot, or a card table for blackjack and baccarat).
- Each round (`POST /api/v1/game-sessions/{session}/simulator-rounds`) creates
  signed `demo.bet` / `demo.win` events and submits them through the same
  ingestion path as real webhooks.
- Signature: `v1=` + hex HMAC-SHA256 of `"{timestamp}.{body}"`, key derived
  from `APP_KEY`, headers `X-Luxbet-Sim-Timestamp` and `X-Luxbet-Sim-Signature`.
  This is LuxBet's own scheme for the simulator only.
- Outcomes come from `SimulatorRoundEngine` (PHP `random_int`). It is a
  development tool, **not a certified RNG or casino game**, and is always
  labelled "Local simulator — not a certified casino game".
- `LUXBET_SIMULATOR_AVAILABLE=false` simulates provider downtime.

## Secrets

`ProviderSecrets` resolves secret *references* from configuration
(`config/services.php` → `game_providers`), never from the database, and
refuses to load production provider credentials unless
`LUXBET_ENVIRONMENT_MODE=production`.

## Sessions

`php artisan luxbet:expire-game-sessions` (scheduled every five minutes) marks
sessions past their expiry as expired. Players can end a session from the
game screen.

## Provider documentation

None supplied yet. Link official documentation here when a provider is
chosen, together with any integration assumptions.
