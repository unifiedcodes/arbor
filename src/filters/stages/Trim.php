<?php

namespace Arbor\filters\stages;

use Arbor\contracts\filter\StageInterface;

class Trim implements StageInterface
{
    public function process(mixed $input, callable $next): mixed
    {
        if (is_string($input)) {
            $input = trim($input);
        }

        return $next($input);
    }
}
