<?php

namespace providers;

use Arbor\contracts\container\ServiceProvider;
use Arbor\container\Container;
use Arbor\http\context\RequestStack;

/**
 * Class RequestProvider
 *
 * Registers core HTTP request context services.
 * Specifically, it binds the RequestStack as a singleton to maintain a shared
 * stack across main and sub-requests.
 *
 * @package Arbor\providers
 */
class RequestProvider extends ServiceProvider
{
    /**
     * Register request-related services.
     *
     * @param Container $container
     * @return void
     */
    public function register(Container $container): void
    {
        // Singleton binding ensures consistent request stack
        $container->singleton(RequestStack::class, fn() => new RequestStack());
    }

    /**
     * Services provided.
     *
     * @return string[]
     */
    public function provides(): array
    {
        return [RequestStack::class];
    }

    /**
     * Service aliases for shorthand resolving.
     *
     * @return array<string, string>
     */
    public function aliases(): array
    {
        return [
            'requestStack' => RequestStack::class,
        ];
    }
}
