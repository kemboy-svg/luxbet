<?php

namespace App\Domain\GameProviders\Support;

/**
 * Prevents open redirects: a return URL is accepted only when it points at
 * this application's own origin; anything else falls back to the catalogue.
 */
class ReturnUrlGuard
{
    public function resolve(?string $url): string
    {
        $fallback = url('/games');

        if ($url === null || $url === '') {
            return $fallback;
        }

        $target = parse_url($url);
        $app = parse_url((string) config('app.url'));

        if ($target === false || $app === false || isset($target['user']) || isset($target['pass'])) {
            return $fallback;
        }

        $sameOrigin = strtolower($target['scheme'] ?? '') === strtolower($app['scheme'] ?? '')
            && strtolower($target['host'] ?? '') === strtolower($app['host'] ?? '')
            && ($target['port'] ?? null) === ($app['port'] ?? null);

        return $sameOrigin ? $url : $fallback;
    }
}
