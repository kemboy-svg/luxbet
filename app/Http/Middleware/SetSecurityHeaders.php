<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers on every response, including a Content Security Policy.
 *
 * Scripts must come from this origin and carry the per-request nonce that
 * Vite adds to its tags. Inline style attributes are allowed because Vue
 * style bindings and the UI library rely on them; inline scripts are not.
 * The CSP is skipped while the Vite dev server is running (local only).
 */
class SetSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);
        Vite::useCspNonce($nonce);

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->remove('X-Powered-By');

        if ($request->isSecure() && (int) config('luxbet.security.hsts_max_age') > 0) {
            $headers->set('Strict-Transport-Security', 'max-age='.(int) config('luxbet.security.hsts_max_age').'; includeSubDomains');
        }

        if (config('luxbet.security.csp.enabled') && ! Vite::isRunningHot()) {
            $name = config('luxbet.security.csp.report_only')
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';
            $headers->set($name, $this->policy($nonce));
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        /** @var list<string> $frameHosts */
        $frameHosts = array_map(fn (string $host): string => 'https://'.$host, (array) config('luxbet.providers.launch_hosts', []));

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'"],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.bunny.net'],
            'font-src' => ["'self'", 'data:', 'https://fonts.bunny.net'],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'connect-src' => ["'self'"],
            'frame-src' => ["'self'", ...$frameHosts],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        if (config('luxbet.security.csp.upgrade_insecure_requests')) {
            $directives['upgrade-insecure-requests'] = [];
        }

        return collect($directives)
            ->map(fn (array $sources, string $directive): string => trim($directive.' '.implode(' ', $sources)))
            ->implode('; ');
    }
}
