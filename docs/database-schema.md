# Database schema

PostgreSQL 16. Timestamps are stored in UTC. Money-like quantities (demo
credits) are integer minor units, never floats. Constraints are enforced in
the database as well as in application code.

## `users`

UUID primary key. Starter-kit columns (name, email, password, two-factor,
remember token) plus:

| Column   | Type        | Notes                                                                                                                                         |
| -------- | ----------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `status` | varchar(16) | `active`, `suspended`, `closed`, `pending` (check constraint); default `active`. Changed only through `ChangeAccountStatus`, which audits it. |

Accounts are closed, not hard-deleted, so audit history stays intact.
`sessions.user_id` and `passkeys.user_id` are UUIDs.

## `player_profiles`

| Column                               | Type                         | Notes                                                                  |
| ------------------------------------ | ---------------------------- | ---------------------------------------------------------------------- |
| `id`                                 | uuid PK                      |                                                                        |
| `user_id`                            | uuid FK → `users.id`, unique | Cascade on delete                                                      |
| `username`                           | varchar(20)                  | Unique ignoring case (`lower(username)` index); `^[A-Za-z0-9_]{3,20}$` |
| `country_code`                       | char(2), null                | ISO 3166-1 alpha-2, upper case                                         |
| `preferred_locale`                   | varchar(10)                  | Default `en`                                                           |
| `date_of_birth`                      | date, null                   | Not collected in the MVP                                               |
| `avatar_path`                        | varchar, null                |                                                                        |
| `age_confirmed_at`                   | timestamptz, null            | Player confirmed 18+ at registration                                   |
| `terms_accepted_at`, `terms_version` | timestamptz, varchar         | Terms of demo use accepted                                             |

## Roles and permissions

`roles`, `permissions`, `model_has_roles`, `model_has_permissions`,
`role_has_permissions` from spatie/laravel-permission 8, with `model_id` as a
UUID. Contents are defined in code (`App\Domain\Identity\Authorization`) and
synced by a migration or `php artisan luxbet:sync-permissions`.

## `audit_logs`

Append-only. A PostgreSQL trigger (`audit_logs_append_only`) rejects every
UPDATE and DELETE.

| Column                       | Type          | Notes                                              |
| ---------------------------- | ------------- | -------------------------------------------------- |
| `id`                         | uuid PK       |                                                    |
| `actor_user_id`              | uuid, null    | No foreign key, so entries survive user removal    |
| `action`                     | varchar(100)  | e.g. `player.registered`, `account.status_changed` |
| `subject_type`, `subject_id` | varchar, null |                                                    |
| `ip_address`, `user_agent`   | varchar, null |                                                    |
| `reason`                     | text, null    |                                                    |
| `metadata`                   | jsonb         | Secret-like keys are redacted before storage       |
| `created_at`                 | timestamptz   |                                                    |

## Other starter-kit tables

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs`, `passkeys`.

## `launch_control`

Singleton record for the real-money launch gate. Created with
`real_money_enabled = false` by its migration.

| Column                     | Type                       | Notes                                             |
| -------------------------- | -------------------------- | ------------------------------------------------- |
| `id`                       | smallint PK                | Always `1`                                        |
| `real_money_enabled`       | boolean                    | Default `false`                                   |
| `environment_mode`         | varchar(16)                | `demo`, `staging` or `production`; default `demo` |
| `approved_jurisdictions`   | jsonb                      | Array of ISO 3166-1 alpha-2 codes; default `[]`   |
| `approval_reference`       | varchar, null              |                                                   |
| `approval_document_path`   | varchar, null              | Path in private storage                           |
| `approved_by`              | uuid FK → `users.id`, null | Restrict on delete                                |
| `approved_at`              | timestamptz, null          |                                                   |
| `review_due_at`            | timestamptz, null          |                                                   |
| `change_reason`            | text, null                 |                                                   |
| `version`                  | integer                    | Default `1`                                       |
| `created_at`, `updated_at` | timestamptz                |                                                   |

Check constraints:

- `launch_control_singleton`: `id = 1`
- `launch_control_environment_mode_valid`: mode is one of the three values
- `launch_control_jurisdictions_is_array`: `jsonb_typeof(approved_jurisdictions) = 'array'`
- `launch_control_version_positive`: `version >= 1`
- `launch_control_real_money_locked`: `real_money_enabled = false`

## `currencies`

Display and demo configuration only; a listed currency does not mean
payments or gambling in it are authorised.

| Column         | Type       | Notes               |
| -------------- | ---------- | ------------------- |
| `code`         | char(3) PK | ISO 4217            |
| `name`         | varchar    |                     |
| `minor_unit`   | smallint   | 0–4                 |
| `is_enabled`   | boolean    |                     |
| `is_demo_unit` | boolean    | True only for `XXX` |

Seeded: `XXX` (Demo credits, minor unit 0), `MUR`, `USD`, `EUR`, `KES`. `XXX`
is ISO 4217's "no currency" code; demo wallets use it so demo credits are
abstract units, never amounts of real money.

## `demo_wallets`

| Column          | Type                           | Notes                        |
| --------------- | ------------------------------ | ---------------------------- |
| `id`            | uuid PK                        |                              |
| `user_id`       | uuid FK → `users.id`           |                              |
| `currency_code` | char(3) FK → `currencies.code` | `XXX`                        |
| `balance_minor` | bigint                         | Demo credits; check `>= 0`   |
| `status`        | varchar(16)                    | `active`, `locked`, `closed` |

Unique `(user_id, currency_code)`.

## `demo_wallet_transactions`

Append-only demo-credit ledger (trigger rejects UPDATE and DELETE). Never a
cash ledger (ADR-002).

| Column                                        | Type                        | Notes                                                                   |
| --------------------------------------------- | --------------------------- | ----------------------------------------------------------------------- |
| `id`                                          | uuid PK                     |                                                                         |
| `wallet_id`                                   | uuid FK → `demo_wallets.id` | Restrict on delete                                                      |
| `reference`                                   | varchar(32), unique         | `DTX-…`                                                                 |
| `type`                                        | varchar                     | `demo_credit`, `demo_bet`, `demo_win`, `demo_refund`, `demo_adjustment` |
| `direction`                                   | varchar                     | `credit` or `debit`                                                     |
| `amount_minor`                                | bigint                      | Check `> 0`                                                             |
| `balance_before_minor`, `balance_after_minor` | bigint                      | Check `>= 0` and consistent with direction                              |
| `source`                                      | varchar                     | `system`, `provider_sandbox`, `admin`                                   |
| `external_reference`                          | varchar, null               | e.g. provider round ID                                                  |
| `idempotency_key`                             | varchar(191), null, unique  |                                                                         |
| `description`                                 | varchar(255)                |                                                                         |
| `created_by`                                  | uuid, null                  | Staff or player who caused it; no FK                                    |

`demo_tx_direction_matches` enforces: credits add (`demo_credit`,
`demo_win`, `demo_refund`, `demo_adjustment`), debits subtract (`demo_bet`,
`demo_adjustment`), and `after = before ± amount`.

## `game_providers`

| Column                          | Type                | Notes                                            |
| ------------------------------- | ------------------- | ------------------------------------------------ |
| `id`                            | uuid PK             |                                                  |
| `name`                          | varchar(100)        |                                                  |
| `slug`                          | varchar(64), unique | kebab-case                                       |
| `environment`                   | varchar             | `sandbox` or `production`                        |
| `status`                        | varchar             | `inactive`, `sandbox`, `active`, `suspended`     |
| `base_url`                      | varchar, null       | Must start with `https://`                       |
| `credentials_secret_reference`  | varchar, null       | Reference only, never a secret; hidden from JSON |
| `webhook_signing_key_reference` | varchar, null       | Reference only; hidden from JSON                 |
| `supports_demo`                 | boolean             |                                                  |
| `is_simulator`                  | boolean             | True for the local simulator                     |

## `games`

| Column               | Type                          | Notes                                                                 |
| -------------------- | ----------------------------- | --------------------------------------------------------------------- |
| `id`                 | uuid PK                       |                                                                       |
| `provider_id`        | uuid FK → `game_providers.id` | Restrict on delete                                                    |
| `provider_game_code` | varchar(100)                  | Unique per provider; never sent to the browser                        |
| `name`               | varchar(100)                  |                                                                       |
| `slug`               | varchar(100), unique          | Public URL `/games/{slug}`                                            |
| `category`           | varchar                       | `slots`, `live_casino`, `blackjack`, `baccarat`, `poker`, `crash`     |
| `thumbnail_path`     | varchar, null                 |                                                                       |
| `description`        | text, null                    |                                                                       |
| `supports_demo`      | boolean                       |                                                                       |
| `is_enabled`         | boolean                       |                                                                       |
| `sort_order`         | integer                       |                                                                       |
| `metadata`           | jsonb object                  | Tile art (`tile.title`, `tile.background`, `tile.border`), `featured` |

A game is **listed** when it is enabled and its provider is `sandbox` or
`active`. It is **playable** when it is also `supports_demo` and its provider
supports demo play in the `sandbox` environment. A category is available only
when it has at least one playable game.

## `game_sessions`

| Column                                                | Type                  | Notes                                                   |
| ----------------------------------------------------- | --------------------- | ------------------------------------------------------- |
| `id`                                                  | uuid PK               |                                                         |
| `user_id`, `game_id`, `provider_id`, `demo_wallet_id` | uuid FKs              |                                                         |
| `session_reference`                                   | varchar(64), unique   | `gs_…`; used in URLs                                    |
| `provider_session_reference`                          | varchar, null, unique |                                                         |
| `mode`                                                | varchar               | Check: always `demo`                                    |
| `status`                                              | varchar               | `created`, `launched`, `completed`, `expired`, `failed` |
| `failure_reason`                                      | varchar, null         |                                                         |
| `launched_at`, `expires_at`, `ended_at`               | timestamptz           |                                                         |

## `provider_webhook_events`

Receipt of each signature-verified provider event, stored before processing.
Only a SHA-256 hash of the payload is kept, never the body.

| Column                        | Type          | Notes                                         |
| ----------------------------- | ------------- | --------------------------------------------- |
| `id`                          | uuid PK       |                                               |
| `provider_id`                 | uuid FK       |                                               |
| `event_id`                    | varchar(128)  | Unique with `provider_id`                     |
| `event_type`                  | varchar       | `demo.bet`, `demo.win`, `demo.refund`         |
| `payload_hash`                | char(64)      |                                               |
| `game_session_id`             | uuid FK, null |                                               |
| `round_reference`, `amount`   |               |                                               |
| `processing_status`           | varchar       | `received`, `processed`, `rejected`, `failed` |
| `failure_reason`              | varchar, null | e.g. `refund_without_bet`, `session_closed`   |
| `received_at`, `processed_at` | timestamptz   |                                               |

## `responsible_play_settings`

One row per player, created on first save. Missing rows mean the defaults.

| Column                          | Type            | Notes                                                   |
| ------------------------------- | --------------- | ------------------------------------------------------- |
| `id`                            | uuid PK         |                                                         |
| `user_id`                       | uuid FK, unique | Cascades on user delete                                 |
| `demo_session_reminder_minutes` | smallint, null  | Check: 15, 30, 45, 60 or 90; null means off. Default 45 |
| `marketing_opt_in`              | boolean         | Default false; changes are audited                      |
| `created_at`, `updated_at`      | timestamptz     |                                                         |

## `favourite_games`

| Column       | Type        | Notes                      |
| ------------ | ----------- | -------------------------- |
| `user_id`    | uuid FK     | Primary key with `game_id` |
| `game_id`    | uuid FK     |                            |
| `created_at` | timestamptz | Default now                |

Only listed games can be added; hidden games simply drop out of the list.
