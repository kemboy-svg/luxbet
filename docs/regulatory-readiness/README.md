# Regulatory readiness review (Milestone 8)

**Status: evidence pack prepared by the development team, awaiting review by
authorised professionals. No real-money functionality is enabled, and none
may be built under this project.**

This folder is the evidence pack and checklist for `instructions.md`
Milestone 8. It records what the platform does today, what each approval
would require, and who must sign off. It is **not** legal advice. Every
regulatory statement here must be confirmed by qualified lawyers in each
target jurisdiction before anyone relies on it.

| Document                                                       | Purpose                                                                       | Owner                     |
| -------------------------------------------------------------- | ----------------------------------------------------------------------------- | ------------------------- |
| [jurisdictions.md](jurisdictions.md)                           | Per-market legal position and licence route                                   | External counsel          |
| [providers-payments-banking.md](providers-payments-banking.md) | Game-provider territory rights, payment and banking approvals                 | Business owner + counsel  |
| [aml-kyc-responsible-play.md](aml-kyc-responsible-play.md)     | Gap analysis of AML/KYC and responsible-play obligations against the platform | Compliance officer (MLRO) |
| [independent-assessments.md](independent-assessments.md)       | Security, RNG/game and compliance assessments to commission                   | Business owner            |
| [evidence-register.md](evidence-register.md)                   | Index of technical evidence already in the repository                         | Development team          |
| [change-control.md](change-control.md)                         | The separate, formally approved project any regulated-money work requires     | Board / owner             |

## Acceptance criteria (from the specification)

| Criterion                                                                                              | Status                                                                                                                                                                     |
| ------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Evidence is recorded and reviewed by authorised professionals                                          | Evidence recorded; **review pending**: see sign-off table below                                                                                                            |
| No real-money functionality is enabled by the development team                                         | **Met**: four independent locks, see [../regulatory-launch-gate.md](../regulatory-launch-gate.md); verified on every deploy by `luxbet:preflight` and `luxbet:launch-gate` |
| A separate, formally approved project change is required for any future regulated-money implementation | **Met**: [change-control.md](change-control.md)                                                                                                                            |

## Sign-off

Each reviewer records their conclusion and the document reference of their
written opinion. The development team cannot complete this table.

| Area                                | Reviewer (name, firm, qualification) | Conclusion | Reference | Date |
| ----------------------------------- | ------------------------------------ | ---------- | --------- | ---- |
| Legal: Mauritius                    |                                      |            |           |      |
| Legal: Kenya                        |                                      |            |           |      |
| Legal: other target markets         |                                      |            |           |      |
| Game-provider territory permissions |                                      |            |           |      |
| Payments and banking                |                                      |            |           |      |
| AML/KYC programme                   |                                      |            |           |      |
| Responsible-play programme          |                                      |            |           |      |
| Independent security assessment     |                                      |            |           |      |
| Game/RNG certification              |                                      |            |           |      |
| Data protection                     |                                      |            |           |      |
