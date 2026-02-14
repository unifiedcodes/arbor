<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use Arbor\storage\Registry;


class Storage
{
    protected Registry $registry;


    public function __construct()
    {
        $this->registry = new Registry();
    }


    public function mount(
        string $schemeName,
        StoreInterface $store,
        string $root = '',
        ?string $baseUrl = null,
        bool $public = false
    ) {
        $this->registry->register(
            $schemeName,
            $store,
            $root,
            $baseUrl,
            $public
        );
    }
}
