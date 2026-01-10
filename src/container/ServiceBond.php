<?php

namespace Arbor\container;

use Closure;
use ReflectionFunction;
use LogicException;

/**
 * Class ServiceBond
 *
 * Represents a binding record in the container for managing service dependencies.
 *
 * @package Arbor\container
 */
class ServiceBond
{
    /**
     * The fully qualified name (FQN) of the service.
     *
     * @var string
     */
    protected string $fqn;

    /**
     * The resolver callable responsible for creating the service instance.
     *
     * @var mixed
     */
    protected mixed $resolver;

    /**
     * Indicates whether the binding is shared (singleton).
     *
     * @var bool
     */
    protected bool $isShared;

    /**
     * ServiceBond constructor.
     *
     * @param string   $fqn      The fully qualified class name.
     * @param mixed $resolver The callable that resolves the instance.
     * @param bool     $isShared Whether the binding should be shared.
     */
    public function __construct(string $fqn, mixed $resolver, bool $isShared = false)
    {
        $this->assertStaticResolver($resolver);
        $this->fqn = $fqn;
        $this->resolver = $resolver;
        $this->isShared = $isShared;
    }

    /**
     * Get the fully qualified name (FQN) of the service.
     *
     * @return string
     */
    public function getFqn(): string
    {
        return $this->fqn;
    }

    /**
     * Set the fully qualified name (FQN) of the service.
     *
     * @param string $fqn
     * @return self
     */
    public function setFqn(string $fqn): self
    {
        $this->fqn = $fqn;
        return $this;
    }

    /**
     * Get the resolver callable.
     *
     * @return callable
     */
    public function getResolver(): mixed
    {
        return $this->resolver;
    }

    /**
     * Set the resolver callable.
     *
     * @param callable $resolver
     * @return self
     */
    public function setResolver(callable $resolver): self
    {
        $this->assertStaticResolver($resolver);
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * Check if the binding is shared (singleton).
     *
     * @return bool
     */
    public function isShared(): bool
    {
        return $this->isShared;
    }

    /**
     * Set whether the binding should be shared.
     *
     * @param bool $isShared
     * @return self
     */
    public function setShared(bool $isShared): self
    {
        $this->isShared = $isShared;
        return $this;
    }


    private function assertStaticResolver(Closure $resolver): void
    {
        $ref = new ReflectionFunction($resolver);

        if ($ref->getClosureThis() !== null) {
            throw new LogicException(
                'Container resolver closures must be static'
            );
        }
    }
}
