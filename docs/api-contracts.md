# API contracts

All JSON API routes live under `/api/v1` (`routes/api.php`), are rate limited
(`throttle:api`, 60 requests per minute per user or IP) and use the standard
envelopes built by `App\Support\Api\ApiResponse`.

## Envelopes

Success:

```json
{
  "data": {},
  "meta": { "request_id": "uuid" }
}
```

Error:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The request could not be processed.",
    "details": {}
  },
  "meta": { "request_id": "uuid" }
}
```

Every response carries an `X-Request-Id` header matching `meta.request_id`.
A valid UUID sent in `X-Request-Id` is reused; anything else is replaced.

Errors never include stack traces, SQL, secrets or provider credentials, even
when `APP_DEBUG=true`.

| Code | HTTP | When |
| --- | --- | --- |
| `VALIDATION_ERROR` | 422 | Invalid input; `details` holds field errors |
| `UNAUTHENTICATED` | 401 | Not signed in |
| `FORBIDDEN` | 403 | Not allowed |
| `REAL_MONEY_DISABLED` | 403 | A real-money route was called while the launch gate is closed |
| `ACCOUNT_INACTIVE` | 403 | The account is suspended, closed or pending |
| `DEMO_WALLET_REFUSED` | 422 | A demo-wallet rule refused the request (nothing changed) |
| `NOT_FOUND` | 404 | Unknown route or resource |
| `METHOD_NOT_ALLOWED` | 405 | Wrong HTTP method |
| `RATE_LIMITED` | 429 | Too many requests |
| `HTTP_ERROR` | other 4xx/5xx | Other HTTP errors |
| `SERVER_ERROR` | 500 | Unexpected failure |

## Endpoints

### `GET /api/v1/health`

Public. Reports dependency health and demo status. Returns `200` when all
checks pass, `503` otherwise.

```json
{
  "data": {
    "status": "ok",
    "checks": { "database": "ok", "redis": "ok" },
    "environment_mode": "demo",
    "real_money_enabled": false
  },
  "meta": { "request_id": "6f1c…" }
}
```

Laravel's own `/up` liveness check is also available.

### Session-authenticated endpoints

These use the browser's existing session cookie (HTTP-only, SameSite=lax)
and require a CSRF token (`X-XSRF-TOKEN`) on state-changing requests. They
also require a verified email and an active account.

### `GET /api/v1/demo-wallets`

The signed-in player's demo wallets (created on first use).

```json
{
  "data": [
    {
      "id": "uuid",
      "balance": 10000,
      "unit": "demo_credits",
      "currency_code": "XXX",
      "status": "active",
      "demo": true,
      "cash_value": null
    }
  ],
  "meta": { "request_id": "uuid" }
}
```

### `GET /api/v1/demo-wallets/{wallet}/transactions`

Newest first. Query: `type` (one of the demo transaction types),
`per_page` (1–100, default 25). Another player's wallet returns `404
NOT_FOUND`. Staff with `demo_wallet.view` and two-factor enabled can read any
wallet.

```json
{
  "data": [
    {
      "reference": "DTX-8F21C4AB12CD",
      "type": "demo_win",
      "type_label": "Demo win",
      "direction": "credit",
      "amount": 1500,
      "signed_amount": 1500,
      "balance_after": 12500,
      "description": "…",
      "created_at": "2026-11-05T17:42:00+00:00",
      "demo": true
    }
  ],
  "meta": {
    "request_id": "uuid",
    "pagination": { "current_page": 1, "per_page": 25, "total": 8, "last_page": 1 }
  }
}
```

### `POST /api/v1/demo-wallets/{wallet}/reset`

Tops the balance back up to the starting allowance. Allowed once per UTC day
and only below the allowance; otherwise `422 DEMO_WALLET_REFUSED`. Rate
limited to 10 per minute.

There are no `/deposit`, `/withdraw`, `/cashout`, transfer, purchase or
conversion endpoints, and there never will be in the demo release.

Endpoints for auth, games, webhooks and admin follow in later milestones as
specified in `instructions.md` §9.
