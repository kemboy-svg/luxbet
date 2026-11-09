# Game providers, payments and banking

## Game providers

Today the only provider is the **local simulator**, which is a development
tool and must never be used for real-money play
([../simulator-games.md](../simulator-games.md)). Any real provider needs,
before integration beyond a sandbox:

| Evidence                     | Detail                                                                                                                               |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Commercial agreement         | Signed contract naming LuxBet's operating entity                                                                                     |
| Territory permissions        | Written confirmation of each country where the provider's games may be offered by LuxBet; the provider's own licences there          |
| Game certification           | Certificates for each game and RTP configuration from an accredited test lab, accepted by each target regulator                      |
| Technical documentation      | Official API documentation, sandbox credentials, signature scheme, IP ranges. Not invented or reverse-engineered (`instructions.md`) |
| Incident and dispute process | How round disputes, outages and rollbacks are handled                                                                                |

Register (complete per provider):

| Provider | Agreement ref. | Territories confirmed | Certificates | Sandbox done | Reviewer |
| -------- | -------------- | --------------------- | ------------ | ------------ | -------- |
|          |                |                       |              |              |          |

## Payments and banking

The platform has **no payment code**: no deposit, withdrawal, card, mobile
money or wallet top-up routes, and `luxbet:preflight` blocks any deploy whose
environment contains payment-provider credentials. Before a regulated-money
project could start, the owner must have:

| Evidence                                         | Detail                                                                                                                                                      |
| ------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Bank account(s)                                  | Operating and segregated player-fund accounts with banks that accept gambling merchants, with written approval                                              |
| Payment providers                                | Merchant agreements explicitly covering licensed gambling (merchant category code 7995) in each market; mobile-money (e.g. M-Pesa) approvals where relevant |
| Player-fund protection                           | How player balances are safeguarded as each regulator requires                                                                                              |
| Chargeback, refund and reconciliation procedures | Daily reconciliation between the ledger, payment providers and bank                                                                                         |
| Tax registration                                 | Gambling and betting taxes and levies in each market                                                                                                        |

Register:

| Institution | Type | Markets | Approval ref. | Reviewer |
| ----------- | ---- | ------- | ------------- | -------- |
|             |      |         |               |          |
