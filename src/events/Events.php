<?php

namespace Arbor\events;

use Arbor\events\EventInterface;
use Arbor\events\ListenerInterface;
use Arbor\events\Registry;
use Arbor\events\Dispatcher;
use Closure;

/**
 * High-level façade for interacting with the Arbor event system.
 *
 * Provides a simplified API for:
 * - Registering event listeners
 * - Dispatching events
 *
 * This class composes the Registry and Dispatcher,
 * acting as a convenience entry point for the
 * application layer.
 *
 * It abstracts internal event infrastructure
 * and exposes a minimal, developer-friendly interface.
 *
 * @package Arbor\events
 */
final class Events
{
    /**
     * @param Registry   $registry   Listener storage component.
     * @param Dispatcher $dispatcher Event dispatching component.
     */
    public function __construct(
        private Registry $registry,
        private Dispatcher $dispatcher
    ) {}

    /**
     * Registers a listener for the given event class.
     *
     * @param string $eventClass Fully-qualified event class name.
     * @param ListenerInterface|Closure $listener Listener instance or closure.
     */
    public function on(
        string $eventClass,
        ListenerInterface|Closure $listener
    ): void {
        $this->registry->add($eventClass, $listener);
    }

    /**
     * Dispatches the given event instance.
     *
     * @param EventInterface $event
     */
    public function dispatch(EventInterface $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
