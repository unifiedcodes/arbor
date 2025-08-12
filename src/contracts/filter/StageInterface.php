<?php

namespace Arbor\contracts\filter;


interface StageInterface
{
    /**
     * Process the given input value.
     *
     * @param mixed $input
     * @param callable $next
     * @return mixed
     */
    public function process(mixed $input, callable $next): mixed;
}
