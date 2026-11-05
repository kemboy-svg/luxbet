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

Endpoints for auth, games, demo wallets, webhooks and admin follow in later
milestones as specified in `instructions.md` §9. There will be no `/deposit`,
`/withdraw` or `/cashout` endpoints.
