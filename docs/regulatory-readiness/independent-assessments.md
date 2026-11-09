# Independent assessments to commission

The development team's own testing (CI, access-control review, security
tests) is not a substitute for independent assessment. Before any
regulated-money project, the owner should commission:

| Assessment                    | Scope                                                                      | Typical provider                                            | Status                                                                           |
| ----------------------------- | -------------------------------------------------------------------------- | ----------------------------------------------------------- | -------------------------------------------------------------------------------- |
| Penetration test              | Web app, API, admin area, staging infrastructure; OWASP ASVS level 2       | CREST- or OSCP-qualified firm independent of the developers | Not started                                                                      |
| Secure code review            | Authentication, authorisation, ledger, webhook ingestion, launch gate      | Independent application-security consultant                 | Not started                                                                      |
| Game and RNG certification    | Every real-money game and the RNG, to each regulator's technical standards | Regulator-accepted test lab                                 | Not applicable to the simulator, which must never be certified or used for money |
| Gambling control system audit | As required by the target regulator (e.g. Kenya's approved control system) | Regulator-approved auditor                                  | Not started                                                                      |
| AML/CFT programme review      | Policies, risk assessment, controls                                        | AML specialist / external auditor                           | Not started                                                                      |
| Data protection review        | Privacy notice, lawful bases, transfers, retention                         | Data protection adviser                                     | Not started                                                                      |
| Infrastructure review         | Hosting location, backups, disaster recovery, monitoring                   | Cloud security consultant                                   | Not started                                                                      |

## Preparing for the penetration test

- Use **staging**, never production, with test accounts for each role.
- Provide [../access-control-review.md](../access-control-review.md),
  [../security.md](../security.md) and [../api-contracts.md](../api-contracts.md).
- Agree rules of engagement in writing (scope, dates, contacts, no denial of
  service).
- Track findings to closure in the table below and retest.

| Finding | Severity | Owner | Fixed in | Retested |
| ------- | -------- | ----- | -------- | -------- |
|         |          |       |          |          |
