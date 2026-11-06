<?php

namespace App\Console\Commands;

use App\Domain\Compliance\LaunchGate\LaunchGate;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Deployment safety checks. Run after every staging or production deploy;
 * a non-zero exit code must stop the release.
 *
 * Fails on anything that could expose real money or secrets: an open launch
 * gate, payment-provider credentials in the environment, live provider keys
 * outside production mode, debug mode, plain-HTTP URLs or insecure cookies.
 */
#[Signature('luxbet:preflight {--allow-local : Skip the checks that only apply to deployed environments}')]
#[Description('Check that this deployment is safe to serve (demo-only, no payment credentials, hardened config)')]
class PreflightCommand extends Command
{
    /**
     * Environment variable prefixes of payment services. None may be set in
     * this demo-only release.
     */
    public const PAYMENT_PREFIXES = [
        'STRIPE_', 'PAYPAL_', 'BRAINTREE_', 'ADYEN_', 'CHECKOUT_COM_', 'SQUARE_',
        'MPESA_', 'DARAJA_', 'FLUTTERWAVE_', 'PAYSTACK_', 'RAZORPAY_', 'MOLLIE_',
        'MCB_JUICE_', 'PEACH_', 'CASHIER_', 'SKRILL_', 'NETELLER_', 'COINBASE_COMMERCE_',
    ];

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $results = [];

    public function handle(LaunchGate $gate, Migrator $migrator): int
    {
        $deployed = ! $this->option('allow-local');

        $this->check('Real-money launch gate is disabled', ! $gate->isRealMoneyEnabled() && config('luxbet.real_money.enabled') === false);

        $payment = $this->matchingEnvKeys(fn (string $key): bool => $this->startsWithAny($key, self::PAYMENT_PREFIXES));
        $this->check('No payment-provider credentials in the environment', $payment === [], implode(', ', $payment));

        if (config('luxbet.environment_mode') !== 'production') {
            $live = $this->matchingEnvKeys(fn (string $key): bool => preg_match('/(^|_)(LIVE|PRODUCTION|PROD)(_|$)/', $key) === 1
                && preg_match('/(KEY|SECRET|TOKEN|PASSWORD)/', $key) === 1);
            $this->check('No live/production secrets outside production mode', $live === [], implode(', ', $live));
        }

        $this->check('Application key is set', filled(config('app.key')));

        try {
            DB::select('select 1');
            $this->check('Database is reachable', true);

            $pending = array_diff(
                array_keys($migrator->getMigrationFiles([database_path('migrations'), ...$migrator->paths()])),
                $migrator->getRepository()->getRan(),
            );
            $this->check('Migrations are up to date', $pending === [], count($pending).' pending');
        } catch (Throwable $e) {
            $this->check('Database is reachable', false, class_basename($e));
        }

        if ($deployed) {
            $this->check('Debug mode is off', config('app.debug') === false);
            $this->check('APP_URL uses HTTPS', str_starts_with((string) config('app.url'), 'https://'));
            $this->check('Session cookies are secure', config('session.secure') === true);
            $this->check('Content Security Policy is enforced', config('luxbet.security.csp.enabled') === true && config('luxbet.security.csp.report_only') === false);
            $this->check('Environment mode is staging, demo or production', in_array(config('luxbet.environment_mode'), ['demo', 'staging', 'production'], true));
            $this->warnUnless('Queue is not synchronous', config('queue.default') !== 'sync');
            $this->warnUnless('Cache is persistent', ! in_array(config('cache.default'), ['array', 'null'], true));
            $this->warnUnless('Mail is delivered (not only logged)', ! in_array(config('mail.default'), ['log', 'array'], true));
        }

        $this->table(['Check', 'Result', 'Detail'], $this->results);

        $failed = count(array_filter($this->results, fn (array $row): bool => $row[1] === 'FAIL'));

        if ($failed > 0) {
            $this->components->error("{$failed} check(s) failed. Do not serve this deployment.");

            return self::FAILURE;
        }

        $this->components->info('Preflight passed. Real money is disabled.');

        return self::SUCCESS;
    }

    private function check(string $name, bool $passed, string $detail = ''): void
    {
        $this->results[] = [$name, $passed ? 'ok' : 'FAIL', $passed ? '' : $detail];
    }

    private function warnUnless(string $name, bool $passed): void
    {
        $this->results[] = [$name, $passed ? 'ok' : 'warn', ''];
    }

    /**
     * @param  callable(string): bool  $matches
     * @return list<string> matching variable names (never their values)
     */
    private function matchingEnvKeys(callable $matches): array
    {
        $keys = array_unique([...array_keys(getenv()), ...array_keys($_ENV), ...array_keys($_SERVER)]);

        return array_values(array_filter(
            array_map('strval', $keys),
            fn (string $key): bool => $matches(strtoupper($key)) && filled(env($key)),
        ));
    }

    /**
     * @param  list<string>  $prefixes
     */
    private function startsWithAny(string $key, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
