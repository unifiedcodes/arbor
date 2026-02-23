<?php

namespace Arbor\events;

use Arbor\events\EventInterface;
use Arbor\events\ListenerInterface;

/**
 * Dispatches events to their registered listeners.
 *
 * The Dispatcher resolves all applicable listeners for a given
 * event instance and invokes them in registration order.
 *
 * Listener resolution includes:
 * - The event's concrete class
 * - All parent classes
 * - All implemented interfaces
 *
 * Duplicate listener instances are prevented from executing
 * multiple times for the same dispatch cycle.
 *
 * The Dispatcher relies on the Registry for listener storage
 * and does not maintain its own state.
 *
 * @package Arbor\events
 */
final class Dispatcher
{
    /**
     * @param Registry $registry Listener registry instance.
     */
    public function __construct(
        private readonly Registry $registry
    ) {}

    /**
     * Resolves all listeners applicable to the given event.
     *
     * This includes listeners registered for:
     * - The event's exact class
     * - Parent classes
     * - Implemented interfaces
     *
     * Ensures that each listener instance is executed only once
     * per dispatch cycle.
     *
     * @param EventInterface $event
     * @return array<int, ListenerInterface>
     */
    private function resolveListeners(EventInterface $event): array
    {
        $eventClass = $event::class;

        $classes = array_merge(
            [$eventClass],
            class_parents($eventClass) ?: [],
            class_implements($eventClass) ?: []
        );

        $resolved = [];
        $seen = [];

        foreach ($classes as $class) {
            foreach ($this->registry->get($class) as $listener) {

                $id = spl_object_id($listener);

                if (isset($seen[$id])) {
                    continue;
                }

                $seen[$id] = true;
                $resolved[] = $listener;
            }
        }

        return $resolved;
    }

    /**
     * Dispatches an event to all resolved listeners.
     *
     * @param EventInterface $event
     */
    public function dispatch(EventInterface $event): void
    {
        foreach ($this->resolveListeners($event) as $listener) {
            $this->invoke($listener, $event);
        }
    }

    /**
     * Invokes a single listener with the provided event.
     *
     * @param ListenerInterface $listener
     * @param EventInterface $event
     */
    private function invoke(
        ListenerInterface $listener,
        EventInterface $event
    ): void {
        $listener->handle($event);
    }
}
