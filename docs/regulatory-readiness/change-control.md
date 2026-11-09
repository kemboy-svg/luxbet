# Change control for regulated money

Any work that would let real money, prizes with cash value or
cash-equivalent rewards flow through LuxBet is **outside this project**. It
requires a separate project with its own approval, budget and team sign-off.
The development team must refuse such work under the current project, even
if asked by an administrator.

## Preconditions for starting that project

1. Written legal opinions for each target market (see
   [jurisdictions.md](jurisdictions.md)) and a granted licence in each.
2. Provider, payment and banking approvals recorded
   ([providers-payments-banking.md](providers-payments-banking.md)).
3. An AML/KYC and responsible-play programme approved by the compliance
   officer ([aml-kyc-responsible-play.md](aml-kyc-responsible-play.md)).
4. Independent assessments completed with no open high or critical findings
   ([independent-assessments.md](independent-assessments.md)).
5. A formal decision by the owner/board, recorded below.

## What that project would have to change (non-exhaustive)

- Build the multi-person approval workflow the launch gate requires.
- Remove the database check constraint and model lock **in a reviewed,
  separately approved migration**, and change the literal
  `real_money.enabled` configuration.
- Build regulated modules separately from the demo ledger: KYC, payments,
  real-money ledger, limits, exclusions, regulator reporting.
- Replace the local simulator with certified games only.
- Re-run every assessment above against the changed system.

## Decision log

| Date       | Decision                                                       | Approved by     | Reference     |
| ---------- | -------------------------------------------------------------- | --------------- | ------------- |
| 2026-11-09 | LuxBet remains demo-only. No regulated-money work is approved. | (project owner) | This document |
