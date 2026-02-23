<?php

namespace Arbor\exception\events;

use Arbor\exception\ExceptionContext;

final class ExceptionOccurred
{
    public function __construct(
        public readonly ExceptionContext $exception,
    ) {}
}
