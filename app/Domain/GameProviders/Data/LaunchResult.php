<?php

namespace App\Domain\GameProviders\Data;

final readonly class LaunchResult
{
    public function __construct(
        public string $launchUrl,
        public ?string $providerSessionReference = null,
    ) {}
}
