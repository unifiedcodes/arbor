<?php

namespace Arbor\facade;

use Arbor\container\ContainerInterface;
use Exception;

abstract class Facade
{
    protected static ?ContainerInterface $container = null;

    /**
     * Set the container used by the facade.
     */
    public static function setContainer(ContainerInterface $container): void
    {
        static::$container = $container;
    }

    /**
     * Return the container key/class represented by this facade.
     */
    abstract protected static function getAccessor(): string;

    /**
     * Resolve the underlying service from the container.
     */
    protected static function resolveInstance(): object
    {
        if (!static::$container instanceof ContainerInterface) {
            throw new Exception(
                'Container is either not set or is not a valid Container type'
            );
        }

        $instance = static::$container->get(
            static::getAccessor()
        );

        if (!is_object($instance)) {
            throw new Exception(
                "Facade accessor '" . static::getAccessor() . "' did not resolve to an object"
            );
        }

        return $instance;
    }

    /**
     * Forward static calls to the resolved service instance.
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::resolveInstance();

        if (!method_exists($instance, $method)) {
            throw new Exception(
                "Method '" . static::class . "::{$method}()' does not exist."
            );
        }

        return $instance->{$method}(...$args);
    }
}
