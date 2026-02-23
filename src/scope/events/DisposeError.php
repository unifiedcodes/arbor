<?php

namespace Arbor\scope\events;

use Arbor\scope\Disposable;
use Throwable;

/**
 * Fired when a Disposable throws an exception
 * during frame disposal.
 */
final class DisposeError
{
    public function __construct(
        public readonly Disposable $disposable,
        public readonly Throwable $exception,
        public readonly int $frameDepth
    ) {}
}
