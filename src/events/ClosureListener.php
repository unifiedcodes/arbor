<?php

namespace Arbor\events;

use Arbor\events\EventInterface;
use Arbor\events\ListenerInterface;
use Closure;

/**
 * Adapts a Closure into a ListenerInterface implementation.
 *
 * This allows developers to register inline closures
 * as event listeners while maintaining a consistent
 * ListenerInterface contract internally.
 *
 * The closure receives the dispatched event instance
 * as its only argument.
 *
 * @package Arbor\events
 */
final class ClosureListener implements ListenerInterface
{
    /**
     * @param Closure $closure Closure that accepts EventInterface.
     */
    public function __construct(
        private readonly Closure $closure
    ) {}

    /**
     * Handles the dispatched event by invoking the wrapped closure.
     *
     * @param EventInterface $event
     */
    public function handle(EventInterface $event): void
    {
        ($this->closure)($event);
    }
}
