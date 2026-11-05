# ADR-001: Modular monolith

- Status: Accepted
- Date: 2026-11-05

## Context

LuxBet's MVP is a demo platform with a small team. Microservices would add
deployment, observability and data-consistency costs before there is any
scale to justify them.

## Decision

Build one Laravel 13 application, organised into domain modules under
`app/Domain/<Module>` (Identity, Players, DemoWallet, Games, GameProviders,
Compliance, ResponsiblePlay, Administration, Audit). HTTP concerns stay in
`app/Http`; controllers stay thin and call domain services and actions.

## Consequences

- One deployable, one database, simple transactions across modules.
- Module boundaries must be respected by convention and review; avoid reaching
  into another module's models directly where a service exists.
- A module can be extracted later if scale requires it.
