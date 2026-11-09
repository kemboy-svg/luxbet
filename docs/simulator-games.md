# Simulator games

The local simulator lets the demo be played end to end without a real game
provider. It is **not** a certified random number generator or casino game,
its figures are design targets for an enjoyable demo, and it only ever moves
fictional demo credits. Code: `app/Domain/GameProviders/Simulator/SimulatorRoundEngine.php`.

Every round still goes through the full provider path: signed bet and win
events, webhook ingestion, idempotent ledger entries.

## Slots

5 reels × 3 rows, 5 paylines (middle, top, bottom, V and inverted V). The
stake is split evenly across the lines. 3, 4 or 5 matching symbols from the
leftmost reel pay; the **crown is wild** and also pays on its own. One
round's payout is capped at 500× the stake.

| Profile | Games                                             | Design return | Win frequency | Feel                   |
| ------- | ------------------------------------------------- | ------------- | ------------- | ---------------------- |
| Low     | Candy Crown, Harbour Lights                       | 95.6%         | ~33% of spins | Frequent, smaller wins |
| Medium  | Island Treasure, Night Orchard (and any new slot) | 94.8%         | ~31%          | Balanced               |
| High    | Copper Comet                                      | 95.6%         | ~30%          | Rarer, bigger wins     |

Pays (multiples of the line bet, for 3 / 4 / 5 of a kind):

| Symbol       | Low              | Medium            | High                |
| ------------ | ---------------- | ----------------- | ------------------- |
| Cherry       | 5 / 10 / 25      | 4 / 10 / 25       | 4 / 10 / 30         |
| Bell         | 7 / 15 / 35      | 6 / 15 / 40       | 8 / 20 / 50         |
| Bar          | 8 / 20 / 40      | 10 / 25 / 60      | 12.5 / 30 / 80      |
| Heart        | 12 / 30 / 60     | 16 / 40 / 100     | 20 / 60 / 160       |
| Diamond      | 20 / 50 / 100    | 25 / 60 / 150     | 40 / 100 / 300      |
| Seven        | 40 / 100 / 400   | 60 / 150 / 600    | 100 / 400 / 2,000   |
| Crown (wild) | 50 / 200 / 1,000 | 100 / 400 / 2,000 | 200 / 1,000 / 5,000 |

A payout smaller than the stake is shown as a **small win** ("40 demo
credits back from your 50 stake"), never celebrated as beating the stake.

## Blackjack

Dealt and scored by the rules from an infinite shoe. Blackjack pays 3:2; the
dealer stands on all 17s. The player's hand is played automatically with a
simple basic strategy (no doubling, splitting or insurance), so one click
deals a whole hand. Design return about 97.6%. The screen shows both hands,
their totals and why the hand was won or lost.

## Baccarat

Punto Banco with the standard third-card rules; the stake is a bet on
Player, paying 1:1, and a tie returns the stake. Design return about 98.8%.

## How the figures are checked

`tests/Unit/GameProviders/SimulatorReturnTest.php` plays 60,000–120,000
rounds per game and checks the return and win frequency stay near the design,
that wilds and paylines are evaluated correctly, and that every blackjack and
baccarat result agrees with the cards shown. The exact slot returns were
computed by enumerating every line combination.

To change a game's feel, edit its profile in `SLOT_PROFILES` and the
mapping in `GAME_PROFILES`, recompute the return, and update the `rtp` value
and this page.
