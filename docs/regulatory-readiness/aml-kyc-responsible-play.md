# AML/KYC and responsible-play programme: gap analysis

What the demo platform already provides, and what a licensed operation would
additionally need. The "needed" column is a starting checklist for the
compliance officer and counsel, not a complete list of legal obligations.

## Identity and KYC

| Area           | In the platform today                                                   | Needed before real money                                                                                             |
| -------------- | ----------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| Registration   | Email, password, 18+ confirmation, terms acceptance recorded            | Verified identity (documents or e-ID), proof of address, liveness checks via an approved KYC provider                |
| Age            | Self-declared 18+ (checkbox) at registration                            | Verified age before any deposit or play                                                                              |
| Account status | Active, suspended, closed; enforced on every request                    | Exclusion registers (national self-exclusion where it exists), PEP and sanctions screening at onboarding and ongoing |
| Staff          | Separate staff accounts, role-based permissions, mandatory 2FA, audited | Fit-and-proper/key-person licensing of named staff where the regulator requires it                                   |

## Anti-money laundering

| Area       | In the platform today                                                                              | Needed before real money                                                                                        |
| ---------- | -------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| Ledger     | Append-only, idempotent demo ledger with balance-after on every row; database triggers block edits | Same design for real funds, plus reconciliation with banks and payment providers                                |
| Audit      | Append-only audit log of sensitive actions with actor, reason and request ID                       | Retention periods required by law; regulator access                                                             |
| Monitoring | None (no money moves)                                                                              | Risk-based transaction monitoring, thresholds, source-of-funds and source-of-wealth checks                      |
| Reporting  | None                                                                                               | Named MLRO, suspicious transaction reporting to the financial intelligence unit, record keeping, staff training |
| Policies   | None                                                                                               | Written AML/CFT policy, business risk assessment, independent audit                                             |

## Responsible play

| Area                 | In the platform today                                             | Needed before real money                                                       |
| -------------------- | ----------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| Session reminders    | Player-chosen interval (15–90 min, default 45), shown during play | Reality checks as each regulator prescribes                                    |
| Session limits       | 30-minute demo sessions                                           | Deposit, loss, wager and session limits set by the player; cooling-off periods |
| Self-exclusion       | Account closure in settings                                       | Self-exclusion for set periods across all products; link to national schemes   |
| Information          | Demo notices on every page; game pages show simulated return      | Odds/RTP disclosure per game, help-line information, risk messaging            |
| Marketing            | Opt-in only, off by default, consent changes audited              | Advertising codes of each market; no targeting of excluded or at-risk players  |
| Under-age protection | 18+ declaration                                                   | Verified age; blocking and reporting                                           |
| Monitoring           | Play history visible to the player and staff                      | Markers-of-harm detection and interventions                                    |

## Data protection

Personal data held: name, email, username, optional country and language,
session and play history, audit records. Controls: encrypted
sessions, hashed passwords, 2FA secrets encrypted, secrets never logged,
backups encrypted. Needed: registration with the data protection authority
in each market, privacy notice review, retention schedule, data-processing
agreements with DigitalOcean and email providers, cross-border transfer
assessment.
