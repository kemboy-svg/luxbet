# Game provider outage or bad events

Today the only provider is the local simulator, so this mostly applies once a
sandbox provider is integrated.

## Symptoms

- Admin → Provider events shows many `rejected` or `failed` events.
- Players report launches failing or rounds not settling.
- System health shows events stuck in `received`.

## Steps

1. **Look at the failure reasons** in Admin → Provider events
   (e.g. `invalid_signature`, `session_closed`, `refund_without_bet`).
    - Many `invalid_signature`: the provider rotated secrets or someone is
      sending forged events. Do not loosen verification. Confirm with the
      provider through a known channel.
2. **Pause the provider** if it is misbehaving: Admin → Providers → set
   status to `suspended` (needs `providers.manage`). Its games stop
   launching; other providers keep working. The change is audited.
3. **Tell players** if needed with a short notice. Demo credits are not
   affected by a pause.
4. **Fix and resume**: once the provider confirms a fix, set the status back
   to `sandbox` and watch the events page.

Events are idempotent by `(provider, event_id)`, so provider retries after
an outage cannot double-apply credits.
