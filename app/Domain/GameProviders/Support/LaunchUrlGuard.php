<?php

namespace App\Domain\GameProviders\Support;

use App\Domain\GameProviders\Exceptions\DemoSessionRefused;

/**
 * Only launch URLs on allowlisted hosts are ever sent to the browser.
 * HTTPS is required, except for this app's own URL (the local simulator).
 * URLs carrying credentials are refused.
 */
class LaunchUrlGuard
{
    public function assertAllowed(string $url): void
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new DemoSessionRefused('The provider returned an invalid launch URL.');
        }

        $host = strtolower($parts['host']);
        $scheme = strtolower($parts['scheme']);
        $app = $this->appOrigin();

        $isAppOrigin = $app !== null && $host === $app['host'] && $scheme === $app['scheme'];
        $isAllowlisted = $scheme === 'https' && in_array($host, $this->extraHosts(), true);

        if (! $isAppOrigin && ! $isAllowlisted) {
            throw new DemoSessionRefused('The provider returned a launch URL that is not on the allowlist.');
        }
    }

    /**
     * @return array{scheme: string, host: string}|null
     */
    private function appOrigin(): ?array
    {
        $parts = parse_url((string) config('app.url'));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return ['scheme' => strtolower($parts['scheme']), 'host' => strtolower($parts['host'])];
    }

    /**
     * @return list<string>
     */
    private function extraHosts(): array
    {
        /** @var list<string> $hosts */
        $hosts = config('luxbet.providers.launch_hosts', []);

        return array_values(array_map('strtolower', $hosts));
    }
}
