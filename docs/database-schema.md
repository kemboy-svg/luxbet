# Database schema

PostgreSQL 16. Timestamps are stored in UTC. Money-like quantities (demo
credits) are integer minor units, never floats. Constraints are enforced in
the database as well as in application code.

## Starter-kit tables

`users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`,
`job_batches`, `failed_jobs`, `passkeys`, plus two-factor columns on `users`.
Milestone 2 switches `users.id` to UUID and adds `status`, before any
deployment.

## `launch_control`

Singleton record for the real-money launch gate. Created with
`real_money_enabled = false` by its migration.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | smallint PK | Always `1` |
| `real_money_enabled` | boolean | Default `false` |
| `environment_mode` | varchar(16) | `demo`, `staging` or `production`; default `demo` |
| `approved_jurisdictions` | jsonb | Array of ISO 3166-1 alpha-2 codes; default `[]` |
| `approval_reference` | varchar, null | |
| `approval_document_path` | varchar, null | Path in private storage |
| `approved_by` | FK → `users.id`, null | Restrict on delete |
| `approved_at` | timestamptz, null | |
| `review_due_at` | timestamptz, null | |
| `change_reason` | text, null | |
| `version` | integer | Default `1` |
| `created_at`, `updated_at` | timestamptz | |

Check constraints:

- `launch_control_singleton`: `id = 1`
- `launch_control_environment_mode_valid`: mode is one of the three values
- `launch_control_jurisdictions_is_array`: `jsonb_typeof(approved_jurisdictions) = 'array'`
- `launch_control_version_positive`: `version >= 1`
- `launch_control_real_money_locked`: `real_money_enabled = false`

## Planned tables

`player_profiles`, `currencies`, `demo_wallets`, `demo_wallet_transactions`,
`game_providers`, `games`, `game_sessions`, `provider_webhook_events`,
`responsible_play_settings`, `audit_logs`. See `instructions.md` §5.1.
