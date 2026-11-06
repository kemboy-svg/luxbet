# Game-provider integration

Status: **not started** (Milestones 4–5). No provider has been selected and
no official provider documentation has been supplied yet.

## Approach

- A `GameProviderInterface` contract with one adapter class per provider.
- A local **simulator** adapter for automated tests and UI work, always
  labelled "Local simulator — not a certified casino game".
- A real provider's sandbox adapter only once its official documentation and
  sandbox credentials are available. Endpoints, signatures, parameters and
  callback fields come from that documentation, never invented.

## Rules (from instructions.md §7)

- Sandbox base URLs and credentials only; production endpoints are rejected
  in demo mode.
- TLS verification on, strict timeouts, retries only for idempotent calls.
- Verify webhook signatures with the provider's documented algorithm; enforce
  the replay window; persist each event ID before processing; reject
  duplicates.
- Validate launch URLs against an allowlist; never accept iframe or redirect
  URLs from the client.
- Log correlation IDs, never secrets or personal data. Provider secrets are
  never sent to the browser.

## Provider documentation

None supplied yet. Link official documentation here when a provider is
chosen, together with any integration assumptions.
