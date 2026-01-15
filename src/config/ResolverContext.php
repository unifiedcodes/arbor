<?php

namespace Arbor\Config;


use Arbor\Config\Registry;
use Closure;


final class ResolverContext
{
    public function __construct(
        private readonly Registry $registry,
        private ?Closure $resolver = null
    ) {}

    public function setResolver(callable $resolver): void
    {
        // optional: guard to prevent replacing resolver more than once
        if ($this->resolver !== null) {
            throw new \LogicException("Resolver already set");
        }
        $this->resolver = $resolver;
    }

    public function resolve(mixed $value): mixed
    {
        return ($this->resolver)($value);
    }

    public function ref(string $key): mixed
    {
        return $this->registry->get($key);
    }


    public function callMethod(string $name, mixed ...$args)
    {
        // if (!isset($this->methods[$name])) {
        //     throw new \RuntimeException("Unregistered method '{$name}'");
        // }

        // return ($this->methods[$name])(...$args);
    }


    public function hasMethod(string $name): bool
    {
        return isset($this->methods[$name]);
    }
}
