<?php

namespace Arbor\events;

use Arbor\events\ListenerInterface;
use InvalidArgumentException;
use Closure;

/**
 * Maintains a mapping between event classes and their listeners.
 *
 * The Registry is responsible only for storing and retrieving
 * listeners associated with specific event classes.
 *
 * It enforces:
 * - The event class must exist
 * - The event class must implement EventInterface
 * - Closures are automatically wrapped into ClosureListener
 *
 * This class does not dispatch events. It is a storage
 * mechanism used internally by the event dispatcher.
 *
 * @package Arbor\events
 */
final class Registry
{
    /**
     * @var array<string, array<int, ListenerInterface>>
     */
    private array $listeners = [];

    /**
     * Registers a listener for a given event class.
     *
     * @param string $eventClass Fully-qualified event class name.
     * @param ListenerInterface|Closure $listener Listener instance or Closure.
     *
     * @throws InvalidArgumentException If the event class does not exist
     *                                  or does not implement EventInterface.
     */
    public function add(string $eventClass, ListenerInterface|Closure $listener): void
    {
        if (!class_exists($eventClass)) {
            throw new InvalidArgumentException(
                "Event class '{$eventClass}' does not exist."
            );
        }

        if (!is_subclass_of($eventClass, EventInterface::class)) {
            throw new InvalidArgumentException(
                "Event class '{$eventClass}' must implement EventInterface."
            );
        }

        if ($listener instanceof Closure) {
            $listener = new ClosureListener($listener);
        }

        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Returns all listeners registered for the given event class.
     *
     * @param string $eventClass
     * @return array<int, ListenerInterface>
     */
    public function get(string $eventClass): array
    {
        return $this->listeners[$eventClass] ?? [];
    }

    /**
     * Determines whether listeners exist for the given event class.
     *
     * @param string $eventClass
     */
    public function has(string $eventClass): bool
    {
        return !empty($this->listeners[$eventClass]);
    }

    /**
     * Returns the entire listener map.
     *
     * @return array<string, array<int, ListenerInterface>>
     */
    public function all(): array
    {
        return $this->listeners;
    }
}
