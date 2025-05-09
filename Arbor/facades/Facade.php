<?php

namespace Arbor\facades;

use Arbor\contracts\container\ContainerInterface;
use Exception;

abstract class Facade
{
    /**
     * The instance of the service that the facade is delegating to.
     * 
     * @var object|null
     */
    protected static array $resolvedInstances = [];

    /**
     * The container used for resolving services.
     * 
     * @var ContainerInterface
     */
    protected static ?ContainerInterface $container = null;

    /**
     * Set the instance of the service for this facade.
     *
     * @param object $instance
     * @return void
     */
    public static function setInstance(object $instance): void
    {
        static::$resolvedInstances[static::class] = $instance;
    }

    /**
     * Set the container to be used for resolving the service.
     *
     * @param ContainerInterface $container
     * @return void
     */
    public static function setContainer(ContainerInterface $container): void
    {
        static::$container = $container;
    }

    /**
     * Return the accessor string used to resolve from the container.
     *
     * @return string
     */
    abstract protected static function getAccessor(): string;

    /**
     * Resolve the instance, using the container if necessary.
     *
     * @return object
     * 
     */
    public static function resolveInstance(): object
    {
        if (!isset(static::$resolvedInstances[static::class])) {

            if (!static::$container instanceof ContainerInterface) {
                throw new Exception("Container is either not set or is not a valid Container type");
            }

            static::$resolvedInstances[static::class] = static::$container->get(static::getAccessor());
        }

        return static::$resolvedInstances[static::class];
    }

    /**
     * Handle static calls to the facade.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws Exception
     * 
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::resolveInstance();

        if (!method_exists($instance, $method)) {
            $class = static::class;
            throw new Exception("Method '{$class}::{$method}()' does not exist.");
        }

        return $instance->{$method}(...$args);
    }

    /**
     * Optional: Reset the cached instance (useful in tests).
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$resolvedInstances[static::class] = null;
    }
}
