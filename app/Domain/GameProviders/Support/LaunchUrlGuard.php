<?php

namespace App\Domain\GameProviders\Support;

use App\Domain\GameProviders\Exceptions\DemoSessionRefused;

/**
 * Only launch URLs on allowlisted hosts are ever sent to the browser.
 * HTTPS is required, except for this app's own origin (the local simulator).
 * URLs carrying credentials are refused.
 *
 * "This app's own origin" is APP_URL and the origin of the current request:
 * locally the site is often opened as 127.0.0.1:8000 while APP_URL says
 * localhost:8000, and our own signed URLs are generated for the host the
 * player actually used.
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
        $isAppOrigin = false;
        foreach ($this->appOrigins() as $app) {
            if ($host === $app['host'] && $scheme === $app['scheme']) {
                $isAppOrigin = true;
            }
        }
        $isAllowlisted = $scheme === 'https' && in_array($host, $this->extraHosts(), true);

        if (! $isAppOrigin && ! $isAllowlisted) {
            throw new DemoSessionRefused('The provider returned a launch URL that is not on the allowlist.');
        }
    }

    /**
     * @return list<array{scheme: string, host: string}>
     */
    private function appOrigins(): array
    {
        $origins = [];

        foreach ([(string) config('app.url'), request()->root()] as $root) {
            $parts = parse_url($root);

            if ($root !== '' && $parts !== false && isset($parts['scheme'], $parts['host'])) {
                $origins[] = ['scheme' => strtolower($parts['scheme']), 'host' => strtolower($parts['host'])];
            }
        }

        return $origins;
    }

    /**
     * @return list<string>
     */
    private function extraHosts(): array
    {
        /** @var list<string> $hosts */
        $hosts = config('luxbet.providers.launch_hosts', []);

        return array_map('strtolower', $hosts);
    }
}
