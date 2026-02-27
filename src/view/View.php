<?php

namespace Arbor\view;


use Arbor\view\Schemes;


class View
{
    private Schemes $schemes;

    public function __construct()
    {
        // create schemes.
        $this->schemes = new Schemes();

        // create presets.
        // create view stack.
        // create renderer.
    }
}
