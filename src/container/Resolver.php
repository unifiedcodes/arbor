<?php

namespace Arbor\container;


use Throwable;
use Closure;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionFunctionAbstract;

use Arbor\container\Registry;
use Arbor\container\ServiceContainer;
use Arbor\contracts\Container\ContainerInterface;

/**
 * Class Resolver
 *
 * Provides dependency injection capabilities by resolving class dependencies,
 * invoking methods, functions, and managing circular dependencies.
 *
 * @package Arbor\container
 */
class Resolver
{
    /**
     * @var Registry The dependency registry instance.
     */
    private Registry $registry;

    /**
     * @var  ServiceContainer instance.
     */
    private ServiceContainer $container;

    /**
     * @var array List of keys currently being resolved to detect circular dependencies.
     */
    private array $resolving = [];

    /**
     * @var array Supported primitive types.
     */
    private array $primitive_types = ['string', 'int', 'float', 'bool', 'array', 'callable', 'iterable', 'mixed'];

    /**
     * Resolver constructor.
     *
     * @param ServiceContainer $registry The dependency registry instance.
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        $this->registry = $this->container->getRegistry();
    }

    /**
     * Retrieve an instance of the given key.
     *
     * @param string $key The binding key or class name.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return mixed The resolved instance.
     * @throws Exception If a circular dependency is detected or the resolver type is unsupported.
     */
    public function get(string $key, array $customParams = [])
    {
        if (in_array($key, $this->resolving, true)) {
            throw new Exception("Circular dependency detected: {$key}");
        }

        $this->resolving[] = $key;

        try {
            // If registry has a binding
            if ($this->registry->has($key)) {
                $bond = $this->registry->getBinding($key);

                // If bond is shared, retrieve or create the shared instance.
                if ($bond->isShared()) {
                    $instance = $this->registry->getSharedInstance($key);

                    if ($instance) {
                        return $instance;
                    }

                    $newInstance = $this->instantiateService($bond->getResolver(), $customParams);
                    $this->registry->setSharedInstance($key, $newInstance);

                    return $newInstance;
                }

                // Create a new instance for non-shared bond.
                return $this->instantiateService($bond->getResolver(), $customParams);
            }

            // Try to auto-resolve a non-bound class.
            return $this->instantiateService($key, $customParams);
        } finally {
            array_pop($this->resolving);
        }
    }

    /**
     * Instantiates a service based on the provided resolver.
     *
     * @param mixed $resolver A Closure, class name, or callable array.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return mixed The instantiated service.
     * @throws Exception If the resolver type is unsupported.
     */
    protected function instantiateService(mixed $resolver, array $customParams = [])
    {
        // If the resolver is a closure.
        if ($resolver instanceof Closure) {
            return $this->call($resolver, $customParams);
        }

        // If the resolver is a class name.
        if (is_string($resolver) && class_exists($resolver)) {
            return $this->invokeClass($resolver, $customParams);
        }

        // If the resolver is a callable array.
        if (is_array($resolver) && is_callable($resolver)) {
            return $this->call($resolver, $customParams);
        }


        // Unsupported resolver type.
        throw new Exception("Unsupported resolver type provided. '{$resolver}' either class doesn't exists or it's not a valid fqn");
    }

    /**
     * Calls a given callable. Supports both instance and static method calls.
     *
     * @param callable|array|Closure $callable The callable to be invoked.
     * @param array $customParams Custom parameters for dependency resolution.
     * @param bool $isStatic If true, the method is called statically.
     * @return mixed The result of the callable execution.
     * @throws Exception If the callable is not valid.
     */
    public function call($callable, array $customParams = [], bool $isStatic = false)
    {
        // If the callable is an array (class and method)
        if (is_array($callable) && count($callable) === 2) {
            [$class, $method] = $callable;

            if ($isStatic) {
                return $this->invokeStaticMethod($class, $method, $customParams);
            } else {
                // For non-static, resolve the instance if class name is provided.
                $instance = is_string($class) ? $this->get($class) : $class;

                if (!method_exists($instance, $method)) {
                    throw new Exception("Method '{$method}' does not exist on class " . get_class($instance));
                }

                return $this->invokeMethod($instance, $method, $customParams);
            }
        }

        if (is_callable($callable)) {
            return $this->invokeFunction($callable, $customParams);
        }

        throw new Exception("Resolver::call expects a valid callable function, closure, or method");
    }

    /**
     * Instantiates a class using reflection.
     *
     * @param string $className The fully qualified class name.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return mixed The instantiated class.
     * @throws Exception If the class is not instantiable.
     */
    protected function invokeClass(string $className, array $customParams = [])
    {
        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new Exception("Class {$className} is not instantiable");
        }


        $constructor = $reflectionClass->getConstructor();
        $parameters = $this->getResolvedParameters($constructor, $customParams);

        return $reflectionClass->newInstanceArgs($parameters);
    }

    /**
     * Invokes a callable function using reflection.
     *
     * @param callable $callable The callable function.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return mixed The result of the function call.
     */
    protected function invokeFunction(callable $callable, array $customParams = [])
    {
        $reflection = new ReflectionFunction($callable);
        $parameters = $this->getResolvedParameters($reflection, $customParams);

        return $reflection->invokeArgs($parameters);
    }

    /**
     * Invokes an instance method using reflection.
     *
     * @param object $instance The instance on which to invoke the method.
     * @param string $method The method name.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return mixed The result of the method call.
     */
    protected function invokeMethod($instance, string $method, array $customParams = [])
    {
        $reflection = new ReflectionMethod($instance, $method);
        $parameters = $this->getResolvedParameters($reflection, $customParams);

        return $reflection->invokeArgs($instance, $parameters);
    }

    /**
     * Invokes a static method using reflection.
     *
     * @param string $class The class name.
     * @param string $method The static method name.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return mixed The result of the static method call.
     * @throws Exception If the method is not static.
     */
    protected function invokeStaticMethod(string $class, string $method, array $customParams = [])
    {
        $reflection = new ReflectionMethod($class, $method);
        if (!$reflection->isStatic()) {
            throw new Exception("Method '{$method}' on class {$class} is not static");
        }
        $parameters = $this->getResolvedParameters($reflection, $customParams);
        return $reflection->invokeArgs(null, $parameters);
    }

    /**
     * Resolves parameters for functions, methods, or constructors using reflection.
     *
     * @param ReflectionFunctionAbstract|null $reflection The reflection instance.
     * @param array $customParams Custom parameters for dependency resolution.
     * @return array The array of resolved parameters.
     */
    protected function getResolvedParameters(ReflectionFunctionAbstract|null $reflection, array $customParams = []): array
    {
        if ($reflection === null) {
            return [];
        }

        $parameters = $reflection->getParameters();

        $resolved = [];

        foreach ($parameters as $index => $parameter) {
            $resolved[] = $this->resolveParameterDependency($parameter, $customParams, $index);
        }

        return $resolved;
    }

    /**
     * Resolves a single parameter dependency.
     *
     * @param ReflectionParameter $parameter The parameter to resolve.
     * @param array $customParams Custom parameters provided by the user.
     * @param int $position position of parameter.
     * @return mixed The resolved parameter value.
     * @throws Exception If the dependency cannot be resolved.
     */
    protected function resolveParameterDependency(
        ReflectionParameter $parameter,
        array $customParams = [],
        int $position = 0
    ) {
        $paramName = $parameter->getName();
        $type = ($parameter->getType() instanceof ReflectionNamedType)
            ? $parameter->getType()->getName()
            : 'mixed';

        // 1. Named parameter wins
        if (array_key_exists($paramName, $customParams)) {
            return $customParams[$paramName];
        }

        // 2. Positional match by index
        if (array_key_exists($position, $customParams)) {
            return $customParams[$position];
        }

        // 3. Primitive resolution
        if (in_array($type, $this->primitive_types, true)) {
            return $this->resolvePrimitiveParameter($parameter, $type);
        }

        // 4. Special case: container injection
        if ($type === ServiceContainer::class || $type === ContainerInterface::class) {
            return $this->container;
        }

        // 5. Classes & interfaces
        if (interface_exists($type) || class_exists($type)) {
            $reflection = new ReflectionClass($type);

            if ($reflection->isInterface() || $reflection->isAbstract()) {
                if ($this->registry->has($type)) {
                    return $this->get($type);
                }

                $kind = $reflection->isInterface() ? 'interface' : 'abstract class';
                throw new Exception("Cannot resolve parameter '{$paramName}': no binding found for {$kind} '{$type}'");
            }
        }

        try {
            return $this->get($type);
        } catch (Throwable $th) {
            $message = "Failed to resolve parameter '{$paramName}' of type '{$type}'";
            $message .= ' ' . $th->getMessage();
            throw new Exception($message, 0, $th);
        }
    }


    /**
     * Resolves a primitive parameter by checking for default values or attributes.
     *
     * @param ReflectionParameter $parameter The parameter to resolve.
     * @param string $type The primitive type of the parameter.
     * @return mixed The resolved primitive value.
     * @throws Exception If a required primitive parameter cannot be resolved.
     */
    protected function resolvePrimitiveParameter(ReflectionParameter $parameter, string $type)
    {

        $paramName = $parameter->getName();
        $attributes = $parameter->getAttributes();

        if (count($attributes) > 0) {
            return $this->resolveAttributedParameter($attributes);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isOptional()) {
            return null;
        }

        throw new Exception("Cannot resolve required primitive parameter: $paramName of type $type");
    }

    /**
     * Processes attributes to resolve parameter values.
     *
     * @param array $attributes An array of reflection attributes.
     * @return mixed The resolved value from the attribute.
     */
    protected function resolveAttributedParameter(array $attributes): mixed
    {
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if (method_exists($instance, 'require')) {
                $this->invokeMethod($instance, 'require');
            }
            if (method_exists($instance, 'resolve')) {
                return $instance->resolve();
            }
        }

        return null;
    }
}
