<?php

namespace App\Domain\GameProviders\Support;

use App\Domain\GameProviders\Contracts\GameProviderAdapter;
use App\Domain\GameProviders\Exceptions\ProviderUnavailable;
use App\Domain\Games\Models\GameProvider;
use Illuminate\Contracts\Container\Container;

class ProviderAdapterRegistry
{
    public function __construct(private readonly Container $container) {}

    public function has(GameProvider $provider): bool
    {
        return is_string(config('luxbet.providers.adapters.'.$provider->slug));
    }

    public function for(GameProvider $provider): GameProviderAdapter
    {
        $class = config('luxbet.providers.adapters.'.$provider->slug);

        if (! is_string($class) || ! is_subclass_of($class, GameProviderAdapter::class)) {
            throw ProviderUnavailable::because('No adapter is configured for this provider.');
        }

        /** @var GameProviderAdapter */
        return $this->container->make($class);
    }
}
