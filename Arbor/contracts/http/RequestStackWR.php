<?php

namespace Arbor\contracts\http;

use Arbor\http\context\RequestContext;

interface RequestStackWR
{
    public function push(RequestContext $context): void;
    public function pop(): ?RequestContext;
    public function clear(): void;
    public function peek(int $depth = 1): ?RequestContext;
}
