<?php

namespace App\Domain\GameProviders\Simulator;

/**
 * HMAC-SHA256 signing used by the local simulator's events:
 * signature = hex(HMAC_SHA256(secret, "{timestamp}.{raw body}")).
 * This is LuxBet's own scheme for its simulator only; a real provider's
 * documented algorithm must be used for that provider.
 */
final class SimulatorSigner
{
    public const TIMESTAMP_HEADER = 'x-luxbet-sim-timestamp';

    public const SIGNATURE_HEADER = 'x-luxbet-sim-signature';

    public static function sign(string $body, int $timestamp, string $secret): string
    {
        return 'v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    /**
     * @return array<string, string>
     */
    public static function headers(string $body, int $timestamp, string $secret): array
    {
        return [
            self::TIMESTAMP_HEADER => (string) $timestamp,
            self::SIGNATURE_HEADER => self::sign($body, $timestamp, $secret),
        ];
    }
}
