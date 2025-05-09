<?php

namespace Arbor\support;

class Helpers
{
    public static function load(): void
    {
        foreach (glob(__DIR__ . '/helpers/*.php') as $file) {
            require_once $file;
        }
    }
}
