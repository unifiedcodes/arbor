<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;


class Storage
{
    protected Registry $registry;


    public function __construct()
    {
        $this->registry = new Registry();
    }


    public function mount(string $scheme, StoreInterface $store)
    {
        $this->registry->register($scheme, $store);
    }
}
