<?php

namespace Arbor\storage;


use Arbor\storage\stores\StoreInterface;
use Exception;


class Registry
{
    private array $schemes = [];


    public function register(string $scheme, StoreInterface $store)
    {
        if (isset($schemes[$scheme])) {
            throw new Exception("scheme: {$scheme} already registered in storage");
        }

        $this->schemes[$scheme] = $store;
    }
}
