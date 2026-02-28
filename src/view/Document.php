<?php

namespace Arbor\view;


final class Document
{
    public function __construct(
        private Component $component,
    ) {}

    public function component(): Component
    {
        return $this->component;
    }
}
