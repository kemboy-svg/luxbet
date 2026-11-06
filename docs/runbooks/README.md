# Runbooks

Operational procedures for LuxBet staging and production (demo mode only).

| Runbook                                          | When                                                 |
| ------------------------------------------------ | ---------------------------------------------------- |
| [staging-setup.md](staging-setup.md)             | Creating the DigitalOcean staging environment        |
| [deploy-and-rollback.md](deploy-and-rollback.md) | Every release, and undoing one                       |
| [backups-and-restore.md](backups-and-restore.md) | Backup schedule, restore drill, real restore         |
| [incident-response.md](incident-response.md)     | Outage, security incident or suspected data exposure |
| [provider-outage.md](provider-outage.md)         | A game provider is failing or sending bad events     |
| [account-actions.md](account-actions.md)         | Suspending players, removing staff access            |

Every server command assumes you are the deploy user in `/opt/luxbet`.
`docker compose` there uses `compose.yml` and `.env` automatically.
