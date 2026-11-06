// End-to-end smoke test of demo play in a real browser against a running app.
//
//   BASE_URL=http://127.0.0.1:8000 node tests/e2e/demo-play.mjs
//
// Signs in as the seeded demo player, plays rounds on a slot, blackjack and
// baccarat simulator, checks every balance change against stake and payout,
// and fails on any console error (including CSP violations).
// Screenshots and report.json go to tests/e2e/output/.
import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8000';
const OUT = new URL('./output/', import.meta.url).pathname;
const ROUNDS = Number(process.env.ROUNDS ?? 8);
mkdirSync(OUT, { recursive: true });

const report = { games: [], consoleErrors: [], failures: [] };
const fail = (message) => {
    report.failures.push(message);
    console.error('FAIL:', message);
};

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
page.on(
    'console',
    (m) => m.type() === 'error' && report.consoleErrors.push(m.text()),
);
page.on('pageerror', (e) => report.consoleErrors.push(String(e)));

async function shot(name) {
    await page.screenshot({ path: `${OUT}${name}.png`, fullPage: false });
}

// Sign in.
await page.goto(`${BASE}/login`);
await page.fill('input[name="email"]', 'test@example.com');
await page.fill('input[name="password"]', 'password');
await Promise.all([
    page.waitForURL(/\/dashboard/),
    page.click('button[type="submit"]'),
]);

async function playGame(slug, buttonLabel) {
    const game = { slug, rounds: [], ok: true };
    report.games.push(game);

    await page.goto(`${BASE}/games/${slug}`);
    await shot(`${slug}-details`);
    await Promise.all([
        page.waitForURL(/\/play\//, { timeout: 15000 }),
        page.getByRole('button', { name: /Play demo/ }).click(),
    ]);
    await page.waitForTimeout(500);
    await shot(`${slug}-start`);

    let previous = null;
    for (let i = 0; i < ROUNDS; i++) {
        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/simulator-rounds'), {
                timeout: 15000,
            }),
            page
                .getByRole('button', { name: buttonLabel, exact: true })
                .click(),
        ]);
        const body = await response.json();

        if (!response.ok()) {
            fail(
                `${slug} round ${i + 1}: HTTP ${response.status()} ${body?.error?.code ?? ''} ${body?.error?.message ?? ''}`,
            );
            game.ok = false;
            break;
        }

        const { stake, payout, result, balance } = body.data;
        game.rounds.push({ stake, payout, result, balance });

        if (previous !== null && balance !== previous - stake + payout) {
            fail(
                `${slug} round ${i + 1}: balance ${balance} != ${previous} - ${stake} + ${payout}`,
            );
            game.ok = false;
        }
        if (body.data.demo !== true) {
            fail(`${slug} round ${i + 1}: response not marked demo`);
        }
        previous = balance;

        // The cabinet must show the same balance the server returned.
        await page.waitForTimeout(300);
        const shown = await page.locator('body').innerText();
        if (!shown.includes(balance.toLocaleString('en'))) {
            fail(
                `${slug} round ${i + 1}: balance ${balance} not shown on screen`,
            );
            game.ok = false;
        }
        if (i === 0 || i === ROUNDS - 1) {
            await shot(`${slug}-round-${i + 1}`);
        }
    }

    game.wins = game.rounds.filter((r) => r.payout > 0).length;
    console.log(
        `${slug}: ${game.rounds.length} rounds, ${game.wins} wins, final balance ${previous}`,
    );
}

await playGame('candy-crown', 'SPIN');
await playGame('royal-21', 'DEAL');
await playGame('velvet-baccarat', 'DEAL');

// The player's pages reflect what was played.
await page.goto(`${BASE}/dashboard`);
await page.waitForTimeout(500);
await shot('dashboard');
const dashboard = await page.locator('body').innerText();
for (const name of ['Candy Crown', 'Royal 21', 'Velvet Baccarat']) {
    if (!dashboard.includes(name)) fail(`dashboard does not list ${name}`);
}
await page.goto(`${BASE}/play-history`);
await shot('play-history');

// Mobile view of a slot cabinet.
await page.setViewportSize({ width: 390, height: 844 });
await page.goto(`${BASE}/games/candy-crown`);
await Promise.all([
    page.waitForURL(/\/play\//),
    page.getByRole('button', { name: /Play demo/ }).click(),
]);
await page.getByRole('button', { name: 'SPIN', exact: true }).click();
await page.waitForTimeout(800);
await shot('candy-crown-mobile');

await browser.close();

for (const e of report.consoleErrors) fail(`console: ${e}`);
writeFileSync(`${OUT}report.json`, JSON.stringify(report, null, 2));

if (report.failures.length > 0) {
    console.error(`${report.failures.length} failure(s)`);
    process.exit(1);
}
console.log('Demo play smoke test passed.');
