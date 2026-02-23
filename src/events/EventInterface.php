<?php

namespace Arbor\events;

/**
 * Marker interface for all domain events in Arbor.
 *
 * An event represents something that has already occurred
 * within the system. Events are immutable data carriers
 * dispatched through the event bus to notify listeners
 * and subscribers.
 *
 * This interface defines no methods and exists solely for:
 * - Type safety
 * - Architectural boundaries
 * - Event system consistency
 *
 * All event classes must implement this interface.
 *
 * @package Arbor\events
 */
interface EventInterface {}
