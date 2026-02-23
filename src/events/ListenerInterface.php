<?php

namespace Arbor\events;

use Arbor\events\EventInterface;

/**
 * Contract for all event listeners in Arbor.
 *
 * A listener reacts to a dispatched event and performs
 * a side-effect (logging, notifications, state updates, etc.).
 *
 * Implementations should:
 * - Contain application or infrastructure logic
 * - Avoid mutating the event
 * - Remain focused on a single responsibility
 *
 * The event dispatcher invokes the handle() method
 * when a matching event is dispatched.
 *
 * @package Arbor\events
 */
interface ListenerInterface
{
    /**
     * Handles a dispatched event.
     *
     * @param EventInterface $event The event being dispatched.
     */
    public function handle(EventInterface $event): void;
}
